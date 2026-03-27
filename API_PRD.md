# ZEPLOW API — PRODUCT REQUIREMENTS DOCUMENT (PRD)

**Version:** 1.2
**Date:** March 27, 2026
**Derived From:** Zeplow Platform Central PRD v1.1 (March 11, 2026)
**Original Author:** Shakib Bin Kabir
**Status:** Final — Ready for Implementation

---

## TABLE OF CONTENTS

1. Application Overview
2. System Context & Data Flow
3. Laravel Configuration & Project Setup
4. Database Schema
5. Eloquent Models
6. Authentication & Middleware
7. Route Definitions
8. Controller Specifications — Public Endpoints
9. Controller Specifications — Internal Endpoints
10. Controller Specifications — Health Check
11. Deploy Hook Service
12. Cache Strategy & Invalidation
13. CORS Configuration
14. Rate Limiting
15. Error Handling & Exception Handler
16. Email Notifications
17. Logging Strategy
18. Security Headers & Frontend Security
19. Environment Variables
20. DNS & SSL
21. Performance Requirements
22. Cloudflare Integration
23. Directory Structure
24. Implementation Order
25. Testing Checklist
26. Post-Launch Checklist

---

## 1. APPLICATION OVERVIEW

### 1.1 What This App Is

The Zeplow API is a **Laravel 11 API-only application** that serves as the central data layer for the entire Zeplow platform. It has two jobs:

1. **Receive and store content** synced from the CMS app (cms.zeplow.com)
2. **Serve JSON responses** to the three Next.js frontend sites (and any future consumers)

It is not a CMS. It has no admin panel, no Blade views, no sessions, no browser-facing UI. It is a pure REST API.

### 1.2 Properties

| Property | Value |
|:---|:---|
| Framework | Laravel 11 |
| Admin Panel | None (API-only) |
| Database | MySQL — `api_zeplow` |
| Hosting | cPanel shared hosting (existing plan) |
| URL | `https://api.zeplow.com` |
| PHP Version | 8.2+ |
| Purpose | Central API for all frontends + future systems |
| Budget | $0/month additional |

### 1.3 Consumers

| Consumer | Interaction | Auth |
|:---|:---|:---|
| CMS app (cms.zeplow.com) | Pushes content via internal endpoints | Bearer token (API key) |
| Next.js — zeplow.com | Fetches content at build time (static export) | None (public) |
| Next.js — narrative.zeplow.com | Fetches content at build time (static export) | None (public) |
| Next.js — logic.zeplow.com | Fetches content at build time (static export) | None (public) |
| Frontend visitors | Submit contact forms (runtime POST) | None (public, rate-limited) |
| Future systems (ERP, mobile, etc.) | Will consume the same public API | TBD (API structure supports expansion) |

### 1.4 Sites Served

The API serves content for three distinct websites. Each is identified by a `site_key`:

| Site | site_key | Domain | Description |
|:---|:---|:---|:---|
| Zeplow Parent | `parent` | zeplow.com | Authority, group overview, venture hub |
| Zeplow Narrative | `narrative` | narrative.zeplow.com | Creative agency — brand storytelling services |
| Zeplow Logic | `logic` | logic.zeplow.com | Tech company — automation & dev services |

### 1.5 Non-Goals (Explicitly Out of Scope for This App)

- Filament admin panel or any UI
- Session management or cookie handling
- Blade views or HTML rendering
- Real-time features (WebSockets, broadcasting)
- User registration or multi-user auth
- Direct database writes from frontends (except contact form)
- Image storage or media management (handled by CMS)
- ERP, billing, or client portal endpoints (API structure supports them, but we don't build them now)

---

## 2. SYSTEM CONTEXT & DATA FLOW

### 2.1 Where the API Sits in the Architecture

```
┌──────────────────────────────────────────────────┐
│  CMS APP (cms.zeplow.com)                         │
│  Laravel 11 + Filament v3                         │
│  MySQL DB: cms_zeplow                             │
│                                                   │
│  Editor publishes content →                       │
│  Observer dispatches Job →                        │
│  Job sends HTTP POST to API app                   │
└──────────────────┬───────────────────────────────┘
                   │
                   │  POST api.zeplow.com/internal/v1/content/sync
                   │  POST api.zeplow.com/internal/v1/config/sync
                   │  DELETE api.zeplow.com/internal/v1/content/sync
                   │  (Authenticated via Bearer API key)
                   │
                   ▼
┌──────────────────────────────────────────────────┐
│  THIS APP: API (api.zeplow.com)                   │
│  Laravel 11 (API-only, no Filament, no sessions)  │
│  MySQL DB: api_zeplow                             │
│                                                   │
│  1. Receives content from CMS → stores in own DB  │
│  2. Serves public REST API for frontends           │
│  3. On content sync → fires Cloudflare deploy hook │
│  4. Stores contact form submissions + sends email  │
└──────────────────┬───────────────────────────────┘
                   │
                   │  a) POST https://api.cloudflare.com/pages/webhooks/...
                   │     (Deploy hook per site — triggers static rebuild)
                   │
                   │  b) GET api.zeplow.com/sites/v1/{siteKey}/...
                   │     (Next.js calls these during build to fetch content)
                   │
                   ▼
┌──────────────────────────────────────────────────┐
│  CLOUDFLARE PAGES (Free Tier)                     │
│                                                   │
│  3 Cloudflare Pages projects:                     │
│    zeplow-parent    → zeplow.com                  │
│    zeplow-narrative → narrative.zeplow.com         │
│    zeplow-logic     → logic.zeplow.com             │
│                                                   │
│  On deploy hook → pulls code from GitHub →         │
│  Runs next build → calls API for data →            │
│  Outputs static HTML → deploys to CDN              │
└──────────────────────────────────────────────────┘
```

### 2.2 Data Flow — Content Sync (CMS → API)

```
Step 1: CMS app sends HTTP POST to /internal/v1/content/sync
        - Payload: { site_key, content_type, slug, data (JSON), published_at }
        - Auth: Bearer token in Authorization header
        - Retry: CMS retries 3 times with 5-second backoff on failure

Step 2: API validates the payload (site_key, content_type, slug, data required)

Step 3: API upserts content into site_content table
        - Unique key: (site_key, content_type, slug)
        - Existing record → update data + synced_at
        - New record → create

Step 4: API invalidates relevant cache keys
        - List cache: Increments cache version counter for site:{siteKey}:{cachePrefix}
          (old parameterized list cache keys become orphaned and expire naturally after TTL)
        - Detail cache: site:{siteKey}:{cachePrefix}:{slug} (directly forgotten)
        - Uses TYPE_TO_CACHE_PREFIX mapping (see Section 12)

Step 5: API fires Cloudflare Pages deploy hook for the affected site
        - POST to the deploy hook URL stored in .env
        - Logs result in deploy_logs table

Step 6: Cloudflare rebuilds the affected frontend site (~60-90 seconds)
```

### 2.3 Data Flow — Content Delete (CMS → API)

```
Step 1: CMS sends HTTP DELETE to /internal/v1/content/sync
        - Payload: { site_key, content_type, slug }
        - Auth: Bearer token

Step 2: API deletes the matching record from site_content table

Step 3: API invalidates relevant cache keys (version counter increment + detail cache clear)

Step 4: API fires deploy hook for the affected site
```

### 2.4 Data Flow — Config Sync (CMS → API)

```
Step 1: CMS sends HTTP POST to /internal/v1/config/sync
        - Payload: { site_key, config (JSON object) }
        - Auth: Bearer token

Step 2: API upserts into site_configs table (unique on site_key)

Step 3: API invalidates config cache: site:{siteKey}:config

Step 4: API fires deploy hook for the affected site
```

### 2.5 Data Flow — Public API Request (Frontend → API)

```
Step 1: Next.js build process calls GET /sites/v1/{siteKey}/pages (or any public endpoint)

Step 2: API checks file-based cache for this exact cache key

Step 3: Cache hit → return cached JSON instantly (with Cache-Control: public, max-age=3600)
        Cache miss → query api_zeplow database → format response → store in cache → return

Step 4: Next.js uses the JSON to generate static HTML pages

Note: This happens at BUILD TIME only. Live visitors never hit the API —
they receive pre-built static HTML from Cloudflare CDN.
Exception: Contact form submissions are runtime POSTs from the browser.
```

### 2.6 Data Flow — Contact Form Submission (Visitor → API)

```
Step 1: Visitor fills out contact form on any frontend site (runtime, in browser)

Step 2: Browser POSTs to /sites/v1/{siteKey}/contact
        - Payload: { name, email, company, message, budget_range, source }
        - Includes hidden honeypot field (fax_number)

Step 3: API checks honeypot — if filled, return fake 200 success (bot detected, nothing stored)

Step 4: API validates form data

Step 5: API stores in contact_submissions table (with site_key)

Step 6: API sends email notification to hello@zeplow.com via SMTP

Step 7: API returns { status: "received", message: "Thank you..." }
        - Email failure does NOT fail the response — submission is stored regardless
```

---

## 3. LARAVEL CONFIGURATION & PROJECT SETUP

### 3.1 Installation

```bash
composer create-project laravel/laravel api-app
cd api-app
composer require laravel/sanctum
composer require guzzlehttp/guzzle
```

**No other packages.** The API app has two jobs: store content and serve JSON. Keep it lean.

### 3.2 Packages Required

| Package | Purpose | Version |
|:---|:---|:---|
| `laravel/sanctum` | API authentication (internal endpoints) | ^4.0 |
| `guzzlehttp/guzzle` | HTTP client for deploy hooks | ^7.0 (included in Laravel) |

### 3.3 Lean Service Provider Configuration

This app must boot fast. Strip everything unnecessary.

**Remove/disable these service providers:**

- Session service provider (API doesn't need sessions)
- View service provider (no Blade views)
- Broadcasting service provider (no real-time)

**Keep these:**

- Route service provider
- Auth service provider (for Sanctum API key auth)
- Cache service provider (for response caching)
- Database service provider
- Validation service provider
- Encryption service provider
- Filesystem service provider
- Foundation service provider
- Hashing service provider
- Pipeline service provider
- Queue service provider

```php
// config/app.php — Key configuration

'providers' => [
    // Framework
    Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    Illuminate\Hashing\HashServiceProvider::class,
    Illuminate\Pipeline\PipelineServiceProvider::class,
    Illuminate\Queue\QueueServiceProvider::class,
    Illuminate\Routing\RoutingServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class,

    // App
    App\Providers\AppServiceProvider::class,
    App\Providers\RouteServiceProvider::class,

    // Sanctum for API auth
    Laravel\Sanctum\SanctumServiceProvider::class,
],
```

### 3.4 Key Config Overrides

**Session driver** — set to `array` (no persistent sessions):

```php
// .env
SESSION_DRIVER=array
```

**Cache driver** — file-based (cPanel compatible, no Redis needed):

```php
// config/cache.php
return [
    'default' => 'file',
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
    ],
    'prefix' => 'zeplow_api',
];
```

**Queue driver** — synchronous initially (switch to database/redis later without code changes):

```php
// .env
QUEUE_CONNECTION=sync
```

---

## 4. DATABASE SCHEMA

### 4.1 Database Name

`api_zeplow` — created on the same cPanel hosting as the CMS database, but completely separate.

### 4.2 Complete Schema

```sql
-- ============================================
-- DATABASE: api_zeplow
-- Used by: API Laravel app (api.zeplow.com)
-- ============================================

-- Site Content (flat store — receives synced content from CMS)
--
-- This is the core table. ALL content types (pages, projects, blog posts,
-- testimonials, team members) are stored here in a single table.
-- The `data` column holds the full JSON payload for each content item.
-- Content is uniquely identified by (site_key, content_type, slug).
--
CREATE TABLE site_content (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,              -- "parent", "narrative", "logic"
    content_type VARCHAR(50) NOT NULL,          -- "page", "project", "blog_post", "testimonial", "team_member"
    slug VARCHAR(255) NOT NULL,                 -- URL-friendly identifier, unique per site+type
    data JSON NOT NULL,                         -- Full content payload (varies by content_type)
    published_at TIMESTAMP NULL,                -- When content was published (NULL = draft/unpublished)
    synced_at TIMESTAMP NOT NULL,               -- When this record was last synced from CMS
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_site_type_slug (site_key, content_type, slug),
    INDEX idx_site_key (site_key),
    INDEX idx_content_type (content_type),
    INDEX idx_published (published_at)
);

-- Site Configs (synced from CMS)
--
-- One config record per site. Stores navigation, footer, CTA, social links.
-- Synced separately from content because it has a different structure.
--
CREATE TABLE site_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL UNIQUE,       -- One config per site
    config JSON NOT NULL,                       -- Full config payload (nav, footer, CTA, socials)
    synced_at TIMESTAMP NOT NULL,               -- Last sync timestamp
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Contact Submissions (from frontend contact forms)
--
-- Stores every valid form submission. Email notification is sent
-- asynchronously but the record is always stored regardless of email success.
--
CREATE TABLE contact_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,              -- Which site the form was submitted from
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    company VARCHAR(255) NULL,
    message TEXT NOT NULL,
    budget_range VARCHAR(100) NULL,             -- "$3,000 - $5,000", etc.
    source VARCHAR(255) NULL,                   -- "narrative.zeplow.com", etc.
    is_read BOOLEAN NOT NULL DEFAULT FALSE,     -- For future admin review feature
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_site_key (site_key),
    INDEX idx_is_read (is_read)
);

-- Deploy Log (tracks Cloudflare deploy hook triggers)
--
-- Every time the API fires a deploy hook, the result is logged here.
-- Used for monitoring and debugging failed deploys.
--
CREATE TABLE deploy_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,              -- Which site was deployed
    trigger_source VARCHAR(50) NOT NULL,        -- "content_sync", "content_delete", "config_sync", "manual"
    status ENUM('triggered', 'success', 'failed') NOT NULL DEFAULT 'triggered',
    response_code INT NULL,                     -- HTTP status from Cloudflare
    response_body TEXT NULL,                    -- First 500 chars of Cloudflare response
    last_error TEXT NULL,                       -- Error message if failed
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_site_key (site_key),
    INDEX idx_status (status)
);

-- API Keys (for CMS → API authentication)
--
-- Stores the shared API key(s) that the CMS uses to authenticate
-- when pushing content to internal endpoints.
--
CREATE TABLE api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,                 -- Human-readable label: "CMS Sync Key"
    `key` VARCHAR(64) NOT NULL UNIQUE,          -- SHA-256 hash of the API key (plaintext shown once at generation)
    scope VARCHAR(50) NOT NULL DEFAULT 'internal',  -- "internal" for CMS→API sync
    is_active BOOLEAN NOT NULL DEFAULT TRUE,    -- Can be deactivated without deletion
    last_used_at TIMESTAMP NULL,                -- Updated on every authenticated request
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_key_active (`key`, is_active)
);

-- Laravel default tables (created by migrations that ship with Laravel)
-- These are required for the framework to function:
--
-- cache              — file-based cache metadata (if using database cache driver)
-- jobs               — queued jobs (if using database queue driver)
-- failed_jobs        — failed queue jobs
-- migrations         — migration tracking
```

### 4.3 Data Column Structures

The `data` JSON column in `site_content` holds different structures depending on `content_type`. Here is the exact shape of each:

#### content_type: "page"

```json
{
  "title": "About Us",
  "template": "about",
  "content": [
    {
      "type": "hero",
      "data": {
        "heading": "We don't make ads...",
        "subheading": null,
        "cta_text": "Book a Heartbeat Review",
        "cta_url": "/contact",
        "background_color": "#034c3c"
      }
    },
    {
      "type": "text",
      "data": {
        "heading": "Our Story",
        "body": "<p>HTML content here...</p>"
      }
    }
  ],
  "seo": {
    "title": "About — Zeplow Narrative",
    "description": "We turn businesses into stories worth following.",
    "og_image": "https://cms.zeplow.com/storage/og/narrative-about.jpg"
  },
  "sort_order": 1
}
```

**Possible block types in `content` array:** `hero`, `text`, `cards`, `cta`, `image`, `gallery`, `testimonials`, `team`, `projects`, `stats`, `divider`, `raw_html`.

#### content_type: "project"

```json
{
  "title": "Tututor.ai",
  "one_liner": "An AI-powered tutoring platform that personalizes learning...",
  "client_name": "Tututor",
  "industry": "EdTech",
  "url": "https://tututor.ai",
  "challenge": "Manual tutoring couldn't scale...",
  "solution": "Built an AI-powered adaptive learning engine...",
  "outcome": "10x student engagement, 60% reduction in tutor workload",
  "tech_stack": ["Next.js", "Python", "PostgreSQL", "OpenAI"],
  "images": [
    "https://cms.zeplow.com/storage/projects/tututor-1.jpg",
    "https://cms.zeplow.com/storage/projects/tututor-2.jpg"
  ],
  "tags": ["web-app", "ai", "saas"],
  "featured": true,
  "sort_order": 0
}
```

#### content_type: "blog_post"

```json
{
  "title": "Why Your Brand Feels Forgettable",
  "excerpt": "Most brands fail not because they lack quality...",
  "body": "<h2>The Invisibility Tax</h2><p>Every day your brand goes unnoticed...</p>",
  "cover_image": "https://cms.zeplow.com/storage/blog/cover-1.jpg",
  "tags": ["branding", "strategy"],
  "author": "Shadman Sakib",
  "seo": {
    "title": "Why Your Brand Feels Forgettable — Zeplow Narrative",
    "description": "Most brands fail not because they lack quality..."
  }
}
```

#### content_type: "testimonial"

```json
{
  "name": "John Doe",
  "role": "CEO",
  "company": "Example Corp",
  "quote": "Our business finally feels under control.",
  "avatar": "https://cms.zeplow.com/storage/testimonials/john.jpg",
  "sort_order": 0
}
```

#### content_type: "team_member"

```json
{
  "name": "Shadman Sakib",
  "role": "Co-Founder & CEO",
  "bio": "Strategy, direction, and brand & venture leadership.",
  "photo": "https://cms.zeplow.com/storage/team/shadman.jpg",
  "linkedin": "https://linkedin.com/in/shadmansakib",
  "email": "shadman@zeplow.com",
  "is_founder": true,
  "sort_order": 0
}
```

### 4.4 Config Column Structure

The `config` JSON column in `site_configs` holds:

```json
{
  "site_name": "Zeplow Narrative",
  "domain": "narrative.zeplow.com",
  "tagline": "Stories that sell.",
  "nav_items": [
    { "label": "About", "url": "/about", "is_external": false },
    { "label": "Services", "url": "/services", "is_external": false },
    { "label": "Work", "url": "/work", "is_external": false },
    { "label": "Process", "url": "/process", "is_external": false },
    { "label": "Insights", "url": "/insights", "is_external": false },
    { "label": "Contact", "url": "/contact", "is_external": false }
  ],
  "footer_links": [
    {
      "group_title": "The Zeplow Group",
      "links": [
        { "label": "Zeplow Narrative", "url": "https://narrative.zeplow.com" },
        { "label": "Zeplow Logic", "url": "https://logic.zeplow.com" },
        { "label": "Insights", "url": "/insights" }
      ]
    }
  ],
  "footer_text": "© 2026 Zeplow LTD. All rights reserved.",
  "cta_text": "Book a Heartbeat Review",
  "cta_url": "/contact",
  "social_links": {
    "linkedin": "https://linkedin.com/company/zeplow",
    "instagram": "https://instagram.com/zeplow",
    "whatsapp": "https://wa.me/8801XXXXXXXXX"
  },
  "contact_email": "hello@zeplow.com"
}
```

---

## 5. ELOQUENT MODELS

### 5.1 SiteContent

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $table = 'site_content';

    protected $fillable = [
        'site_key',
        'content_type',
        'slug',
        'data',
        'published_at',
        'synced_at',
    ];

    protected $casts = [
        'data'         => 'array',
        'published_at' => 'datetime',
        'synced_at'    => 'datetime',
    ];
}
```

### 5.2 SiteConfig

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteConfig extends Model
{
    protected $table = 'site_configs';

    protected $fillable = [
        'site_key',
        'config',
        'synced_at',
    ];

    protected $casts = [
        'config'    => 'array',
        'synced_at' => 'datetime',
    ];
}
```

### 5.3 ContactSubmission

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $table = 'contact_submissions';

    protected $fillable = [
        'site_key',
        'name',
        'email',
        'company',
        'message',
        'budget_range',
        'source',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
```

### 5.4 DeployLog

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeployLog extends Model
{
    protected $table = 'deploy_logs';

    protected $fillable = [
        'site_key',
        'trigger_source',
        'status',
        'response_code',
        'response_body',
        'last_error',
    ];
}
```

### 5.5 ApiKey

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'key',
        'scope',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

}
```

---

## 6. AUTHENTICATION & MIDDLEWARE

### 6.1 Authentication Strategy

| Endpoint Group | Auth Method | Details |
|:---|:---|:---|
| Internal (`/internal/v1/*`) | Bearer token (API key) | 64-char random string, SHA-256 hashed before validation against `api_keys` table |
| Public (`/sites/v1/*` GET) | None | Open read-only endpoints |
| Public (`/sites/v1/*/contact` POST) | None | Rate-limited, honeypot spam protection |
| Health (`/health`) | None | Open monitoring endpoint |

### 6.2 API Key Format

- 64-character random string generated via `Str::random(64)`
- Stored as a **SHA-256 hash** in the `api_keys` table — the plaintext key is shown once at generation and cannot be retrieved
- Same plaintext key is configured in the CMS app's `.env` as `ZEPLOW_API_KEY`
- Sent in `Authorization: Bearer {api_key}` header
- On validation, the incoming bearer token is hashed with SHA-256 before querying the database

### 6.3 ValidateApiKey Middleware

```php
<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next, string $scope = 'internal')
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Missing API key'], 401);
        }

        $apiKey = ApiKey::where('key', hash('sha256', $token))
            ->where('is_active', true)
            ->where('scope', $scope)
            ->first();

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API key'], 403);
        }

        // Track usage
        $apiKey->update(['last_used_at' => now()]);

        return $next($request);
    }
}
```

### 6.4 ValidateSiteKey Middleware

Validates `{siteKey}` route parameter against a whitelist of known site keys. Returns 404 immediately for unknown site keys, preventing database queries and cache pollution for invalid keys.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateSiteKey
{
    private const VALID_SITE_KEYS = ['parent', 'narrative', 'logic'];

    public function handle(Request $request, Closure $next)
    {
        $siteKey = $request->route('siteKey');
        if (!in_array($siteKey, self::VALID_SITE_KEYS)) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return $next($request);
    }
}
```

### 6.5 Middleware Registration

Register the middleware in `app/Http/Kernel.php` (or `bootstrap/app.php` in Laravel 11):

```php
// Route middleware aliases
'api_key'  => \App\Http\Middleware\ValidateApiKey::class,
'site_key' => \App\Http\Middleware\ValidateSiteKey::class,
```

### 6.6 Key Generation (One-Time Setup)

Run this via tinker or a seeder to generate the initial API key:

```php
use Illuminate\Support\Str;
use App\Models\ApiKey;

$key = Str::random(64);

ApiKey::create([
    'name'  => 'CMS Sync Key',
    'key'   => hash('sha256', $key),
    'scope' => 'internal',
]);

echo "API Key: {$key}";
// Copy this key to the CMS app's .env as ZEPLOW_API_KEY
// Store it securely — the plaintext key is shown only once and cannot be retrieved
```

---

## 7. ROUTE DEFINITIONS

### 7.1 Complete Route Map

```
api.zeplow.com/
│
├── /sites/v1/{siteKey}/              ← Public API (Next.js consumes at build time)
│   ├── GET  /config                  ← Site configuration (nav, footer, CTA, socials)
│   ├── GET  /pages                   ← All published pages (list)
│   ├── GET  /pages/{slug}            ← Single page with content blocks
│   ├── GET  /projects                ← All published projects (list, with pagination)
│   ├── GET  /projects/{slug}         ← Single project detail
│   ├── GET  /blog                    ← All published blog posts (list, with pagination)
│   ├── GET  /blog/{slug}             ← Single blog post
│   ├── GET  /testimonials            ← All published testimonials
│   ├── GET  /team                    ← All team members
│   └── POST /contact                 ← Contact form submission
│
├── /internal/v1/                     ← Private API (CMS → API sync)
│   ├── POST   /content/sync          ← Receive content from CMS
│   ├── POST   /config/sync           ← Receive site config from CMS
│   ├── DELETE /content/sync           ← Delete content from API
│   ├── POST   /content/sync-all      ← Full resync (all content for a site)
│   └── POST   /deploy/trigger/{siteKey} ← Manually trigger deploy for a site
│
└── /health                           ← Health check endpoint
    └── GET /                         ← Returns { status: "ok", timestamp: "..." }
```

### 7.2 Route Definition File

```php
<?php

// routes/api.php

use App\Http\Controllers\Sites\SiteConfigController;
use App\Http\Controllers\Sites\SitePageController;
use App\Http\Controllers\Sites\SiteProjectController;
use App\Http\Controllers\Sites\SiteBlogController;
use App\Http\Controllers\Sites\SiteTestimonialController;
use App\Http\Controllers\Sites\SiteTeamController;
use App\Http\Controllers\Sites\ContactController;
use App\Http\Controllers\Internal\ContentSyncController;
use App\Http\Controllers\Internal\ConfigSyncController;
use App\Http\Controllers\Internal\DeployController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes (consumed by Next.js at build time + contact form at runtime)
|--------------------------------------------------------------------------
|
| Rate limited: 60 requests/minute per IP
| No authentication required
| CORS: zeplow.com, narrative.zeplow.com, logic.zeplow.com, localhost:3000-3002
|
*/
Route::prefix('sites/v1/{siteKey}')
    ->middleware(['throttle:60,1', 'site_key'])
    ->group(function () {

        // Read-only data endpoints
        Route::get('/config',            [SiteConfigController::class, 'show']);
        Route::get('/pages',             [SitePageController::class, 'index']);
        Route::get('/pages/{slug}',      [SitePageController::class, 'show']);
        Route::get('/projects',          [SiteProjectController::class, 'index']);
        Route::get('/projects/{slug}',   [SiteProjectController::class, 'show']);
        Route::get('/blog',              [SiteBlogController::class, 'index']);
        Route::get('/blog/{slug}',       [SiteBlogController::class, 'show']);
        Route::get('/testimonials',      [SiteTestimonialController::class, 'index']);
        Route::get('/team',              [SiteTeamController::class, 'index']);

        // Contact form (runtime POST from browser)
        Route::post('/contact',          [ContactController::class, 'store']);
    });

/*
|--------------------------------------------------------------------------
| Internal API Routes (CMS → API sync)
|--------------------------------------------------------------------------
|
| Authenticated via Bearer API key (ValidateApiKey middleware)
| Only the CMS app should call these
|
*/
Route::prefix('internal/v1')
    ->middleware('api_key:internal')
    ->group(function () {

        Route::post('/content/sync',              [ContentSyncController::class, 'sync']);
        Route::delete('/content/sync',            [ContentSyncController::class, 'delete']);
        Route::post('/content/sync-all',          [ContentSyncController::class, 'syncAll']);
        Route::post('/config/sync',               [ConfigSyncController::class, 'sync']);
        Route::post('/deploy/trigger/{siteKey}',  [DeployController::class, 'trigger']);
    });

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'index']);
```

---

## 8. CONTROLLER SPECIFICATIONS — PUBLIC ENDPOINTS

### 8.1 SiteConfigController

**File:** `app/Http/Controllers/Sites/SiteConfigController.php`

**GET `/sites/v1/{siteKey}/config`**

Returns the full configuration for a site: navigation, footer, CTA, social links.

**Response (200):**

```json
{
  "site_key": "narrative",
  "site_name": "Zeplow Narrative",
  "domain": "narrative.zeplow.com",
  "tagline": "Stories that sell.",
  "nav_items": [
    { "label": "About", "url": "/about", "is_external": false },
    { "label": "Services", "url": "/services", "is_external": false },
    { "label": "Work", "url": "/work", "is_external": false },
    { "label": "Process", "url": "/process", "is_external": false },
    { "label": "Insights", "url": "/insights", "is_external": false },
    { "label": "Contact", "url": "/contact", "is_external": false }
  ],
  "footer_links": [
    {
      "group_title": "The Zeplow Group",
      "links": [
        { "label": "Zeplow Narrative", "url": "https://narrative.zeplow.com" },
        { "label": "Zeplow Logic", "url": "https://logic.zeplow.com" },
        { "label": "Insights", "url": "/insights" }
      ]
    }
  ],
  "footer_text": "© 2026 Zeplow LTD. All rights reserved.",
  "cta_text": "Book a Heartbeat Review",
  "cta_url": "/contact",
  "social_links": {
    "linkedin": "https://linkedin.com/company/zeplow",
    "instagram": "https://instagram.com/zeplow",
    "whatsapp": "https://wa.me/8801XXXXXXXXX"
  },
  "contact_email": "hello@zeplow.com"
}
```

**Cache:** `site:{siteKey}:config` — 1 hour TTL.
**Cache-Control header:** `public, max-age=3600`
**Error:** 404 if site_key not found in site_configs table.

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteConfig;
use Illuminate\Support\Facades\Cache;

class SiteConfigController extends Controller
{
    public function show(string $siteKey)
    {
        $cacheKey = "site:{$siteKey}:config";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey) {
            $config = SiteConfig::where('site_key', $siteKey)->firstOrFail();

            return array_merge(
                ['site_key' => $config->site_key],
                $config->config
            );
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
```

---

### 8.2 SitePageController

**File:** `app/Http/Controllers/Sites/SitePageController.php`

#### GET `/sites/v1/{siteKey}/pages`

Returns all published pages for a site (list view — no content blocks).

**Response (200):**

```json
[
  {
    "id": 1,
    "slug": "home",
    "title": "Home",
    "template": "home",
    "seo": {
      "title": "Zeplow Narrative — Brand Storytelling Agency",
      "description": "We turn businesses into stories worth following.",
      "og_image": "https://cms.zeplow.com/storage/og/narrative-home.jpg"
    },
    "sort_order": 0,
    "published_at": "2026-03-15T00:00:00Z"
  },
  {
    "id": 2,
    "slug": "about",
    "title": "About",
    "template": "about",
    "seo": { "title": "...", "description": "...", "og_image": null },
    "sort_order": 1,
    "published_at": "2026-03-15T00:00:00Z"
  }
]
```

**Cache:** `site:{siteKey}:pages:v{version}:list` — 1 hour. Version counter ensures all list caches are invalidated atomically on content sync.

#### GET `/sites/v1/{siteKey}/pages/{slug}`

Returns a single page with full content blocks.

**Response (200):**

```json
{
  "id": 1,
  "slug": "home",
  "title": "Home",
  "template": "home",
  "content": [
    {
      "type": "hero",
      "data": {
        "heading": "We don't make ads. We make your business unforgettable.",
        "subheading": null,
        "cta_text": "Book a Heartbeat Review",
        "cta_url": "/contact",
        "background_color": "#034c3c"
      }
    },
    {
      "type": "text",
      "data": {
        "heading": "The Invisibility Tax",
        "body": "<p>Your product is great. But no one's telling the story...</p>"
      }
    },
    {
      "type": "projects",
      "data": {
        "heading": "Featured Work",
        "count": 3,
        "featured_only": true
      }
    },
    {
      "type": "cta",
      "data": {
        "heading": "If this feels like your kind of thinking, we should talk.",
        "description": null,
        "button_text": "Get in Touch",
        "button_url": "/contact",
        "style": "primary"
      }
    }
  ],
  "seo": {
    "title": "Zeplow Narrative — Brand Storytelling Agency",
    "description": "We turn businesses into stories worth following.",
    "og_image": "https://cms.zeplow.com/storage/og/narrative-home.jpg"
  },
  "published_at": "2026-03-15T00:00:00Z"
}
```

**Cache:** `site:{siteKey}:pages:{slug}` — 1 hour.
**Error:** 404 if page not found or not published.

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;

class SitePageController extends Controller
{
    public function index(string $siteKey)
    {
        $version  = Cache::get("site:{$siteKey}:pages:version", 1);
        $cacheKey = "site:{$siteKey}:pages:v{$version}:list";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey) {
            return SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'page')
                ->whereNotNull('published_at')
                ->orderBy('data->sort_order')
                ->get()
                ->map(function ($item) {
                    $data = $item->data;
                    return [
                        'id'           => $item->id,
                        'slug'         => $item->slug,
                        'title'        => $data['title'],
                        'template'     => $data['template'],
                        'seo'          => $data['seo'] ?? null,
                        'sort_order'   => $data['sort_order'] ?? 0,
                        'published_at' => $item->published_at,
                    ];
                });
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function show(string $siteKey, string $slug)
    {
        $cacheKey = "site:{$siteKey}:pages:{$slug}";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey, $slug) {
            $item = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'page')
                ->where('slug', $slug)
                ->whereNotNull('published_at')
                ->firstOrFail();

            return array_merge(
                ['id' => $item->id, 'slug' => $item->slug],
                $item->data,
                ['published_at' => $item->published_at]
            );
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
```

---

### 8.3 SiteProjectController

**File:** `app/Http/Controllers/Sites/SiteProjectController.php`

#### GET `/sites/v1/{siteKey}/projects`

**Query Parameters:**

| Parameter | Type | Default | Description |
|:---|:---|:---|:---|
| `featured` | boolean | false | Filter to featured projects only |
| `limit` | integer | 0 | If >0, returns simple array (no pagination meta). Used for homepage widgets. |
| `page` | integer | 1 | Page number for pagination |
| `per_page` | integer | 50 | Items per page (default 50) |

**Response with `?limit=3` (simple array, backwards compatible):**

```json
[
  {
    "id": 1,
    "slug": "tututor-ai",
    "title": "Tututor.ai",
    "one_liner": "An AI-powered tutoring platform that personalizes learning...",
    "client_name": "Tututor",
    "industry": "EdTech",
    "url": "https://tututor.ai",
    "images": [
      "https://cms.zeplow.com/storage/projects/tututor-1.jpg",
      "https://cms.zeplow.com/storage/projects/tututor-2.jpg"
    ],
    "tags": ["web-app", "ai", "saas"],
    "featured": true,
    "sort_order": 0
  }
]
```

**Response without `limit` (paginated):**

```json
{
  "data": [
    {
      "id": 1,
      "slug": "tututor-ai",
      "title": "Tututor.ai",
      "one_liner": "An AI-powered tutoring platform...",
      "client_name": "Tututor",
      "industry": "EdTech",
      "url": "https://tututor.ai",
      "images": ["..."],
      "tags": ["web-app", "ai", "saas"],
      "featured": true,
      "sort_order": 0
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 50,
    "total": 17
  }
}
```

**Cache:** `site:{siteKey}:projects:v{version}:list:{featured}:{limit}:{page}:{perPage}` — 1 hour. Version counter ensures all parameterized list caches are invalidated atomically on content sync.

#### GET `/sites/v1/{siteKey}/projects/{slug}`

Returns full project detail including challenge/solution/outcome and tech_stack.

**Response (200):**

```json
{
  "id": 1,
  "slug": "tututor-ai",
  "title": "Tututor.ai",
  "one_liner": "An AI-powered tutoring platform...",
  "client_name": "Tututor",
  "industry": "EdTech",
  "url": "https://tututor.ai",
  "challenge": "Manual tutoring couldn't scale...",
  "solution": "Built an AI-powered adaptive learning engine...",
  "outcome": "10x student engagement, 60% reduction in tutor workload",
  "tech_stack": ["Next.js", "Python", "PostgreSQL", "OpenAI"],
  "images": [
    "https://cms.zeplow.com/storage/projects/tututor-1.jpg",
    "https://cms.zeplow.com/storage/projects/tututor-2.jpg",
    "https://cms.zeplow.com/storage/projects/tututor-3.jpg"
  ],
  "tags": ["web-app", "ai", "saas"],
  "featured": true,
  "sort_order": 0,
  "published_at": "2026-03-15T00:00:00Z"
}
```

**Cache:** `site:{siteKey}:projects:{slug}` — 1 hour.

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteProjectController extends Controller
{
    public function index(Request $request, string $siteKey)
    {
        $featured = $request->boolean('featured', false);
        $limit    = $request->integer('limit', 0);
        $page     = $request->integer('page', 1);
        $perPage  = $request->integer('per_page', 50);

        $version  = Cache::get("site:{$siteKey}:projects:version", 1);
        $cacheKey = "site:{$siteKey}:projects:v{$version}:list:{$featured}:{$limit}:{$page}:{$perPage}";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey, $featured, $limit, $page, $perPage) {
            $query = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'project')
                ->whereNotNull('published_at');

            if ($featured) {
                $query->where('data->featured', true);
            }

            $query->orderBy('data->sort_order');

            // If limit is specified, return a simple limited list (backwards compatible)
            if ($limit > 0) {
                return $query->limit($limit)->get()->map(fn($item) => $this->formatListItem($item));
            }

            // Otherwise, paginate
            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => collect($paginated->items())->map(fn($item) => $this->formatListItem($item)),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ];
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function show(string $siteKey, string $slug)
    {
        $cacheKey = "site:{$siteKey}:projects:{$slug}";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey, $slug) {
            $item = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'project')
                ->where('slug', $slug)
                ->whereNotNull('published_at')
                ->firstOrFail();

            return array_merge(
                ['id' => $item->id, 'slug' => $item->slug],
                $item->data,
                ['published_at' => $item->published_at]
            );
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function formatListItem(SiteContent $item): array
    {
        $data = $item->data;
        return [
            'id'          => $item->id,
            'slug'        => $item->slug,
            'title'       => $data['title'],
            'one_liner'   => $data['one_liner'],
            'client_name' => $data['client_name'] ?? null,
            'industry'    => $data['industry'] ?? null,
            'url'         => $data['url'] ?? null,
            'images'      => $data['images'] ?? [],
            'tags'        => $data['tags'] ?? [],
            'featured'    => $data['featured'] ?? false,
            'sort_order'  => $data['sort_order'] ?? 0,
        ];
    }
}
```

---

### 8.4 SiteBlogController

**File:** `app/Http/Controllers/Sites/SiteBlogController.php`

#### GET `/sites/v1/{siteKey}/blog`

**Query Parameters:**

| Parameter | Type | Default | Description |
|:---|:---|:---|:---|
| `tag` | string | "" | Filter by tag (e.g., `?tag=branding`) |
| `limit` | integer | 0 | If >0, returns simple array (no pagination meta) |
| `page` | integer | 1 | Page number for pagination |
| `per_page` | integer | 20 | Items per page (default 20) |

**Response with `?limit=10` (simple array):**

```json
[
  {
    "id": 1,
    "slug": "why-your-brand-feels-forgettable",
    "title": "Why Your Brand Feels Forgettable",
    "excerpt": "Most brands fail not because they lack quality...",
    "cover_image": "https://cms.zeplow.com/storage/blog/cover-1.jpg",
    "tags": ["branding", "strategy"],
    "author": "Shadman Sakib",
    "published_at": "2026-03-20T00:00:00Z"
  }
]
```

**Response without `limit` (paginated):**

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

**Cache:** `site:{siteKey}:blog:v{version}:list:{tag}:{limit}:{page}:{perPage}` — 1 hour. Version counter ensures all parameterized list caches are invalidated atomically on content sync.
**Sort order:** `published_at` descending (newest first).

#### GET `/sites/v1/{siteKey}/blog/{slug}`

Returns full blog post with body HTML and SEO.

**Response (200):**

```json
{
  "id": 1,
  "slug": "why-your-brand-feels-forgettable",
  "title": "Why Your Brand Feels Forgettable",
  "excerpt": "Most brands fail not because they lack quality...",
  "body": "<h2>The Invisibility Tax</h2><p>Every day your brand goes unnoticed...</p>",
  "cover_image": "https://cms.zeplow.com/storage/blog/cover-1.jpg",
  "tags": ["branding", "strategy"],
  "author": "Shadman Sakib",
  "seo": {
    "title": "Why Your Brand Feels Forgettable — Zeplow Narrative",
    "description": "Most brands fail not because they lack quality..."
  },
  "published_at": "2026-03-20T00:00:00Z"
}
```

**Cache:** `site:{siteKey}:blog:{slug}` — 1 hour.

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteBlogController extends Controller
{
    public function index(Request $request, string $siteKey)
    {
        $tag     = $request->string('tag', '');
        $limit   = $request->integer('limit', 0);
        $page    = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 20);

        $version  = Cache::get("site:{$siteKey}:blog:version", 1);
        $cacheKey = "site:{$siteKey}:blog:v{$version}:list:{$tag}:{$limit}:{$page}:{$perPage}";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey, $tag, $limit, $page, $perPage) {
            $query = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'blog_post')
                ->whereNotNull('published_at')
                ->orderByDesc('published_at');

            if ($tag->isNotEmpty()) {
                $query->whereJsonContains('data->tags', $tag->toString());
            }

            if ($limit > 0) {
                return $query->limit($limit)->get()->map(fn($item) => $this->formatListItem($item));
            }

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => collect($paginated->items())->map(fn($item) => $this->formatListItem($item)),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ];
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function show(string $siteKey, string $slug)
    {
        $cacheKey = "site:{$siteKey}:blog:{$slug}";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey, $slug) {
            $item = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'blog_post')
                ->where('slug', $slug)
                ->whereNotNull('published_at')
                ->firstOrFail();

            return array_merge(
                ['id' => $item->id, 'slug' => $item->slug],
                $item->data,
                ['published_at' => $item->published_at]
            );
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function formatListItem(SiteContent $item): array
    {
        $data = $item->data;
        return [
            'id'           => $item->id,
            'slug'         => $item->slug,
            'title'        => $data['title'],
            'excerpt'      => $data['excerpt'] ?? null,
            'cover_image'  => $data['cover_image'] ?? null,
            'tags'         => $data['tags'] ?? [],
            'author'       => $data['author'] ?? null,
            'published_at' => $item->published_at,
        ];
    }
}
```

---

### 8.5 SiteTestimonialController

**File:** `app/Http/Controllers/Sites/SiteTestimonialController.php`

#### GET `/sites/v1/{siteKey}/testimonials`

**Response (200):**

```json
[
  {
    "id": 1,
    "name": "John Doe",
    "role": "CEO",
    "company": "Example Corp",
    "quote": "Our business finally feels under control.",
    "avatar": "https://cms.zeplow.com/storage/testimonials/john.jpg",
    "sort_order": 0
  }
]
```

**Cache:** `site:{siteKey}:testimonials:v{version}:list` — 1 hour. Version counter ensures list cache is invalidated atomically on content sync.

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;

class SiteTestimonialController extends Controller
{
    public function index(string $siteKey)
    {
        $version  = Cache::get("site:{$siteKey}:testimonials:version", 1);
        $cacheKey = "site:{$siteKey}:testimonials:v{$version}:list";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey) {
            return SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'testimonial')
                ->whereNotNull('published_at')
                ->orderBy('data->sort_order')
                ->get()
                ->map(function ($item) {
                    $data = $item->data;
                    return [
                        'id'         => $item->id,
                        'name'       => $data['name'],
                        'role'       => $data['role'] ?? null,
                        'company'    => $data['company'] ?? null,
                        'quote'      => $data['quote'],
                        'avatar'     => $data['avatar'] ?? null,
                        'sort_order' => $data['sort_order'] ?? 0,
                    ];
                });
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
```

---

### 8.6 SiteTeamController

**File:** `app/Http/Controllers/Sites/SiteTeamController.php`

#### GET `/sites/v1/{siteKey}/team`

**Response (200):**

```json
[
  {
    "id": 1,
    "name": "Shadman Sakib",
    "role": "Co-Founder & CEO",
    "bio": "Strategy, direction, and brand & venture leadership.",
    "photo": "https://cms.zeplow.com/storage/team/shadman.jpg",
    "linkedin": "https://linkedin.com/in/shadmansakib",
    "email": "shadman@zeplow.com",
    "is_founder": true,
    "sort_order": 0
  },
  {
    "id": 2,
    "name": "Shakib Bin Kabir",
    "role": "Co-Founder & CTO",
    "bio": "Systems, automation, AI & technical architecture.",
    "photo": "https://cms.zeplow.com/storage/team/shakib.jpg",
    "linkedin": "https://linkedin.com/in/shakibbinkabir",
    "email": "shakib@zeplow.com",
    "is_founder": true,
    "sort_order": 1
  }
]
```

**Cache:** `site:{siteKey}:team:v{version}:list` — 1 hour. Version counter ensures list cache is invalidated atomically on content sync.

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;

class SiteTeamController extends Controller
{
    public function index(string $siteKey)
    {
        $version  = Cache::get("site:{$siteKey}:team:version", 1);
        $cacheKey = "site:{$siteKey}:team:v{$version}:list";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey) {
            return SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'team_member')
                ->orderBy('data->sort_order')
                ->get()
                ->map(function ($item) {
                    $data = $item->data;
                    return [
                        'id'         => $item->id,
                        'name'       => $data['name'],
                        'role'       => $data['role'],
                        'bio'        => $data['bio'] ?? null,
                        'photo'      => $data['photo'] ?? null,
                        'linkedin'   => $data['linkedin'] ?? null,
                        'email'      => $data['email'] ?? null,
                        'is_founder' => $data['is_founder'] ?? false,
                        'sort_order' => $data['sort_order'] ?? 0,
                    ];
                });
        });

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
```

**Note:** Team members do NOT filter by `published_at` — all team members are returned regardless. The CMS controls visibility by deciding what to sync.

---

### 8.7 ContactController

**File:** `app/Http/Controllers/Sites/ContactController.php`

#### POST `/sites/v1/{siteKey}/contact`

**Request Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "company": "Example Corp",
  "message": "I need help with...",
  "budget_range": "$3,000 - $5,000",
  "source": "narrative.zeplow.com"
}
```

**Validation Rules:**

| Field | Rules |
|:---|:---|
| `name` | required, string, max:255 |
| `email` | required, email, max:255 |
| `company` | nullable, string, max:255 |
| `message` | required, string, max:5000 |
| `budget_range` | nullable, string, max:100 |
| `source` | nullable, string, max:255 |

**Spam Protection:** Hidden honeypot field (`fax_number`). The field name is chosen to sound like a real form field to trick bots, but it is not part of the actual form. If the hidden field is filled by a bot, the API returns a fake success response without storing or emailing. The response is identical to a real success — never alert the bot.

**Success Response (200):**

```json
{
  "status": "received",
  "message": "Thank you. We'll be in touch within 24 hours."
}
```

**Validation Error Response (422):**

```json
{
  "error": "Validation failed",
  "fields": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."]
  }
}
```

**Behavior:**
1. Check honeypot → if filled, return fake 200
2. Validate input → return 422 on failure
3. Store in `contact_submissions` table
4. Send email notification to `hello@zeplow.com` via SMTP
5. If email fails, log error but still return 200 (submission is stored — email is secondary)
6. Return success response

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request, string $siteKey)
    {
        // Honeypot check — reject silently if filled
        if ($request->filled('fax_number')) {
            return response()->json([
                'status'  => 'received',
                'message' => 'Thank you. We\'ll be in touch within 24 hours.',
            ]);
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'company'      => 'nullable|string|max:255',
            'message'      => 'required|string|max:5000',
            'budget_range' => 'nullable|string|max:100',
            'source'       => 'nullable|string|max:255',
        ]);

        $submission = ContactSubmission::create(array_merge(
            $validated,
            ['site_key' => $siteKey]
        ));

        // Send email notification
        try {
            Mail::raw(
                "New contact submission from {$validated['name']} ({$validated['email']})\n\n" .
                "Company: " . ($validated['company'] ?? 'N/A') . "\n" .
                "Budget: " . ($validated['budget_range'] ?? 'N/A') . "\n" .
                "Source: " . ($validated['source'] ?? 'N/A') . "\n\n" .
                "Message:\n{$validated['message']}",
                function ($message) use ($siteKey) {
                    $message->to('hello@zeplow.com')
                        ->subject("New Lead — {$siteKey}");
                }
            );
        } catch (\Exception $e) {
            Log::error('Contact email notification failed', [
                'submission_id' => $submission->id,
                'error'         => $e->getMessage(),
            ]);
            // Don't fail the response — submission is stored, email is secondary
        }

        return response()->json([
            'status'  => 'received',
            'message' => 'Thank you. We\'ll be in touch within 24 hours.',
        ]);
    }
}
```

---

## 9. CONTROLLER SPECIFICATIONS — INTERNAL ENDPOINTS

All internal endpoints require the `api_key:internal` middleware (Bearer token in Authorization header).

### 9.1 ContentSyncController

**File:** `app/Http/Controllers/Internal/ContentSyncController.php`

This is the most critical controller in the API. It receives content from the CMS, stores it, invalidates caches, and triggers deploys.

#### POST `/internal/v1/content/sync`

**Request Body (from CMS):**

```json
{
  "site_key": "narrative",
  "content_type": "page",
  "slug": "about",
  "data": {
    "title": "About Us",
    "template": "about",
    "content": [...],
    "seo": {...},
    "sort_order": 1
  },
  "published_at": "2026-03-15T00:00:00Z"
}
```

**Validation Rules:**

| Field | Rules |
|:---|:---|
| `site_key` | required, string, max:50 |
| `content_type` | required, string, max:50 |
| `slug` | required, string, max:255 |
| `data` | required, array |
| `published_at` | nullable, date |

**Behavior:**
1. Validate payload
2. Upsert into `site_content` table (unique on site_key + content_type + slug)
3. Invalidate relevant cache keys using `TYPE_TO_CACHE_PREFIX` mapping
4. Trigger Cloudflare deploy hook for the site
5. Return success response

**Success Response (200):**

```json
{
  "status": "synced",
  "site_key": "narrative",
  "content_type": "page",
  "slug": "about"
}
```

#### DELETE `/internal/v1/content/sync`

**Request Body:**

```json
{
  "site_key": "narrative",
  "content_type": "project",
  "slug": "old-project"
}
```

**Behavior:**
1. Delete matching record from `site_content`
2. Invalidate relevant cache keys
3. Trigger deploy hook
4. Return success response

**Success Response (200):**

```json
{
  "status": "deleted"
}
```

#### Critical: TYPE_TO_CACHE_PREFIX Mapping

The CMS sends content types like `blog_post` and `team_member`, but the public API endpoints use `/blog` and `/team`. The cache keys must match the public endpoint prefixes.

```php
private const TYPE_TO_CACHE_PREFIX = [
    'page'        => 'pages',
    'project'     => 'projects',
    'blog_post'   => 'blog',
    'testimonial' => 'testimonials',
    'team_member' => 'team',
];
```

If a content_type is not in the map, the fallback is `{content_type}s` (append "s").

**Full Implementation:**

```php
<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\DeployService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentSyncController extends Controller
{
    /**
     * Map content_type from CMS to the cache prefix used by public controllers.
     * CMS sends "blog_post", but the public endpoint is /blog, so cache key uses "blog".
     */
    private const TYPE_TO_CACHE_PREFIX = [
        'page'        => 'pages',
        'project'     => 'projects',
        'blog_post'   => 'blog',
        'testimonial' => 'testimonials',
        'team_member' => 'team',
    ];

    public function __construct(private DeployService $deployService) {}

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'site_key'     => 'required|string|max:50',
            'content_type' => 'required|string|max:50',
            'slug'         => 'required|string|max:255',
            'data'         => 'required|array',
            'published_at' => 'nullable|date',
        ]);

        // Upsert content
        SiteContent::updateOrCreate(
            [
                'site_key'     => $validated['site_key'],
                'content_type' => $validated['content_type'],
                'slug'         => $validated['slug'],
            ],
            [
                'data'         => $validated['data'],
                'published_at' => $validated['published_at'],
                'synced_at'    => now(),
            ]
        );

        // Invalidate caches using version counter strategy
        $siteKey     = $validated['site_key'];
        $type        = $validated['content_type'];
        $slug        = $validated['slug'];
        $cachePrefix = self::TYPE_TO_CACHE_PREFIX[$type] ?? $type . 's';

        // Increment version counter — all old list cache keys (including parameterized
        // variants like list:true:3:1:50) become orphaned and expire naturally after TTL
        Cache::increment("site:{$siteKey}:{$cachePrefix}:version");
        // Detail cache uses a fixed key, so direct forget is safe
        Cache::forget("site:{$siteKey}:{$cachePrefix}:{$slug}");

        // Trigger deploy
        $this->deployService->trigger($siteKey, 'content_sync');

        return response()->json([
            'status'       => 'synced',
            'site_key'     => $siteKey,
            'content_type' => $type,
            'slug'         => $slug,
        ]);
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'site_key'     => 'required|string',
            'content_type' => 'required|string',
            'slug'         => 'required|string',
        ]);

        SiteContent::where('site_key', $validated['site_key'])
            ->where('content_type', $validated['content_type'])
            ->where('slug', $validated['slug'])
            ->delete();

        $cachePrefix = self::TYPE_TO_CACHE_PREFIX[$validated['content_type']]
            ?? $validated['content_type'] . 's';

        Cache::increment("site:{$validated['site_key']}:{$cachePrefix}:version");
        Cache::forget("site:{$validated['site_key']}:{$cachePrefix}:{$validated['slug']}");

        $this->deployService->trigger($validated['site_key'], 'content_delete');

        return response()->json(['status' => 'deleted']);
    }

    public function syncAll(Request $request)
    {
        $validated = $request->validate([
            'site_key' => 'required|string|max:50',
        ]);

        // Increment version counters for ALL content types (invalidates all list caches)
        $siteKey = $validated['site_key'];
        foreach (self::TYPE_TO_CACHE_PREFIX as $type => $prefix) {
            Cache::increment("site:{$siteKey}:{$prefix}:version");
        }
        Cache::forget("site:{$siteKey}:config");

        // Trigger deploy
        $this->deployService->trigger($siteKey, 'full_resync');

        return response()->json([
            'status'   => 'cache_cleared',
            'site_key' => $siteKey,
        ]);
    }
}
```

---

### 9.2 ConfigSyncController

**File:** `app/Http/Controllers/Internal/ConfigSyncController.php`

#### POST `/internal/v1/config/sync`

**Request Body:**

```json
{
  "site_key": "narrative",
  "config": {
    "site_name": "Zeplow Narrative",
    "domain": "narrative.zeplow.com",
    "tagline": "Stories that sell.",
    "nav_items": [...],
    "footer_links": [...],
    "footer_text": "© 2026 Zeplow LTD. All rights reserved.",
    "cta_text": "Book a Heartbeat Review",
    "cta_url": "/contact",
    "social_links": {...},
    "contact_email": "hello@zeplow.com"
  }
}
```

**Implementation:**

```php
<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\SiteConfig;
use App\Services\DeployService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigSyncController extends Controller
{
    public function __construct(private DeployService $deployService) {}

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'site_key' => 'required|string|max:50',
            'config'   => 'required|array',
        ]);

        SiteConfig::updateOrCreate(
            ['site_key' => $validated['site_key']],
            [
                'config'    => $validated['config'],
                'synced_at' => now(),
            ]
        );

        // Clear config cache
        Cache::forget("site:{$validated['site_key']}:config");

        // Trigger deploy
        $this->deployService->trigger($validated['site_key'], 'config_sync');

        return response()->json([
            'status'   => 'synced',
            'site_key' => $validated['site_key'],
        ]);
    }
}
```

---

### 9.3 DeployController

**File:** `app/Http/Controllers/Internal/DeployController.php`

#### POST `/internal/v1/deploy/trigger/{siteKey}`

Manually triggers a Cloudflare Pages deploy for a given site. Used for manual recovery.

```php
<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\DeployService;

class DeployController extends Controller
{
    public function __construct(private DeployService $deployService) {}

    public function trigger(string $siteKey)
    {
        $success = $this->deployService->trigger($siteKey, 'manual');

        return response()->json([
            'status'   => $success ? 'triggered' : 'failed',
            'site_key' => $siteKey,
        ], $success ? 200 : 500);
    }
}
```

---

## 10. CONTROLLER SPECIFICATIONS — HEALTH CHECK

**File:** `app/Http/Controllers/HealthController.php`

#### GET `/health`

**Response (200):**

```json
{
  "status": "ok",
  "timestamp": "2026-03-11T14:30:00Z",
  "database": "connected",
  "version": "1.0.0"
}
```

**No cache.** Used for uptime monitoring.

**Implementation:**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index()
    {
        $dbStatus = 'disconnected';

        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Exception $e) {
            // Database connection failed — still return 200
            // but indicate db is disconnected
        }

        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toISOString(),
            'database'  => $dbStatus,
            'version'   => '1.0.0',
        ]);
    }
}
```

---

## 11. DEPLOY HOOK SERVICE

**File:** `app/Services/DeployService.php`

This service fires Cloudflare Pages deploy hooks. It is called by both content sync controllers and the manual deploy controller.

**Configuration:** Deploy hook URLs are stored in `.env` and loaded via `config/services.php`.

```php
// config/services.php (add this section)
'cloudflare' => [
    'deploy_hooks' => [
        'parent'    => env('CF_DEPLOY_HOOK_PARENT'),
        'narrative' => env('CF_DEPLOY_HOOK_NARRATIVE'),
        'logic'     => env('CF_DEPLOY_HOOK_LOGIC'),
    ],
],
```

**Implementation:**

```php
<?php

namespace App\Services;

use App\Models\DeployLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeployService
{
    public function trigger(string $siteKey, string $triggerSource = 'content_sync'): bool
    {
        $hookUrl = config("services.cloudflare.deploy_hooks.{$siteKey}");

        if (!$hookUrl) {
            Log::warning("No deploy hook configured for site: {$siteKey}");
            return false;
        }

        $log = DeployLog::create([
            'site_key'       => $siteKey,
            'trigger_source' => $triggerSource,
            'status'         => 'triggered',
        ]);

        try {
            $response = Http::timeout(10)->post($hookUrl);

            $log->update([
                'status'        => $response->successful() ? 'success' : 'failed',
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 500),
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            $log->update([
                'status'     => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            Log::error("Deploy hook failed for {$siteKey}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
```

---

## 12. CACHE STRATEGY & INVALIDATION

### 12.1 Cache Driver

File-based cache (cPanel compatible, no Redis needed).

```php
// config/cache.php
'default' => 'file',
'prefix'  => 'zeplow_api',
```

### 12.2 Cache Key Naming Convention

All cache keys follow the pattern: `site:{siteKey}:{contentPrefix}:{identifier}`

List cache keys include a version counter (`v{version}`) to enable atomic invalidation of all parameterized variants. When content is synced, the version counter is incremented — old cache keys become orphaned and expire naturally after TTL. This approach is compatible with Laravel's file-based cache driver (no Redis or tag support required).

| Cache Key Pattern | TTL | Endpoint | Invalidated By |
|:---|:---|:---|:---|
| `site:{siteKey}:config` | 1 hour | GET /config | Config sync (direct forget) |
| `site:{siteKey}:{prefix}:version` | Forever | — | Incremented on content sync/delete |
| `site:{siteKey}:pages:v{version}:list` | 1 hour | GET /pages | Page sync/delete (version increment) |
| `site:{siteKey}:pages:{slug}` | 1 hour | GET /pages/{slug} | Page sync/delete (direct forget) |
| `site:{siteKey}:projects:v{version}:list:{featured}:{limit}:{page}:{perPage}` | 1 hour | GET /projects | Project sync/delete (version increment) |
| `site:{siteKey}:projects:{slug}` | 1 hour | GET /projects/{slug} | Project sync/delete (direct forget) |
| `site:{siteKey}:blog:v{version}:list:{tag}:{limit}:{page}:{perPage}` | 1 hour | GET /blog | Blog post sync/delete (version increment) |
| `site:{siteKey}:blog:{slug}` | 1 hour | GET /blog/{slug} | Blog post sync/delete (direct forget) |
| `site:{siteKey}:testimonials:v{version}:list` | 1 hour | GET /testimonials | Testimonial sync/delete (version increment) |
| `site:{siteKey}:team:v{version}:list` | 1 hour | GET /team | Team member sync/delete (version increment) |

### 12.3 TYPE_TO_CACHE_PREFIX Mapping

This is critical for correct cache invalidation. The CMS uses content_type names that differ from the public API URL paths:

| CMS content_type | Public URL prefix | Cache prefix |
|:---|:---|:---|
| `page` | `/pages` | `pages` |
| `project` | `/projects` | `projects` |
| `blog_post` | `/blog` | `blog` |
| `testimonial` | `/testimonials` | `testimonials` |
| `team_member` | `/team` | `team` |

Without this mapping, a blog post sync would try to invalidate `site:narrative:blog_posts:list` instead of the correct `site:narrative:blog:list`.

### 12.4 Cache Invalidation Rules

On content sync (create/update):
1. Increment the version counter: `Cache::increment("site:{siteKey}:{prefix}:version")` — this atomically invalidates ALL list cache keys for that content type, including every parameterized variant (e.g., `list:true:3:1:50`). Old cache entries become orphaned and expire naturally after their 1-hour TTL.
2. Clear the detail cache: `Cache::forget("site:{siteKey}:{prefix}:{slug}")`

On content delete:
1. Same as sync (version counter increment + detail cache forget)

On config sync:
1. Clear config cache: `Cache::forget("site:{siteKey}:config")`

On full resync:
1. Increment version counters for ALL content types for the site
2. Clear config cache

**Why version counters instead of direct cache clearing:** The deploy hook triggers an immediate Cloudflare Pages rebuild, which fetches content from the API within seconds of a sync. If parameterized list cache keys (e.g., `projects:list:true:3:1:50`) are not invalidated, the rebuild will fetch stale data. The version counter approach ensures that every list cache variant is effectively invalidated the moment content changes, without needing tag-aware cache drivers or filesystem iteration. It is fully compatible with Laravel's file-based cache driver on cPanel shared hosting.

### 12.5 HTTP Cache Headers

All public GET endpoints return:

```
Cache-Control: public, max-age=3600
```

This allows Cloudflare's proxy (orange cloud on `api.zeplow.com`) to cache API responses at the edge, further reducing load on the shared hosting server.

---

## 13. CORS CONFIGURATION

**File:** `config/cors.php`

```php
<?php

return [
    'paths' => ['sites/*', 'health'],

    'allowed_methods' => ['GET', 'POST'],

    'allowed_origins' => [
        'https://zeplow.com',
        'https://narrative.zeplow.com',
        'https://logic.zeplow.com',
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:3002',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
```

**Notes:**
- Only public endpoints (`sites/*` and `health`) have CORS headers. Internal endpoints don't need them (server-to-server).
- Only GET and POST are allowed (no PUT, PATCH, DELETE from browsers).
- Localhost ports match the dev servers for each Next.js app (3000 = parent, 3001 = narrative, 3002 = logic).
- `max_age: 86400` (24 hours) means browsers cache the preflight response for a day.

---

## 14. RATE LIMITING

Public endpoints are rate-limited to prevent abuse:

| Endpoint Group | Limit | Window |
|:---|:---|:---|
| Public API (`/sites/v1/*`) | 60 requests | 1 minute per IP |
| Internal API (`/internal/v1/*`) | No limit | N/A (authenticated) |
| Health check (`/health`) | No limit | N/A |

**Configuration:** Uses Laravel's built-in `throttle` middleware applied in the route definition (see Section 7.2).

**Response when rate limit exceeded (429):**

```json
{
  "error": "Too many requests"
}
```

**Note:** During a Next.js build, each site makes 15-30 API calls. With 60 requests/minute per IP, a single build will never hit the limit. Even if all 3 sites build simultaneously from the same Cloudflare IP, the 60/min limit should be sufficient. If issues arise, increase to 120/min.

**Rate Limit Monitoring:** Log all 429 responses to `laravel.log` with the client IP. If Cloudflare Pages build IPs are consistently hitting rate limits, increase the limit to 120/min or whitelist known Cloudflare builder IP ranges.

---

## 15. ERROR HANDLING & EXCEPTION HANDLER

### 15.1 Error Response Strategy

This app ALWAYS returns JSON. No HTML error pages, ever.

| Scenario | HTTP Code | Response Body |
|:---|:---|:---|
| Missing API key on internal endpoint | 401 | `{"error": "Missing API key"}` |
| Invalid/inactive API key | 403 | `{"error": "Invalid API key"}` |
| Content not found (public endpoint) | 404 | `{"error": "Not found"}` |
| Unknown site_key in URL | 404 | `{"error": "Not found"}` | ValidateSiteKey middleware |
| Invalid site_key (no matching records) | 404 | `{"error": "Not found"}` |
| Validation error on sync | 422 | `{"error": "Validation failed", "fields": {...}}` |
| Validation error on contact form | 422 | `{"error": "Validation failed", "fields": {...}}` |
| Rate limit exceeded | 429 | `{"error": "Too many requests"}` |
| Deploy hook fails | — | Logged internally. Content is still stored. No user-facing error. |
| Database connection failure | 500 | `{"error": "Internal server error"}` (logged) |
| Any unhandled exception | 500 | `{"error": "Internal server error"}` (logged, no stack trace exposed) |
| Honeypot triggered on contact form | 200 | Fake success (never reveal detection) |

### 15.2 Exception Handler

**File:** `app/Exceptions/Handler.php`

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // Always return JSON (this is an API-only app)

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'error'  => 'Validation failed',
                'fields' => $e->errors(),
            ], 422);
        }

        // Log unexpected errors
        \Illuminate\Support\Facades\Log::error('Unhandled exception', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json(['error' => 'Internal server error'], 500);
    }
}
```

---

## 16. EMAIL NOTIFICATIONS

### 16.1 When Emails Are Sent

The only email the API sends is a contact form notification to `hello@zeplow.com`.

### 16.2 Email Configuration

SMTP via cPanel's mail server:

```env
MAIL_MAILER=smtp
MAIL_HOST=... (cPanel SMTP host)
MAIL_PORT=465
MAIL_USERNAME=hello@zeplow.com
MAIL_PASSWORD=... (email password)
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=hello@zeplow.com
MAIL_FROM_NAME="Zeplow"
```

### 16.3 Email Content

Plain-text email (no HTML template needed):

```
Subject: New Lead — narrative

New contact submission from John Doe (john@example.com)

Company: Example Corp
Budget: $3,000 - $5,000
Source: narrative.zeplow.com

Message:
I need help with rebranding our company...
```

### 16.4 Email Failure Handling

Email failure does NOT fail the contact form response. The submission is always stored in the database. Email failure is logged but the user sees a success message regardless.

---

## 17. LOGGING STRATEGY

### 17.1 Log Driver

Laravel default file logging: `storage/logs/laravel.log`

### 17.2 What Gets Logged

| Event | Log Level | Location |
|:---|:---|:---|
| Contact email notification failure | ERROR | `laravel.log` |
| Deploy hook failure | ERROR | `laravel.log` + `deploy_logs` table |
| Deploy hook success | — | `deploy_logs` table only |
| Unhandled exception | ERROR | `laravel.log` |
| Rate limit exceeded (429) | WARNING | `laravel.log` (with client IP) |
| Missing deploy hook config | WARNING | `laravel.log` |

### 17.3 Database Logging Tables

| Table | Purpose | Key Fields |
|:---|:---|:---|
| `deploy_logs` | Every deploy hook trigger | site_key, trigger_source, status, response_code, last_error |
| `contact_submissions` | Every valid form submission | site_key, name, email, message, is_read |

---

## 18. SECURITY HEADERS & FRONTEND SECURITY

### 18.1 API-Level Security

| Concern | Implementation |
|:---|:---|
| HTTPS | Enforced by Cloudflare (api.zeplow.com proxied through orange cloud) |
| Internal endpoint auth | Bearer API key hashed with SHA-256 and validated against `api_keys` table |
| Public endpoint protection | Rate limiting (60/min per IP) |
| Spam protection | Honeypot field on contact form |
| CORS | Restricted to known frontend domains + localhost |
| XSS in blog content | Blog body HTML should be sanitized at CMS level before sync. API stores and serves as-is. |
| SQL injection | Protected by Eloquent ORM (parameterized queries) |
| Mass assignment | Protected by `$fillable` on all models |

### 18.2 API Key Security Rules

- API key is a 64-character random string — treat as a secret
- API key is stored as a **SHA-256 hash** in the database. The plaintext key is shown once at generation and cannot be retrieved.
- Never commit the plaintext key to Git
- Store the plaintext key in `.env` on both CMS and API servers
- On each request, the incoming bearer token is hashed with `hash('sha256', $token)` before querying the database — the plaintext key never touches the database
- If compromised, deactivate in `api_keys` table (`is_active = false`) and generate a new one
- `last_used_at` is tracked on every authenticated request for auditing

---

## 19. ENVIRONMENT VARIABLES

### 19.1 Complete .env File

```env
APP_NAME="Zeplow API"
APP_ENV=production
APP_KEY=base64:... (generate via php artisan key:generate)
APP_DEBUG=false
APP_URL=https://api.zeplow.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=api_zeplow
DB_USERNAME=api_user
DB_PASSWORD=... (strong password, different from CMS)

# Cache & Queue
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=array

# Cloudflare Deploy Hooks
CF_DEPLOY_HOOK_PARENT=https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/...
CF_DEPLOY_HOOK_NARRATIVE=https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/...
CF_DEPLOY_HOOK_LOGIC=https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/...

# Email (for contact form notifications)
MAIL_MAILER=smtp
MAIL_HOST=... (cPanel SMTP host)
MAIL_PORT=465
MAIL_USERNAME=hello@zeplow.com
MAIL_PASSWORD=... (email password)
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=hello@zeplow.com
MAIL_FROM_NAME="Zeplow"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 19.2 Config Files to Create/Modify

| File | What to Add |
|:---|:---|
| `config/services.php` | `cloudflare.deploy_hooks` array (parent, narrative, logic) |
| `config/cache.php` | File driver with `zeplow_api` prefix |
| `config/cors.php` | Allowed origins for all 3 sites + localhost |

---

## 20. DNS & SSL

### 20.1 DNS Record

| Type | Name | Content | Proxy |
|:---|:---|:---|:---|
| A | `api` | cPanel server IP | **Yes (orange cloud)** |

### 20.2 SSL

| Domain | SSL Provider | Notes |
|:---|:---|:---|
| api.zeplow.com | Cloudflare (auto, proxied) | Universal SSL, free |

### 20.3 Why Orange Cloud Matters for the API

Proxying `api.zeplow.com` through Cloudflare (orange cloud) provides:

1. **Free SSL** — Cloudflare handles HTTPS termination
2. **DDoS protection** — Cloudflare absorbs attacks
3. **Edge caching** — API responses with `Cache-Control: public` headers are cached at Cloudflare's 330+ edge nodes, reducing load on shared hosting
4. **IP hiding** — The cPanel server's real IP is not exposed

---

## 21. PERFORMANCE REQUIREMENTS

### 21.1 Target Metrics

| Metric | Target |
|:---|:---|
| API response time (cached) | < 50ms |
| API response time (uncached) | < 200ms |
| Health check response | < 20ms |
| Deploy hook trigger time | < 2 seconds |
| Contact form submission | < 1 second |
| API calls during one site build | 15–30 requests |
| Total build time per site | 60–120 seconds (including API calls) |

### 21.2 Performance Rules

| Rule | Implementation |
|:---|:---|
| Strip unnecessary service providers | No sessions, no views, no broadcasting |
| Cache all read responses | 1-hour file-based cache on all public GET endpoints |
| Minimal packages | Only Sanctum + Guzzle (both included by default) |
| No N+1 queries | All queries are direct (no Eloquent relationships loaded in API app) |
| Lean JSON responses | Only return fields the frontend needs, no excess data |
| HTTP Cache-Control headers | `public, max-age=3600` on all public GETs (Cloudflare edge caching) |

---

## 22. CLOUDFLARE INTEGRATION

### 22.1 Deploy Hooks

| Site | .env Variable | Purpose |
|:---|:---|:---|
| Parent | `CF_DEPLOY_HOOK_PARENT` | Triggers rebuild of zeplow.com |
| Narrative | `CF_DEPLOY_HOOK_NARRATIVE` | Triggers rebuild of narrative.zeplow.com |
| Logic | `CF_DEPLOY_HOOK_LOGIC` | Triggers rebuild of logic.zeplow.com |

**How to get deploy hook URLs:** Cloudflare Pages dashboard → project → Settings → Builds & deployments → Deploy hooks → Create hook.

### 22.2 Edge Caching

Because `api.zeplow.com` is proxied through Cloudflare (orange cloud), and all public GET responses include `Cache-Control: public, max-age=3600`, Cloudflare will cache these responses at its edge nodes. This means:

- First request for `GET /sites/v1/narrative/pages` hits the Laravel server
- Second request (from any location) is served directly from Cloudflare's CDN
- Cache expires after 1 hour (matching Laravel's internal cache TTL)
- During a Next.js build, most API calls will be edge-cached (fast)

---

## 23. DIRECTORY STRUCTURE

```
api-app/
├── app/
│   ├── Exceptions/
│   │   └── Handler.php                    # JSON-only exception handler
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php             # Base controller
│   │   │   ├── HealthController.php       # GET /health
│   │   │   ├── Internal/
│   │   │   │   ├── ContentSyncController.php   # POST/DELETE /internal/v1/content/sync
│   │   │   │   ├── ConfigSyncController.php    # POST /internal/v1/config/sync
│   │   │   │   └── DeployController.php        # POST /internal/v1/deploy/trigger/{siteKey}
│   │   │   └── Sites/
│   │   │       ├── SiteConfigController.php      # GET /sites/v1/{siteKey}/config
│   │   │       ├── SitePageController.php        # GET /sites/v1/{siteKey}/pages[/{slug}]
│   │   │       ├── SiteProjectController.php     # GET /sites/v1/{siteKey}/projects[/{slug}]
│   │   │       ├── SiteBlogController.php        # GET /sites/v1/{siteKey}/blog[/{slug}]
│   │   │       ├── SiteTestimonialController.php # GET /sites/v1/{siteKey}/testimonials
│   │   │       ├── SiteTeamController.php        # GET /sites/v1/{siteKey}/team
│   │   │       └── ContactController.php         # POST /sites/v1/{siteKey}/contact
│   │   ├── Middleware/
│   │   │   ├── ValidateApiKey.php         # Bearer token validation for internal endpoints
│   │   │   └── ValidateSiteKey.php        # Site key whitelist validation for public endpoints
│   │   └── Kernel.php                     # Middleware registration
│   ├── Models/
│   │   ├── SiteContent.php                # Core content model (flat JSON store)
│   │   ├── SiteConfig.php                 # Site configuration model
│   │   ├── ContactSubmission.php          # Contact form submissions
│   │   ├── DeployLog.php                  # Deploy hook trigger logs
│   │   └── ApiKey.php                     # API key model
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Services/
│       └── DeployService.php              # Cloudflare deploy hook trigger
├── config/
│   ├── app.php                            # Lean provider list
│   ├── cache.php                          # File driver, zeplow_api prefix
│   ├── cors.php                           # Allowed origins for frontends
│   ├── database.php                       # MySQL connection
│   └── services.php                       # Cloudflare deploy hook URLs
├── database/
│   ├── migrations/
│   │   ├── xxxx_create_site_content_table.php
│   │   ├── xxxx_create_site_configs_table.php
│   │   ├── xxxx_create_contact_submissions_table.php
│   │   ├── xxxx_create_deploy_logs_table.php
│   │   └── xxxx_create_api_keys_table.php
│   └── seeders/
│       └── ApiKeySeeder.php               # Generate initial API key
├── routes/
│   └── api.php                            # All route definitions
├── .env                                   # Environment configuration
├── .htaccess                              # Force HTTPS on cPanel
└── composer.json
```

---

## 24. IMPLEMENTATION ORDER

### Phase 1: Project Setup (Day 1)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 1.1 | Create MySQL database `api_zeplow` on cPanel | Via cPanel MySQL interface | Nothing |
| 1.2 | Create database user `api_user` with full privileges on `api_zeplow` | Via cPanel | 1.1 |
| 1.3 | Install fresh Laravel 11 project | `composer create-project laravel/laravel api-app` | Nothing |
| 1.4 | Install Sanctum | `composer require laravel/sanctum` | 1.3 |
| 1.5 | Strip unnecessary service providers from `config/app.php` | Remove Session, View, Broadcasting providers | 1.3 |
| 1.6 | Configure `.env` | Database, cache (file), session (array), queue (sync) | 1.1, 1.3 |
| 1.7 | Configure `config/cache.php` | File driver, `zeplow_api` prefix | 1.3 |

### Phase 2: Database & Models (Day 2)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 2.1 | Create migration: `site_content` table | See Section 4.2 schema | 1.3 |
| 2.2 | Create migration: `site_configs` table | See Section 4.2 schema | 1.3 |
| 2.3 | Create migration: `contact_submissions` table | See Section 4.2 schema | 1.3 |
| 2.4 | Create migration: `deploy_logs` table | See Section 4.2 schema | 1.3 |
| 2.5 | Create migration: `api_keys` table | See Section 4.2 schema | 1.3 |
| 2.6 | Run all migrations | `php artisan migrate` | 1.1, 1.6, 2.1–2.5 |
| 2.7 | Create Eloquent models | SiteContent, SiteConfig, ContactSubmission, DeployLog, ApiKey | 2.1–2.5 |
| 2.8 | Create ApiKeySeeder | Generates 64-char API key, stores in `api_keys` table | 2.7 |
| 2.9 | Run seeder, record API key | `php artisan db:seed --class=ApiKeySeeder` | 2.6, 2.8 |

### Phase 3: Middleware & Services (Day 3)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 3.1 | Create ValidateApiKey middleware | Bearer token validation against `api_keys` table | 2.7 |
| 3.2 | Register middleware aliases `api_key` and `site_key` | In Kernel.php or bootstrap/app.php | 3.1, 3.5 |
| 3.3 | Create DeployService | Cloudflare deploy hook trigger with logging | 2.7 |
| 3.4 | Add `services.cloudflare.deploy_hooks` to `config/services.php` | Hook URLs from .env | 1.6 |
| 3.5 | Create ValidateSiteKey middleware | Whitelist validation for `{siteKey}` route parameter against `['parent', 'narrative', 'logic']` | 1.3 |
| 3.6 | Implement cache version counter system | Add `Cache::increment` / `Cache::get` version logic to all list cache controllers and ContentSyncController invalidation | 3.3 |

### Phase 4: Internal Controllers (Days 4–5)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 4.1 | Create ContentSyncController | sync(), delete(), syncAll() with TYPE_TO_CACHE_PREFIX mapping | 2.7, 3.3 |
| 4.2 | Create ConfigSyncController | sync() with cache invalidation | 2.7, 3.3 |
| 4.3 | Create DeployController | Manual deploy trigger | 3.3 |
| 4.4 | Define internal routes in `routes/api.php` | `/internal/v1/*` with `api_key:internal` middleware | 3.2, 4.1–4.3 |
| 4.5 | Test: POST /internal/v1/content/sync without key | Expect 401 | 4.4 |
| 4.6 | Test: POST /internal/v1/content/sync with invalid key | Expect 403 | 4.4 |
| 4.7 | Test: POST /internal/v1/content/sync with valid key | Expect 200, content stored | 4.4 |

### Phase 5: Public Controllers (Days 6–8)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 5.1 | Create SiteConfigController | GET /config with caching | 2.7 |
| 5.2 | Create SitePageController | GET /pages, GET /pages/{slug} with caching | 2.7 |
| 5.3 | Create SiteProjectController | GET /projects (with featured/limit/pagination), GET /projects/{slug} | 2.7 |
| 5.4 | Create SiteBlogController | GET /blog (with tag/limit/pagination), GET /blog/{slug} | 2.7 |
| 5.5 | Create SiteTestimonialController | GET /testimonials | 2.7 |
| 5.6 | Create SiteTeamController | GET /team | 2.7 |
| 5.7 | Create ContactController | POST /contact with honeypot + email notification | 2.7 |
| 5.8 | Create HealthController | GET /health | 1.3 |
| 5.9 | Define public routes in `routes/api.php` | `/sites/v1/{siteKey}/*` with throttle middleware | 5.1–5.8 |
| 5.10 | Configure CORS | `config/cors.php` with allowed origins | 1.3 |
| 5.11 | Configure rate limiting | 60/min on public endpoints | 1.3 |

### Phase 6: Error Handling & Exception Handler (Day 8)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 6.1 | Create custom Exception Handler | JSON-only responses for all errors | 1.3 |
| 6.2 | Test all error scenarios | 401, 403, 404, 422, 429, 500 | 5.9, 6.1 |

### Phase 7: Deployment & Integration Testing (Days 9–10)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 7.1 | Deploy API to cPanel | Upload via Git/FTP, configure .env, run migrations | All above |
| 7.2 | Configure Cloudflare proxy for api.zeplow.com | Verify orange cloud is on, SSL works | 7.1 |
| 7.3 | Add deploy hook URLs to API .env | CF_DEPLOY_HOOK_PARENT, NARRATIVE, LOGIC | 7.1 |
| 7.4 | Test: GET /health | Returns 200 with database connected | 7.2 |
| 7.5 | Test: CMS publish → API receives → content stored | End-to-end sync test (requires CMS deployed) | 7.2 |
| 7.6 | Test: Public API endpoints return correct JSON | curl/Postman testing on all endpoints | 7.2 |
| 7.7 | Test: Content sync → deploy hook fires → Cloudflare rebuilds | Full pipeline test | 7.3, 7.5 |
| 7.8 | Test: Contact form submission → email received | End-to-end contact test | 7.2 |
| 7.9 | Test: Rate limiting works | 60+ requests in 1 minute → 429 | 7.2 |
| 7.10 | Test: Honeypot detection works | Submit with fax_number filled → fake 200, nothing stored | 7.2 |

---

## 25. TESTING CHECKLIST

### 25.1 Authentication Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| GET /health | Returns `{"status": "ok"}` with 200 | ☐ |
| POST /internal/v1/content/sync without API key | Returns 401 | ☐ |
| POST /internal/v1/content/sync with invalid key | Returns 403 | ☐ |
| POST /internal/v1/content/sync with valid key | Returns 200, content stored | ☐ |
| POST /internal/v1/config/sync with valid key | Returns 200, config stored | ☐ |
| DELETE /internal/v1/content/sync with valid key | Returns 200, content deleted | ☐ |

### 25.2 Public Endpoint Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| GET /sites/v1/narrative/config | Returns site config with nav, footer, CTA, socials | ☐ |
| GET /sites/v1/narrative/pages | Returns list of published pages | ☐ |
| GET /sites/v1/narrative/pages/about | Returns full page content with blocks | ☐ |
| GET /sites/v1/logic/projects?featured=true | Returns only featured projects | ☐ |
| GET /sites/v1/logic/projects?limit=3 | Returns simple array (no pagination meta), max 3 items | ☐ |
| GET /sites/v1/logic/projects (no limit) | Returns paginated response with data + meta | ☐ |
| GET /sites/v1/logic/projects/tututor-ai | Returns full project detail with challenge/solution/outcome | ☐ |
| GET /sites/v1/parent/blog | Returns published blog posts | ☐ |
| GET /sites/v1/parent/blog?tag=branding | Returns only posts tagged "branding" | ☐ |
| GET /sites/v1/narrative/blog/some-slug | Returns full blog post with body HTML | ☐ |
| GET /sites/v1/narrative/testimonials | Returns published testimonials sorted by sort_order | ☐ |
| GET /sites/v1/narrative/team | Returns all team members sorted by sort_order | ☐ |
| GET /sites/v1/nonexistent/pages | Returns 404 | ☐ |
| GET /sites/v1/narrative/pages/nonexistent | Returns 404 | ☐ |

### 25.3 Contact Form Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| POST /sites/v1/narrative/contact with valid data | Returns 200, email sent, stored in DB | ☐ |
| POST /sites/v1/narrative/contact with missing name | Returns 422 with validation errors | ☐ |
| POST /sites/v1/narrative/contact with invalid email | Returns 422 with validation errors | ☐ |
| POST /sites/v1/narrative/contact with honeypot (`fax_number`) filled | Returns fake 200, nothing stored | ☐ |

### 25.4 Cache & Performance Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Same endpoint called twice within 1 hour | Second response served from cache (faster) | ☐ |
| Sync blog_post content type → check cache key | Cache key uses "blog" prefix, not "blog_posts" | ☐ |
| Sync content → check cache invalidation | List and detail caches cleared for correct prefix | ☐ |
| Check Cache-Control header on public GET | Contains `public, max-age=3600` | ☐ |

### 25.5 Rate Limiting Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Hit rate limit (60+ requests/minute) | Returns 429 | ☐ |
| Internal endpoint ignores rate limit | No 429 regardless of request count | ☐ |
| Trigger simultaneous builds for all 3 sites | None of the 3 builds receive 429 responses | ☐ |

### 25.6 Deploy Hook Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Content sync triggers deploy hook | deploy_logs shows "success" | ☐ |
| Config sync triggers deploy hook | deploy_logs shows "success" | ☐ |
| Content delete triggers deploy hook | deploy_logs shows "success" | ☐ |
| Manual trigger via /internal/v1/deploy/trigger/{siteKey} | deploy_logs shows "success" | ☐ |
| Invalid site_key for deploy | No crash, warning logged | ☐ |

---

## 26. POST-LAUNCH CHECKLIST

| # | Task | When |
|:---|:---|:---|
| 1 | Verify api.zeplow.com responds to GET /health | Day 1 |
| 2 | Verify Cloudflare SSL is active on api.zeplow.com | Day 1 |
| 3 | Verify Cloudflare proxy is active (orange cloud) | Day 1 |
| 4 | Verify CORS headers present on public endpoints | Day 1 |
| 5 | Test CMS→API sync end-to-end (publish content, verify in API DB) | Day 1 |
| 6 | Test contact form end-to-end (submit → email received) | Day 1 |
| 7 | Document the API key in a secure location (not in Git) | Day 1 |
| 8 | Back up api_zeplow database | Day 1 (then weekly) |
| 9 | Monitor API response times for first week | Week 1 |
| 10 | Check deploy_logs for any failed deploys | Weekly |
| 11 | Check contact_submissions for any unread submissions | Daily |
| 12 | Monitor laravel.log for errors | Weekly |
| 13 | Verify rate limiting is functioning (not too strict for builds, not too loose for abuse) | Day 2 |
| 14 | Verify cache invalidation works after content updates | Day 2 |

---

*End of API PRD.*
