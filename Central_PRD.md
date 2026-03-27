# ZEPLOW PLATFORM — PRODUCT REQUIREMENTS DOCUMENT (PRD)

**Version:** 1.3
**Date:** March 27, 2026
**Author:** Shakib Bin Kabir
**Status:** Final — Ready for Implementation (Architect Review Applied)

---

## TABLE OF CONTENTS

1. Project Overview (includes Known Limitations)
2. System Architecture
3. App 1: CMS (cms.zeplow.com)
4. App 2: API (api.zeplow.com) (includes Site Key Validation Middleware)
5. App 3: Frontend — Monorepo (3 Next.js Sites) (includes Image Optimization Strategy)
6. Database Schemas
7. CMS → API Sync System
8. Build & Deploy Pipeline
9. Authentication & Security (API key hashing)
10. Caching Strategy (prefix-based cache invalidation)
11. Error Handling & Logging (includes Monitoring & Alerting)
12. SEO Requirements
13. Performance Requirements
14. DNS & Domain Configuration
15. Environment Variables
16. Third-Party Services
17. Implementation Order
18. Testing Checklist
19. Post-Launch Checklist (includes Backup Strategy)

---

## 1. PROJECT OVERVIEW

### 1.1 What We're Building

Three public websites powered by a centralized CMS and API system:

| Site | Domain | Purpose |
|:---|:---|:---|
| Zeplow Parent | zeplow.com | Authority, group overview, venture hub |
| Zeplow Narrative | narrative.zeplow.com | Creative agency — brand storytelling services |
| Zeplow Logic | logic.zeplow.com | Tech company — automation & dev services |

Plus two backend applications:

| App | Domain | Purpose |
|:---|:---|:---|
| CMS | cms.zeplow.com | Content management (Filament admin panel) |
| API | api.zeplow.com | Central API serving all frontends + future systems |

### 1.2 Technical Constraints

| Constraint | Value |
|:---|:---|
| Backend hosting | cPanel shared hosting (existing) |
| Frontend hosting | Cloudflare Pages (free tier) |
| Budget | $0/month additional |
| CMS framework | Laravel 11 + Filament v3 |
| API framework | Laravel 11 (API-only, no Filament) |
| Frontend framework | Next.js 14+ (App Router, static export) |
| Databases | MySQL (2 separate databases on same cPanel) |
| Content update frequency | ~1x per week |
| Target performance | Lighthouse 95+ on all pages |

### 1.3 Non-Goals (Explicitly Out of Scope)

- Frontend visual design (will be done separately)
- Client portal / dashboard
- Live chat / Intercom
- Newsletter / email capture system
- Multi-language support
- Analytics dashboard in Filament
- ERP, billing, or any future API scopes (API structure supports them, but we don't build them now)
- Mobile apps

### 1.4 Known Limitations (V1)

| Limitation | Impact | Future Improvement |
|:---|:---|:---|
| **No content versioning or rollback** | If incorrect content is published, the only recovery is to manually edit it back in the CMS. There is no undo, no revision history, and no diff view. | Add a `content_versions` table in the CMS that stores a JSON snapshot of each model's data on every save, with a "Restore to version" action in Filament. |
| **No draft preview** | Editors cannot preview unpublished content on the live frontend. They must publish to see how it looks. | Add a preview mode that fetches draft content from the CMS directly (bypassing the API). |
| **Single-region hosting** | CMS and API are hosted on a single cPanel server. If the server goes down, new content cannot be published (but live sites remain up via Cloudflare CDN). | Migrate to a managed cloud provider with multi-region support if uptime SLA is needed. |

---

## 2. SYSTEM ARCHITECTURE

### 2.1 Architecture Diagram

```
┌───────────────────────────────────────────��──────────────────┐
│  APP 1: CMS                                                   │
│  cms.zeplow.com (cPanel Shared Hosting)                       │
│  Laravel 11 + Filament v3                                     │
│  MySQL DB: cms_zeplow                                         │
│                                                               │
│  Editors manage content here.                                 │
│  On publish → Observer dispatches Job → HTTP POST to API app. │
└──────────────────────┬────────────────────────────────────────┘
                       │
                       │  POST api.zeplow.com/internal/v1/content/sync
                       │  (Authenticated via shared API key)
                       │
                       ↓
┌──────────────────────────────────────────────────────────────┐
│  APP 2: API                                                   │
│  api.zeplow.com (cPanel Shared Hosting)                       │
│  Laravel 11 (API-only, no Filament, no sessions)              │
│  MySQL DB: api_zeplow                                         ���
│                                                               │
│  Receives content from CMS → stores in own DB.                │
│  Serves public REST API for frontends.                        │
│  On content sync → fires Cloudflare deploy hook.              │
└──────────────────────┬────────────────────────────────────────┘
                       │
                       │  POST https://api.cloudflare.com/pages/webhooks/...
                       │  (Deploy hook per site)
                       │
                       ↓
┌──────────────────────────────────────────────────────────────┐
│  CLOUDFLARE PAGES (Free Tier)                                 │
│                                                               │
│  Receives deploy hook → pulls code from GitHub                │
│  Runs: npm ci → next build (on Cloudflare infrastructure)     │
│  During build, Next.js calls api.zeplow.com to fetch content  │
│  Outputs pure static HTML/CSS/JS                              │
│  Deploys to Cloudflare CDN (330+ global edge nodes)           │
│                                                               │
│  3 Cloudflare Pages projects:                                 │
│    zeplow-parent    → zeplow.com                              │
│    zeplow-narrative → narrative.zeplow.com                    │
│    zeplow-logic     → logic.zeplow.com                        │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Data Flow — Content Publish Cycle

```
Step 1: Editor logs into cms.zeplow.com
Step 2: Editor creates/edits content (page, project, blog post, etc.)
Step 3: Editor clicks "Publish" in Filament
Step 4: Laravel Observer on the model detects the save
Step 5: Observer dispatches a SyncContentJob
        - With QUEUE_CONNECTION=sync, job executes immediately
        - With database/redis queue (future), job executes asynchronously
        - Includes: site_key, content_type, slug, full data payload
        - Authenticated via API key in Authorization header
        - Retry: 3 attempts with 5-second backoff between each
Step 6: API app validates the payload
Step 7: API app upserts content into api_zeplow database
Step 8: API app fires Cloudflare Pages deploy hook for the affected site
Step 9: Cloudflare Pages pulls latest code from GitHub
Step 10: Next.js build runs, fetching content from api.zeplow.com/sites/v1/...
Step 11: Static HTML generated and deployed to Cloudflare CDN
Step 12: Live site updated (~60-90 seconds after Step 3)
```

### 2.3 Data Flow — Visitor Request (Runtime)

```
Step 1: Visitor types narrative.zeplow.com in browser
Step 2: DNS resolves to Cloudflare (managed via Cloudflare DNS)
Step 3: Cloudflare edge node (nearest to visitor) serves cached static HTML
Step 4: No server hit, no database query, no PHP execution
Step 5: Page loads in <1 second globally
```

---

## 3. APP 1: CMS (cms.zeplow.com)

### 3.1 Overview

| Property | Value |
|:---|:---|
| Framework | Laravel 11 |
| Admin Panel | Filament v3 |
| Database | MySQL — `cms_zeplow` |
| Hosting | cPanel shared hosting |
| URL | cms.zeplow.com |
| Users | Shakib + Shadman (2 admin users) |
| Purpose | Content creation and management only |

### 3.2 Laravel Packages Required

| Package | Purpose | Version |
|:---|:---|:---|
| `filament/filament` | Admin panel | ^3.0 |
| `filament/spatie-laravel-media-library-plugin` | Image/file uploads in Filament | ^3.0 |
| `spatie/laravel-medialibrary` | Media management (images, files) | ^11.0 |
| `guzzlehttp/guzzle` | HTTP client for API sync | ^7.0 (included in Laravel) |

No other packages. Keep the CMS lean.

### 3.3 Filament Resources (Admin Panel Sections)

Each resource represents a section in the Filament sidebar:

| Resource | Model | Description |
|:---|:---|:---|
| **SiteResource** | Site | Manage the 3 sites (parent, narrative, logic) |
| **PageResource** | Page | Manage static pages per site |
| **ProjectResource** | Project | Manage portfolio/case study items per site |
| **BlogPostResource** | BlogPost | Manage blog articles per site |
| **TestimonialResource** | Testimonial | Manage client quotes per site |
| **TeamMemberResource** | TeamMember | Manage founder/team bios per site |
| **SiteConfigResource** | SiteConfig | Manage navigation, footer, CTA per site |

### 3.4 Filament Resource Specifications

#### 3.4.1 SiteResource

**Purpose:** Manage the 3 website properties.
**Permissions:** Only super-admin can create/delete sites. Both admins can edit.

**Form Fields:**

| Field | Type | Validation | Notes |
|:---|:---|:---|:---|
| name | TextInput | required, max:255 | "Zeplow", "Zeplow Narrative", "Zeplow Logic" |
| key | TextInput | required, unique, max:50, alpha_dash | "parent", "narrative", "logic" |
| domain | TextInput | required, max:255 | "zeplow.com", "narrative.zeplow.com", etc. |
| tagline | TextInput | nullable, max:255 | "Story. Systems. Ventures." |
| description | Textarea | nullable | Short site description |
| seo_defaults | KeyValue | nullable | Default meta_title, meta_description, og_image |

**Table Columns:** name, key, domain, updated_at

**Seed Data (3 records):**

| name | key | domain | tagline |
|:---|:---|:---|:---|
| Zeplow | parent | zeplow.com | Story. Systems. Ventures. |
| Zeplow Narrative | narrative | narrative.zeplow.com | Stories that sell. |
| Zeplow Logic | logic | logic.zeplow.com | Build once. Run forever. |

#### 3.4.2 PageResource

**Purpose:** Manage static pages (Home, About, Services, etc.) per site.

**Form Fields:**

| Field | Type | Validation | Notes |
|:---|:---|:---|:---|
| site_id | Select (relationship) | required | Dropdown of sites |
| title | TextInput | required, max:255 | "About Us", "Our Services" |
| slug | TextInput | required, max:255, unique per site | Auto-generated from title, editable |
| template | Select | required | Options: "home", "about", "services", "work", "process", "insights", "contact", "ventures", "default" |
| content | Repeater (content blocks) | nullable | See Content Block System below |
| seo_title | TextInput | nullable, max:255 | Override page title for SEO |
| seo_description | Textarea | nullable, max:500 | Meta description |
| og_image | FileUpload (SpatieMediaLibrary) | nullable | Open Graph image |
| is_published | Toggle | default: false | Controls visibility |
| published_at | DateTimePicker | nullable | Schedule publishing |
| sort_order | TextInput (numeric) | default: 0 | Page ordering in navigation |

**Content Block System (Repeater):**

Each page's `content` field is a Filament Repeater with a Block type selector. Each block has:

| Block Type | Fields | Description |
|:---|:---|:---|
| `hero` | heading (text), subheading (text), cta_text (text), cta_url (text), background_color (color) | Hero/banner section |
| `text` | heading (text, nullable), body (RichEditor) | General text section |
| `cards` | heading (text, nullable), cards (Repeater: title, description, link_text, link_url) | Grid of cards |
| `cta` | heading (text), description (text, nullable), button_text (text), button_url (text), style (select: "primary", "secondary") | Call-to-action block |
| `image` | image (FileUpload), alt_text (text), caption (text, nullable), full_width (toggle) | Single image |
| `gallery` | images (Repeater: image, alt_text, caption) | Image gallery |
| `testimonials` | heading (text, nullable), use_all (toggle), selected_ids (MultiSelect, relationship) | Testimonial display block |
| `team` | heading (text, nullable), use_all (toggle), selected_ids (MultiSelect, relationship) | Team member display block |
| `projects` | heading (text, nullable), count (number), featured_only (toggle) | Project grid block |
| `stats` | stats (Repeater: number, label, suffix) | Statistics/metrics display |
| `divider` | style (select: "line", "space", "gradient") | Visual separator |
| `raw_html` | html (Textarea) | Custom HTML (emergency use only) |

**Table Columns:** title, site.name, template, is_published, sort_order, updated_at
**Table Filters:** site_id, is_published, template

> **Note:** See CMS PRD Section 7.4 for complete block-level validation rules. All required fields are enforced in the Filament Repeater to prevent malformed JSON from reaching the API.

#### 3.4.3 ProjectResource

**Purpose:** Manage portfolio/case study items.

**Form Fields:**

| Field | Type | Validation | Notes |
|:---|:---|:---|:---|
| site_id | Select (relationship) | required | Which site this project belongs to |
| title | TextInput | required, max:255 | "Tututor.ai" |
| slug | TextInput | required, max:255, unique per site | Auto-generated from title |
| one_liner | Textarea | required, max:500 | "An AI-powered tutoring platform..." |
| client_name | TextInput | nullable, max:255 | Client/brand name |
| industry | TextInput | nullable, max:255 | "EdTech", "Logistics", etc. |
| url | TextInput (url) | nullable | Live project URL |
| challenge | Textarea | nullable | Problem/bottleneck (for case study format) |
| solution | Textarea | nullable | What was built/delivered |
| outcome | Textarea | nullable | Results/metrics |
| tech_stack | TagsInput | nullable | ["Next.js", "Python", "PostgreSQL"] |
| images | SpatieMediaLibrary (multiple) | required, min:1 | Project screenshots |
| tags | TagsInput | nullable | ["branding", "web", "automation"] |
| featured | Toggle | default: false | Show on homepage |
| is_published | Toggle | default: false | Controls visibility |
| sort_order | TextInput (numeric) | default: 0 | Display ordering |

**Table Columns:** title, site.name, client_name, industry, featured, is_published, sort_order
**Table Filters:** site_id, is_published, featured, industry

#### 3.4.4 BlogPostResource

**Purpose:** Manage blog/insights articles.

**Form Fields:**

| Field | Type | Validation | Notes |
|:---|:---|:---|:---|
| site_id | Select (relationship) | required | Which site |
| title | TextInput | required, max:255 | Article title |
| slug | TextInput | required, max:255, unique per site | Auto-generated |
| excerpt | Textarea | nullable, max:500 | Short preview text |
| body | RichEditor | required | Full article content |
| cover_image | SpatieMediaLibrary | nullable | Header image |
| tags | TagsInput | nullable | ["branding", "automation", "strategy"] |
| author | TextInput | nullable, max:255 | Author name |
| seo_title | TextInput | nullable, max:255 | Override title for SEO |
| seo_description | Textarea | nullable, max:500 | Meta description |
| is_published | Toggle | default: false | Controls visibility |
| published_at | DateTimePicker | nullable | Publish date |

**Table Columns:** title, site.name, author, tags, is_published, published_at
**Table Filters:** site_id, is_published, author

#### 3.4.5 TestimonialResource

**Form Fields:**

| Field | Type | Validation | Notes |
|:---|:---|:---|:---|
| site_id | Select (relationship) | required | Which site |
| name | TextInput | required, max:255 | Person's name |
| role | TextInput | nullable, max:255 | "CEO", "Founder" |
| company | TextInput | nullable, max:255 | Company name |
| quote | Textarea | required, max:1000 | The testimonial text |
| avatar | SpatieMediaLibrary | nullable | Person's photo |
| is_published | Toggle | default: true | Visibility |
| sort_order | TextInput (numeric) | default: 0 | Display order |

**Table Columns:** name, company, site.name, is_published, sort_order

#### 3.4.6 TeamMemberResource

**Form Fields:**

| Field | Type | Validation | Notes |
|:---|:---|:---|:---|
| site_id | Select (relationship) | required | Which site |
| name | TextInput | required, max:255 | "Shadman Sakib" |
| role | TextInput | required, max:255 | "Co-Founder & CEO" |
| bio | Textarea | nullable, max:1000 | Short bio |
| photo | SpatieMediaLibrary | nullable | Profile photo |
| linkedin | TextInput (url) | nullable | LinkedIn profile URL |
| email | TextInput (email) | nullable | Contact email |
| is_founder | Toggle | default: false | Distinguish founders from team |
| is_published | Toggle | default: true | Controls visibility — only published team members are synced to API and shown on frontend |
| sort_order | TextInput (numeric) | default: 0 | Display order |

**Table Columns:** name, role, site.name, is_founder, is_published, sort_order
**Table Filters:** site_id, is_published

#### 3.4.7 SiteConfigResource

**Purpose:** Manage per-site configuration (navigation, footer, global CTA).

**Form Fields:**

| Field | Type | Validation | Notes |
|:---|:---|:---|:---|
| site_id | Select (relationship) | required, unique | One config per site |
| nav_items | Repeater | required | label (text), url (text), is_external (toggle) |
| footer_links | Repeater | nullable | group_title (text), links (Repeater: label, url) |
| footer_text | TextInput | nullable | "© 2026 Zeplow LTD." |
| cta_text | TextInput | nullable | "Book a Heartbeat Review" |
| cta_url | TextInput | nullable | "/contact" |
| social_links | KeyValue | nullable | { linkedin: "...", instagram: "...", whatsapp: "..." } |
| contact_email | TextInput (email) | nullable | "hello@zeplow.com" |
| contact_phone | TextInput | nullable | Phone/WhatsApp number |

**Table Columns:** site.name, cta_text, updated_at

### 3.5 Filament Dashboard Widgets

| Widget | Content |
|:---|:---|
| **Content Overview** | Card showing: Total Pages (published/draft), Total Projects, Total Blog Posts across all sites |
| **Last Deploy Status** | Shows timestamp of last successful sync to API per site. If last sync failed, shows red warning with error message |
| **Quick Actions** | Buttons: "Resync All Content" (manual trigger), "View Parent Site", "View Narrative Site", "View Logic Site" |

### 3.6 Filament Users & Auth

| User | Email | Role |
|:---|:---|:---|
| Shakib | shakib@zeplow.com | Super Admin (full access) |
| Shadman | shadman@zeplow.com | Admin (full content access, no site creation/deletion) |

Authentication: Filament's built-in auth (Laravel session-based). No external SSO needed.

### 3.7 Media Storage

| Setting | Value |
|:---|:---|
| Storage disk | `public` (local storage on cPanel) |
| Base URL | `cms.zeplow.com/storage/` |
| Max upload size | 5 MB per file |
| Allowed types | jpg, jpeg, png, webp, svg, gif, pdf |
| Image conversions | Thumbnail (400×300), Medium (800×600), Large (1600×1200) — via Spatie MediaLibrary |

**Note:** When content syncs to the API, image URLs are sent as absolute URLs (`https://cms.zeplow.com/storage/...`). The frontend renders these directly. Images are served from the CMS server, but since `cms.zeplow.com` is proxied through Cloudflare (orange cloud), all images are cached at Cloudflare's edge CDN — providing free global CDN for media files. If storage needs grow beyond cPanel limits, migrate media to Cloudflare R2 (free tier: 10 GB storage, 10 million requests/month).

---

## 4. APP 2: API (api.zeplow.com)

### 4.1 Overview

| Property | Value |
|:---|:---|
| Framework | Laravel 11 |
| Admin Panel | None (API-only) |
| Database | MySQL — `api_zeplow` |
| Hosting | cPanel shared hosting |
| URL | api.zeplow.com |
| Purpose | Central API for all frontends + future systems |

### 4.2 Laravel Configuration (Lean Setup)

This app must boot fast. Strip everything unnecessary:

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

```php name=api-app/config/app.php
// Key configuration differences from a standard Laravel app

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

### 4.3 Laravel Packages Required

| Package | Purpose | Version |
|:---|:---|:---|
| `laravel/sanctum` | API authentication (internal endpoints) | ^4.0 |
| `guzzlehttp/guzzle` | HTTP client for deploy hooks | ^7.0 (included) |

Nothing else. The API app has two jobs: store content and serve JSON.

### 4.4 Site Key Validation (Middleware)

All public endpoints under `/sites/v1/{siteKey}/*` must validate `{siteKey}` against an allowed whitelist **before** any database query, cache lookup, or controller logic executes. This prevents caching empty results for garbage site keys and avoids unnecessary database load.

**Allowed site keys:** `['parent', 'narrative', 'logic']`

If the `{siteKey}` is not in the allowed list, the middleware must immediately return a `404` response with `{"error": "Site not found"}` — no further processing occurs.

```php name=api-app/app/Http/Middleware/ValidateSiteKey.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateSiteKey
{
    private const ALLOWED_SITE_KEYS = ['parent', 'narrative', 'logic'];

    public function handle(Request $request, Closure $next)
    {
        $siteKey = $request->route('siteKey');

        if (!in_array($siteKey, self::ALLOWED_SITE_KEYS, true)) {
            return response()->json(['error' => 'Site not found'], 404);
        }

        return $next($request);
    }
}
```

This middleware must be registered on all `/sites/v1/{siteKey}/*` route groups in `routes/api.php`:

```php
Route::prefix('sites/v1/{siteKey}')
    ->middleware(['throttle:60,1', ValidateSiteKey::class])
    ->group(function () {
        // All public site endpoints...
    });
```

### 4.5 API Route Structure

```
api.zeplow.com/
│
├── /sites/v1/{siteKey}/              ← Public API (Next.js consumes at build time)
│   ├── GET /config                   ← Site configuration (nav, footer, CTA, socials)
│   ├── GET /pages                    ← All published pages (list)
│   ├── GET /pages/{slug}             ← Single page with content blocks
│   ├── GET /projects                 ← All published projects (list, with pagination)
│   ├── GET /projects/{slug}          ← Single project detail
│   ├── GET /blog                     ← All published blog posts (list, with pagination)
│   ├── GET /blog/{slug}              ← Single blog post
│   ├── GET /testimonials             ← All published testimonials
│   ├── GET /team                     ← All team members
│   └── POST /contact                 ← Contact form submission
│
├── /internal/v1/                     ← Private API (CMS → API sync)
│   ├── POST /content/sync            ← Receive content from CMS
│   ├── POST /config/sync             ← Receive site config from CMS
│   ├── DELETE /content/sync          ← Delete content from API
│   ├── POST /content/sync-all        ← Full resync (all content for a site)
│   └── POST /deploy/trigger/{siteKey}← Manually trigger deploy for a site
│
└── /health                           ← Health check endpoint
    └── GET /                         ← Returns { status: "ok", timestamp: "..." }
```

### 4.6 API Controller Specifications

#### 4.6.1 Public Endpoints — SiteConfigController

**GET `/sites/v1/{siteKey}/config`**

Response:
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

Cache: 1 hour. Headers: `Cache-Control: public, max-age=3600`

#### 4.6.2 Public Endpoints — SitePageController

**GET `/sites/v1/{siteKey}/pages`**

Response:
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
    "seo": { "..." : "..." },
    "sort_order": 1,
    "published_at": "2026-03-15T00:00:00Z"
  }
]
```

Cache: 1 hour.

**GET `/sites/v1/{siteKey}/pages/{slug}`**

Response:
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

Cache: 1 hour.

#### 4.6.3 Public Endpoints — SiteProjectController

**GET `/sites/v1/{siteKey}/projects`**

Query parameters:
- `featured=true` (optional) — filter to featured only
- `limit=6` (optional) — limit results (returns simple array, no pagination meta)
- `page=1` (optional) — page number for pagination
- `per_page=50` (optional) — items per page (default 50). *Projects are lightweight list items (title, one-liner, tags) with no heavy text fields, so a larger default page size is appropriate.*

Response (with limit parameter — simple array, backwards compatible):
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
      "https://cms.zeplow.com/storage/projects/tututor-2.jpg",
      "https://cms.zeplow.com/storage/projects/tututor-3.jpg"
    ],
    "tags": ["web-app", "ai", "saas"],
    "featured": true,
    "sort_order": 0
  }
]
```

Response (without limit parameter — paginated):
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

Cache: 1 hour.

**GET `/sites/v1/{siteKey}/projects/{slug}`**

Response:
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

Cache: 1 hour.

#### 4.6.4 Public Endpoints — SiteBlogController

**GET `/sites/v1/{siteKey}/blog`**

Query parameters:
- `tag=branding` (optional)
- `limit=10` (optional) — limit results (returns simple array, no pagination meta)
- `page=1` (optional) — page number for pagination
- `per_page=20` (optional) — items per page (default 20). *Blog posts include excerpts and heavier metadata, so a smaller default page size keeps response payloads fast.*

Response (with limit parameter — simple array):
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

Response (without limit parameter — paginated):
```json
{
  "data": [
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
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

Cache: 1 hour.

**GET `/sites/v1/{siteKey}/blog/{slug}`**

Response:
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

Cache: 1 hour.

#### 4.6.5 Public Endpoints — SiteTestimonialController

**GET `/sites/v1/{siteKey}/testimonials`**

Response:
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

Cache: 1 hour.

#### 4.6.6 Public Endpoints — SiteTeamController

**GET `/sites/v1/{siteKey}/team`**

**Visibility rule:** Only team members where `is_published = true` are synced from the CMS to the API. The API returns all synced team members — no additional filtering is needed at the API level because unpublished team members are never present in `api_zeplow`.

Response:
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

Cache: 1 hour.

#### 4.6.7 Public Endpoints — ContactController

**POST `/sites/v1/{siteKey}/contact`**

Request body:
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

Response:
```json
{
  "status": "received",
  "message": "Thank you. We'll be in touch within 24 hours."
}
```

**Spam protection:** Honeypot field (`website_url`). If the hidden field is filled by a bot, the API returns a fake success response without storing or emailing.

#### 4.6.8 Health Check

**GET `/health`**

Response:
```json
{
  "status": "ok",
  "timestamp": "2026-03-11T14:30:00Z",
  "database": "connected",
  "version": "1.0.0"
}
```

No cache. Used for monitoring.

---

## 5. APP 3: FRONTEND — MONOREPO (3 Next.js Sites)

### 5.1 Overview

| Property | Value |
|:---|:---|
| Framework | Next.js 14+ (App Router) |
| Output mode | Static export (`output: 'export'`) |
| Styling | Tailwind CSS v3 |
| Animation | Framer Motion |
| Monorepo tool | Turborepo |
| Hosting | Cloudflare Pages (free tier) |
| Repository | Single GitHub repo (monorepo) |

### 5.2 Monorepo Structure

```
zeplow-sites/
│
├── apps/
│   ├── parent/                         # zeplow.com
│   │   ├── app/
│   │   │   ├── layout.tsx              # Root layout (fonts, metadata, nav, footer)
│   │   │   ├── page.tsx                # Home page
│   │   │   ├── about/
│   │   │   │   └── page.tsx
│   │   │   ├── ventures/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── narrative/
│   │   │   │   │   └── page.tsx
│   │   │   │   └── logic/
│   │   │   │       └── page.tsx
│   │   │   ├── insights/
│   │   │   │   ├── page.tsx            # Blog listing
│   │   │   │   └── [slug]/
│   │   │   │       └── page.tsx        # Individual blog post
│   │   │   ├── careers/
│   │   │   │   └── page.tsx            # Placeholder page
│   │   │   └── contact/
│   │   │       └── page.tsx
│   │   ├── components/                 # Parent-site-specific components
│   │   │   ├── VentureCard.tsx
│   │   │   └── BeliefBlock.tsx
│   │   ├── next.config.js
│   │   ├── tailwind.config.ts
│   │   ├── tsconfig.json
│   │   └── package.json
│   │
│   ├── narrative/                      # narrative.zeplow.com
│   │   ├── app/
│   │   │   ├── layout.tsx
│   │   │   ├── page.tsx                # Home
│   │   │   ├── about/
│   │   │   │   └── page.tsx
│   │   │   ├── services/
│   │   │   │   └── page.tsx
│   │   │   ├── work/
│   │   │   │   ├── page.tsx            # Portfolio grid
│   │   │   │   └── [slug]/
│   │   │   │       └── page.tsx        # Individual project
│   │   │   ├── process/
│   │   │   │   └── page.tsx
│   │   │   ├── insights/
│   │   │   │   ├── page.tsx
│   │   │   │   └── [slug]/
│   │   │   │       └── page.tsx
│   │   │   └── contact/
│   │   │       └── page.tsx
│   │   ├── components/                 # Narrative-specific components
│   │   │   ├── HeartbeatCTA.tsx
│   │   │   └── AntiClientBlock.tsx
│   │   ├── next.config.js
│   │   ├── tailwind.config.ts
│   │   ├── tsconfig.json
│   │   └── package.json
│   │
│   └── logic/                          # logic.zeplow.com
│       ├── app/
│       │   ├── layout.tsx
│       │   ├── page.tsx                # Home
│       │   ├── about/
│       │   │   └── page.tsx
│       │   ├── services/
│       │   │   └── page.tsx
│       │   ├── work/
│       │   │   ├── page.tsx            # Portfolio grid
│       │   │   └── [slug]/
│       │   │       └── page.tsx        # Individual project (Incident Report)
│       │   ├── process/
│       │   │   └── page.tsx
│       │   ├── insights/
│       │   │   ├── page.tsx
│       │   │   └── [slug]/
│       │   │       └── page.tsx
│       │   └── contact/
│       │       └── page.tsx
│       ├── components/                 # Logic-specific components
│       │   ├── AuditCTA.tsx
│       │   └── IncidentReport.tsx
│       ├── next.config.js
│       ├── tailwind.config.ts
│       ├── tsconfig.json
│       └── package.json
│
├── packages/
│   ├── ui/                             # Shared React components
│   │   ├── src/
│   │   │   ├── Container.tsx           # Max-width wrapper
│   │   │   ├── Button.tsx              # CTA buttons (primary, secondary styles)
│   │   │   ├── Navigation.tsx          # Nav bar (data-driven from API config)
│   │   │   ├── Footer.tsx              # Footer (data-driven from API config)
│   │   │   ├── SectionHeading.tsx      # Consistent section headings
│   │   │   ├── ProjectCard.tsx         # Portfolio grid card
│   │   │   ├── BlogCard.tsx            # Blog listing card
│   │   │   ├── TestimonialCard.tsx     # Testimonial display
│   │   │   ├── TeamCard.tsx            # Team member card
│   │   │   ├── StatsStrip.tsx          # Metrics/numbers display
│   │   │   ├── ContactForm.tsx         # Contact form (submits to API, includes honeypot)
│   │   │   ├── SEO.tsx                 # Metadata component (generates head tags)
│   │   │   ├── ContentRenderer.tsx     # Renders content blocks from API (hero, text, cards, etc.)
│   │   │   ├── OrganizationSchema.tsx  # JSON-LD Organization structured data
│   │   │   ├── ArticleSchema.tsx       # JSON-LD Article structured data
│   │   │   └── index.ts               # Barrel export
│   │   ├── package.json
│   │   └── tsconfig.json
│   │
│   ├── api/                            # Shared API client
│   │   ├── src/
│   │   │   ├── client.ts              # Fetch wrapper
│   │   │   ├── types.ts              # TypeScript interfaces for all API responses
│   │   │   └── index.ts              # Barrel export
│   │   ├── package.json
│   │   └── tsconfig.json
│   │
│   └── config/                         # Shared configuration
│       ├── src/
│       │   ├── colors.ts              # Brand color tokens (parent, narrative, logic)
│       │   ├── fonts.ts               # Font family mappings
│       │   └── index.ts              # Barrel export
│       ├── package.json
│       └── tsconfig.json
│
├── turbo.json                          # Turborepo pipeline config
├── package.json                        # Root package.json (workspaces)
├── pnpm-workspace.yaml                 # PNPM workspace definition
├── .gitignore
└── README.md
```

### 5.3 Key Configuration Files

```json name=turbo.json
{
  "$schema": "https://turbo.build/schema.json",
  "tasks": {
    "build": {
      "dependsOn": ["^build"],
      "outputs": ["out/**", ".next/**"]
    },
    "dev": {
      "cache": false,
      "persistent": true
    },
    "lint": {
      "dependsOn": ["^build"]
    }
  }
}
```

```yaml name=pnpm-workspace.yaml
packages:
  - 'apps/*'
  - 'packages/*'
```

```json name=package.json
{
  "name": "zeplow-sites",
  "private": true,
  "scripts": {
    "dev:parent": "turbo run dev --filter=parent",
    "dev:narrative": "turbo run dev --filter=narrative",
    "dev:logic": "turbo run dev --filter=logic",
    "build:parent": "turbo run build --filter=parent",
    "build:narrative": "turbo run build --filter=narrative",
    "build:logic": "turbo run build --filter=logic",
    "build:all": "turbo run build",
    "lint": "turbo run lint"
  },
  "devDependencies": {
    "turbo": "^2.0.0"
  },
  "packageManager": "pnpm@9.0.0"
}
```

```javascript name=apps/parent/next.config.js
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true,
  },
  transpilePackages: ['@zeplow/ui', '@zeplow/api', '@zeplow/config'],
}

module.exports = nextConfig
```

```javascript name=apps/narrative/next.config.js
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true,
  },
  transpilePackages: ['@zeplow/ui', '@zeplow/api', '@zeplow/config'],
}

module.exports = nextConfig
```

```javascript name=apps/logic/next.config.js
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true,
  },
  transpilePackages: ['@zeplow/ui', '@zeplow/api', '@zeplow/config'],
}

module.exports = nextConfig
```

### 5.4 Image Optimization Strategy

Since Next.js static export uses `unoptimized: true` (no built-in `next/image` optimization), the frontend must rely on Spatie MediaLibrary conversions generated by the CMS for properly sized images. Each image uploaded to the CMS generates three conversions:

| Conversion | Dimensions | Use Case |
|:---|:---|:---|
| `thumbnail` | 400×300 | Card thumbnails, grid items, avatar-sized images |
| `medium` | 800×600 | List page images, blog listing cover images |
| `large` | 1600×1200 | Hero sections, project detail hero images, full-width images |

**URL pattern for Spatie conversions:**
```
https://cms.zeplow.com/storage/{path}/conversions/{filename}-{conversion}.jpg
```

Example:
- Original: `https://cms.zeplow.com/storage/projects/tututor-1.jpg`
- Thumbnail: `https://cms.zeplow.com/storage/projects/conversions/tututor-1-thumbnail.jpg`
- Medium: `https://cms.zeplow.com/storage/projects/conversions/tututor-1-medium.jpg`
- Large: `https://cms.zeplow.com/storage/projects/conversions/tututor-1-large.jpg`

**API client helper:** The `@zeplow/api` package should include a helper function to build the correct image URL for a given conversion:

```typescript name=packages/api/src/image.ts
/**
 * Build a Spatie media conversion URL from an original image URL.
 *
 * @param originalUrl - The original image URL from the API (e.g., https://cms.zeplow.com/storage/projects/tututor-1.jpg)
 * @param conversion - The desired conversion: 'thumbnail', 'medium', or 'large'
 * @returns The conversion URL, or the original URL if conversion cannot be derived
 */
export function getImageUrl(
  originalUrl: string,
  conversion?: 'thumbnail' | 'medium' | 'large'
): string {
  if (!conversion || !originalUrl) return originalUrl;

  // Insert /conversions/ before the filename and append -{conversion}
  const lastSlash = originalUrl.lastIndexOf('/');
  const path = originalUrl.substring(0, lastSlash);
  const filename = originalUrl.substring(lastSlash + 1);
  const nameWithoutExt = filename.substring(0, filename.lastIndexOf('.'));
  const ext = filename.substring(filename.lastIndexOf('.'));

  return `${path}/conversions/${nameWithoutExt}-${conversion}${ext}`;
}
```

**Usage guidelines for frontend developers:**
- **Card components** (ProjectCard, BlogCard, TeamCard): Use `thumbnail` or `medium` conversion
- **List pages** (portfolio grid, blog listing): Use `medium` conversion
- **Hero sections and detail pages**: Use `large` conversion
- **Fallback**: If a conversion URL 404s (e.g., conversion failed), fall back to the original URL

#### Image Optimization Strategy — Complete Pipeline

Images flow through 4 optimization stages:

**Stage 1: Upload Validation (CMS)**
- Max file size: 5 MB per image
- Accepted formats: JPEG, PNG, WebP
- Enforced by Filament's SpatieMediaLibraryFileUpload validation rules

**Stage 2: Spatie MediaLibrary Conversions (CMS, at upload time)**
- `thumbnail`: 400x300, crop-fit, quality 80
- `medium`: 800x600, fit-contain, quality 85
- `large`: 1600x1200, fit-contain, quality 90
- Format: Same as original (JPEG->JPEG, PNG->PNG, WebP->WebP)
- All conversions are non-queued (generated immediately on upload)

**Stage 3: Cloudflare Polish (CDN, at delivery time)**
- Enable Cloudflare Polish on the `cms.zeplow.com` zone (Pro plan feature -- if using free plan, skip this stage and rely on Stages 2 and 4)
- Polish mode: "Lossy" -- automatically converts images to WebP when the browser supports it (via `Accept: image/webp` header)
- This provides format conversion (WebP) without any build-time processing

**Stage 3 (Alternative for Free Plan): Manual WebP Conversion**
- If Cloudflare Polish is unavailable (free plan), add WebP as an additional Spatie conversion:
  ```php
  $this->addMediaConversion('large-webp')
      ->width(1600)->height(1200)
      ->format('webp')
      ->quality(85)
      ->nonQueued();
  ```
- The Observer payload should include both original and WebP URLs
- The frontend renders a `<picture>` element with WebP source and JPEG/PNG fallback

**Stage 4: Responsive Image Rendering (Frontend)**
- All image rendering components must use the `<picture>` element with `srcSet` and `sizes` attributes
- Use the Spatie conversion URLs to provide responsive sources

Create a shared image component in `packages/ui/src/ResponsiveImage.tsx`:

```typescript name=packages/ui/src/ResponsiveImage.tsx
interface ResponsiveImageProps {
  images: {
    thumbnail?: string;
    medium?: string;
    large?: string;
    original: string;
  };
  alt: string;
  className?: string;
  priority?: boolean;
  sizes?: string;
}

export function ResponsiveImage({ images, alt, className, priority, sizes }: ResponsiveImageProps) {
  const srcSet = [
    images.thumbnail && `${images.thumbnail} 400w`,
    images.medium && `${images.medium} 800w`,
    images.large && `${images.large} 1600w`,
  ].filter(Boolean).join(', ');

  return (
    <img
      src={images.large || images.original}
      srcSet={srcSet || undefined}
      sizes={sizes || '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw'}
      alt={alt}
      className={className}
      loading={priority ? 'eager' : 'lazy'}
      decoding={priority ? 'sync' : 'async'}
    />
  );
}
```

### 5.5 Shared API Client

```typescript name=packages/api/src/types.ts
// All TypeScript interfaces for API responses

export interface ProjectImage {
  original: string;
  large: string;
  medium: string;
  thumbnail: string;
  large_webp: string | null;
  alt: string;
}

export interface MediaImage {
  original: string;
  large: string;
  medium: string;
  thumbnail: string;
}

export interface SiteConfig {
  site_key: string;
  site_name: string;
  domain: string;
  tagline: string;
  nav_items: NavItem[];
  footer_links: FooterLinkGroup[];
  footer_text: string;
  cta_text: string;
  cta_url: string;
  social_links: Record<string, string>;
  contact_email: string;
}

export interface NavItem {
  label: string;
  url: string;
  is_external: boolean;
}

export interface FooterLinkGroup {
  group_title: string;
  links: { label: string; url: string }[];
}

export interface PageListItem {
  id: number;
  slug: string;
  title: string;
  template: string;
  seo: SEO;
  sort_order: number;
  published_at: string;
}

export interface Page {
  id: number;
  slug: string;
  title: string;
  template: string;
  content: ContentBlock[];
  seo: SEO;
  published_at: string;
}

export interface ContentBlock {
  type: 'hero' | 'text' | 'cards' | 'cta' | 'image' | 'gallery' |
        'testimonials' | 'team' | 'projects' | 'stats' | 'divider' | 'raw_html';
  data: Record<string, any>;
}

export interface SEO {
  title: string;
  description: string;
  og_image: string | null;
}

export interface ProjectListItem {
  id: number;
  slug: string;
  title: string;
  one_liner: string;
  client_name: string | null;
  industry: string | null;
  url: string | null;
  images: ProjectImage[];
  tags: string[];
  featured: boolean;
  sort_order: number;
}

export interface Project extends ProjectListItem {
  challenge: string | null;
  solution: string | null;
  outcome: string | null;
  tech_stack: string[];
  published_at: string;
}

export interface BlogPostListItem {
  id: number;
  slug: string;
  title: string;
  excerpt: string | null;
  cover_image: MediaImage | null;
  tags: string[];
  author: string | null;
  published_at: string;
}

export interface BlogPost extends BlogPostListItem {
  body: string;
  seo: SEO;
}

export interface Testimonial {
  id: number;
  name: string;
  role: string | null;
  company: string | null;
  quote: string;
  avatar: MediaImage | null;
  sort_order: number;
}

export interface TeamMember {
  id: number;
  name: string;
  role: string;
  bio: string | null;
  photo: MediaImage | null;
  linkedin: string | null;
  email: string | null;
  is_founder: boolean;
  sort_order: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
```

```typescript name=packages/api/src/client.ts
import type {
  SiteConfig, PageListItem, Page, ProjectListItem, Project,
  BlogPostListItem, BlogPost, Testimonial, TeamMember
} from './types';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://api.zeplow.com';

async function fetchApi<T>(path: string): Promise<T> {
  const url = `${API_BASE}${path}`;

  const res = await fetch(url, {
    headers: { 'Accept': 'application/json' },
    // Next.js static export: this runs at build time only
  });

  if (!res.ok) {
    throw new Error(`API Error: ${res.status} ${res.statusText} for ${url}`);
  }

  return res.json();
}

// Site Config
export function getSiteConfig(siteKey: string): Promise<SiteConfig> {
  return fetchApi(`/sites/v1/${siteKey}/config`);
}

// Pages
export function getPages(siteKey: string): Promise<PageListItem[]> {
  return fetchApi(`/sites/v1/${siteKey}/pages`);
}

export function getPage(siteKey: string, slug: string): Promise<Page> {
  return fetchApi(`/sites/v1/${siteKey}/pages/${slug}`);
}

// Projects
export function getProjects(siteKey: string, params?: { featured?: boolean; limit?: number }): Promise<ProjectListItem[]> {
  const query = new URLSearchParams();
  if (params?.featured) query.set('featured', 'true');
  if (params?.limit) query.set('limit', String(params.limit));
  const qs = query.toString();
  return fetchApi(`/sites/v1/${siteKey}/projects${qs ? `?${qs}` : ''}`);
}

export function getProject(siteKey: string, slug: string): Promise<Project> {
  return fetchApi(`/sites/v1/${siteKey}/projects/${slug}`);
}

// Blog
export function getBlogPosts(siteKey: string, params?: { tag?: string; limit?: number }): Promise<BlogPostListItem[]> {
  const query = new URLSearchParams();
  if (params?.tag) query.set('tag', params.tag);
  if (params?.limit) query.set('limit', String(params.limit));
  const qs = query.toString();
  return fetchApi(`/sites/v1/${siteKey}/blog${qs ? `?${qs}` : ''}`);
}

export function getBlogPost(siteKey: string, slug: string): Promise<BlogPost> {
  return fetchApi(`/sites/v1/${siteKey}/blog/${slug}`);
}

// Testimonials
export function getTestimonials(siteKey: string): Promise<Testimonial[]> {
  return fetchApi(`/sites/v1/${siteKey}/testimonials`);
}

// Team
export function getTeamMembers(siteKey: string): Promise<TeamMember[]> {
  return fetchApi(`/sites/v1/${siteKey}/team`);
}
```

```typescript name=packages/api/src/index.ts
export * from './client';
export * from './types';
export * from './image';
```

### 5.6 Shared Configuration

```typescript name=packages/config/src/colors.ts
export const colors = {
  parent: {
    primary: '#034c3c',       // Pine Teal
    background: '#f4f4f4',    // White Smoke
    text: '#140004',          // Coffee Bean
    accent: '#ff6f59',        // Vibrant Coral
  },
  narrative: {
    primary: '#034c3c',       // Pine Teal
    background: '#f4f4f4',    // White Smoke
    text: '#140004',          // Coffee Bean
    accent: '#ff6f59',        // Vibrant Coral
  },
  logic: {
    primary: '#081f1a',       // Deep Logic
    background: '#f4f4f4',    // White Smoke
    text: '#081f1a',          // Deep Logic (same as primary)
    accent: '#00b894',        // System Teal
    error: '#ff7675',         // Error Coral
  },
} as const;
```

```typescript name=packages/config/src/fonts.ts
export const fonts = {
  parent: {
    heading: 'Playfair Display',
    body: 'Manrope',
  },
  narrative: {
    heading: 'Playfair Display',
    body: 'Manrope',
  },
  logic: {
    heading: 'JetBrains Mono',
    body: 'Inter',
  },
} as const;
```

### 5.7 Content Renderer (Critical Shared Component)

```typescript name=packages/ui/src/ContentRenderer.tsx
import type { ContentBlock } from '@zeplow/api';

interface ContentRendererProps {
  blocks: ContentBlock[];
  siteKey: 'parent' | 'narrative' | 'logic';
}

// Defensive block validation — skip blocks with missing required fields.
// This prevents rendering errors if malformed JSON reaches the frontend.
// The ContentRenderer should use this function to filter out invalid blocks
// before rendering (e.g., blocks.filter(isValidBlock).map(...)).
function isValidBlock(block: ContentBlock): boolean {
  switch (block.type) {
    case 'hero':
      return !!block.data?.heading;
    case 'text':
      return !!block.data?.body;
    case 'cards':
      return Array.isArray(block.data?.cards) && block.data.cards.length > 0;
    case 'cta':
      return !!block.data?.heading && !!block.data?.button_text && !!block.data?.button_url;
    case 'image':
      return !!block.data?.image && !!block.data?.alt_text;
    case 'gallery':
      return Array.isArray(block.data?.images) && block.data.images.length > 0;
    case 'stats':
      return Array.isArray(block.data?.stats) && block.data.stats.length > 0;
    case 'divider':
      return !!block.data?.style;
    case 'raw_html':
      return !!block.data?.html;
    default:
      return true; // testimonials, team, projects — always valid (they fetch their own data)
  }
}

// This component receives the content blocks array from the API
// and renders the appropriate component for each block type.
// The actual visual implementation of each block component
// will be done during the frontend design phase.
//
// Structure for implementation:
//
// Each block type maps to a React component:
//   'hero'         → <HeroBlock data={block.data} />
//   'text'         → <TextBlock data={block.data} />
//   'cards'        → <CardsBlock data={block.data} />
//   'cta'          → <CTABlock data={block.data} />
//   'image'        → <ImageBlock data={block.data} />
//   'gallery'      → <GalleryBlock data={block.data} />
//   'testimonials' → <TestimonialsBlock data={block.data} />
//   'team'         → <TeamBlock data={block.data} />
//   'projects'     → <ProjectsBlock data={block.data} />
//   'stats'        → <StatsBlock data={block.data} />
//   'divider'      → <DividerBlock data={block.data} />
//   'raw_html'     → <RawHTMLBlock data={block.data} />
//
// Unknown block types are skipped with a console.warn in development.
// Invalid blocks (missing required fields) are skipped via isValidBlock().

export function ContentRenderer({ blocks, siteKey }: ContentRendererProps) {
  return (
    <>
      {blocks.filter(isValidBlock).map((block, index) => {
        const BlockComponent = blockComponents[block.type];
        if (!BlockComponent) {
          if (process.env.NODE_ENV === 'development') {
            console.warn(`Unknown block type: ${block.type}`);
          }
          return null;
        }
        return <BlockComponent key={index} data={block.data} siteKey={siteKey} />;
      })}
    </>
  );
}

// Block component map — implementations will be built during design phase
const blockComponents: Record<string, React.ComponentType<any>> = {
  // To be implemented
};
```

### 5.8 Page Structure (How Each Page Works)

Every page in every site follows the same pattern:

```typescript name=apps/narrative/app/about/page.tsx
// Example: narrative.zeplow.com/about

import { getPage, getSiteConfig } from '@zeplow/api';
import { ContentRenderer } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'about');
  return {
    title: page.seo.title,
    description: page.seo.description,
    openGraph: {
      title: page.seo.title,
      description: page.seo.description,
      images: page.seo.og_image ? [page.seo.og_image] : [],
    },
  };
}

export default async function AboutPage() {
  const page = await getPage(SITE_KEY, 'about');

  return (
    <main>
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
    </main>
  );
}
```

Dynamic routes (blog posts, projects) use `generateStaticParams`:

```typescript name=apps/narrative/app/work/[slug]/page.tsx
import { getProjects, getProject, getSiteConfig } from '@zeplow/api';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateStaticParams() {
  const projects = await getProjects(SITE_KEY);
  return projects.map((project) => ({
    slug: project.slug,
  }));
}

export async function generateMetadata({ params }: { params: { slug: string } }): Promise<Metadata> {
  const project = await getProject(SITE_KEY, params.slug);
  return {
    title: `${project.title} — Zeplow Narrative`,
    description: project.one_liner,
  };
}

export default async function ProjectPage({ params }: { params: { slug: string } }) {
  const project = await getProject(SITE_KEY, params.slug);

  // Visual layout will be built during design phase
  // Data structure is ready:
  // project.title, project.one_liner, project.challenge,
  // project.solution, project.outcome, project.images,
  // project.tech_stack, project.tags, project.url
  
  return (
    <main>
      {/* Project detail layout — to be designed */}
    </main>
  );
}
```

### 5.9 Root Layout Pattern

```typescript name=apps/narrative/app/layout.tsx
import { getSiteConfig } from '@zeplow/api';
import { Navigation, Footer } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export const metadata: Metadata = {
  metadataBase: new URL('https://narrative.zeplow.com'),
};

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const config = await getSiteConfig(SITE_KEY);

  return (
    <html lang="en">
      <body>
        <Navigation
          siteName={config.site_name}
          items={config.nav_items}
          ctaText={config.cta_text}
          ctaUrl={config.cta_url}
          siteKey={SITE_KEY}
        />
        {children}
        <Footer
          links={config.footer_links}
          text={config.footer_text}
          socialLinks={config.social_links}
          contactEmail={config.contact_email}
          siteKey={SITE_KEY}
        />
      </body>
    </html>
  );
}
```

### 5.10 Contact Form Handling

The contact form on each site submits to the API with honeypot spam protection and Cloudflare Turnstile bot verification:

**Turnstile Integration Notes:**
- Turnstile site key is loaded from `NEXT_PUBLIC_CF_TURNSTILE_SITE_KEY` environment variable
- The Turnstile widget script is loaded dynamically via a `<script>` tag
- The Turnstile token is captured in component state and included in the form submission body as `cf_turnstile_response`
- After successful submission, the Turnstile widget is reset so a new token can be generated
- Server-side verification happens in the API's `ContactController` using `CF_TURNSTILE_SECRET_KEY`

```typescript name=packages/ui/src/ContactForm.tsx
'use client';

import { useState, useEffect, useRef, FormEvent } from 'react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.zeplow.com';
const TURNSTILE_SITE_KEY = process.env.NEXT_PUBLIC_CF_TURNSTILE_SITE_KEY || '';

interface ContactFormProps {
  siteKey: string;
  siteDomain: string;
}

export function ContactForm({ siteKey, siteDomain }: ContactFormProps) {
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [errorMessage, setErrorMessage] = useState('');
  const [turnstileToken, setTurnstileToken] = useState('');
  const turnstileRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    // Load Turnstile script
    if (!document.getElementById('cf-turnstile-script')) {
      const script = document.createElement('script');
      script.id = 'cf-turnstile-script';
      script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
      script.async = true;
      script.defer = true;
      document.head.appendChild(script);
    }

    // Render Turnstile widget once script is loaded
    const interval = setInterval(() => {
      if (window.turnstile && turnstileRef.current && !turnstileRef.current.hasChildNodes()) {
        window.turnstile.render(turnstileRef.current, {
          sitekey: TURNSTILE_SITE_KEY,
          callback: (token: string) => setTurnstileToken(token),
        });
        clearInterval(interval);
      }
    }, 100);

    return () => clearInterval(interval);
  }, []);

  async function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const formData = new FormData(form);

    // Honeypot check — if this hidden field is filled, it's a bot
    if (formData.get('website_url')) {
      // Silently pretend success to not alert the bot
      setStatus('success');
      return;
    }

    setStatus('loading');
    setErrorMessage('');

    try {
      const res = await fetch(`${API_URL}/sites/v1/${siteKey}/contact`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          name: formData.get('name'),
          email: formData.get('email'),
          company: formData.get('company'),
          message: formData.get('message'),
          budget_range: formData.get('budget_range'),
          source: siteDomain,
          cf_turnstile_response: turnstileToken,
        }),
      });

      if (res.ok) {
        setStatus('success');
        form.reset();
        // Reset Turnstile widget for next submission
        if (window.turnstile && turnstileRef.current) {
          window.turnstile.reset(turnstileRef.current);
          setTurnstileToken('');
        }
      } else {
        const data = await res.json();
        setErrorMessage(data.error || 'Something went wrong.');
        setStatus('error');
      }
    } catch {
      setErrorMessage('Something went wrong. Please try again or email us directly at hello@zeplow.com');
      setStatus('error');
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      {/* Honeypot field — hidden from humans, bots will fill it */}
      <div aria-hidden="true" style={{ position: 'absolute', left: '-9999px', top: '-9999px' }}>
        <label htmlFor="website_url">Leave this empty</label>
        <input type="text" id="website_url" name="website_url" tabIndex={-1} autoComplete="off" />
      </div>

      {/* Real form fields — visual implementation during design phase */}
      <input type="text" name="name" required placeholder="Your Name" />
      <input type="email" name="email" required placeholder="Email" />
      <input type="text" name="company" placeholder="Company (optional)" />
      <textarea name="message" required placeholder="Tell us about your project..." />
      <select name="budget_range">
        <option value="">Budget Range (optional)</option>
        <option value="Under $3,000">Under $3,000</option>
        <option value="$3,000 - $5,000">$3,000 - $5,000</option>
        <option value="$5,000 - $10,000">$5,000 - $10,000</option>
        <option value="$10,000+">$10,000+</option>
      </select>

      {/* Cloudflare Turnstile widget */}
      <div ref={turnstileRef} />

      <button type="submit" disabled={status === 'loading'}>
        {status === 'loading' ? 'Sending...' : 'Send Message'}
      </button>

      {status === 'success' && <p>Thank you. We'll be in touch within 24 hours.</p>}
      {status === 'error' && <p>{errorMessage}</p>}
    </form>
  );
}
```

### 5.11 Pages Per Site (Complete List)

**zeplow.com (parent) — 8 routes:**

| Route | Page | Data Source |
|:---|:---|:---|
| `/` | Home | `getPage('parent', 'home')` + `getProjects('parent', { featured: true, limit: 3 })` |
| `/about` | About | `getPage('parent', 'about')` + `getTeamMembers('parent')` |
| `/ventures` | Ventures overview | `getPage('parent', 'ventures')` |
| `/ventures/narrative` | Narrative overview | `getPage('parent', 'ventures-narrative')` |
| `/ventures/logic` | Logic overview | `getPage('parent', 'ventures-logic')` |
| `/insights` | Blog listing | `getBlogPosts('parent')` |
| `/insights/[slug]` | Blog post | `getBlogPost('parent', slug)` |
| `/careers` | Careers placeholder | `getPage('parent', 'careers')` |
| `/contact` | Contact form | `getPage('parent', 'contact')` |

**narrative.zeplow.com — 8 routes:**

| Route | Page | Data Source |
|:---|:---|:---|
| `/` | Home | `getPage('narrative', 'home')` + `getProjects('narrative', { featured: true, limit: 3 })` + `getTestimonials('narrative')` |
| `/about` | About | `getPage('narrative', 'about')` + `getTeamMembers('narrative')` |
| `/services` | Services | `getPage('narrative', 'services')` |
| `/work` | Portfolio grid | `getProjects('narrative')` |
| `/work/[slug]` | Project detail | `getProject('narrative', slug)` |
| `/process` | Methodology | `getPage('narrative', 'process')` |
| `/insights` | Blog listing | `getBlogPosts('narrative')` |
| `/insights/[slug]` | Blog post | `getBlogPost('narrative', slug)` |
| `/contact` | Contact form | `getPage('narrative', 'contact')` |

**logic.zeplow.com — 8 routes:**

| Route | Page | Data Source |
|:---|:---|:---|
| `/` | Home | `getPage('logic', 'home')` + `getProjects('logic', { featured: true, limit: 3 })` + `getTestimonials('logic')` |
| `/about` | About | `getPage('logic', 'about')` + `getTeamMembers('logic')` |
| `/services` | Services | `getPage('logic', 'services')` |
| `/work` | Portfolio grid | `getProjects('logic')` |
| `/work/[slug]` | Project detail (Incident Report) | `getProject('logic', slug)` |
| `/process` | Methodology | `getPage('logic', 'process')` |
| `/insights` | Blog listing | `getBlogPosts('logic')` |
| `/insights/[slug]` | Blog post | `getBlogPost('logic', slug)` |
| `/contact` | Contact form | `getPage('logic', 'contact')` |

**Total: 24 unique routes across 3 sites.**

---

## 6. DATABASE SCHEMAS

### 6.1 DB #1: `cms_zeplow` (CMS App)

```sql name=cms_zeplow_schema.sql
-- ============================================
-- DATABASE: cms_zeplow
-- Used by: CMS Laravel app (cms.zeplow.com)
-- ============================================

-- Users (Filament auth)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Sites
CREATE TABLE sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    `key` VARCHAR(50) NOT NULL UNIQUE,
    domain VARCHAR(255) NOT NULL,
    tagline VARCHAR(255) NULL,
    description TEXT NULL,
    seo_defaults JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Pages
CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    template ENUM('home', 'about', 'services', 'work', 'process', 'insights', 'contact', 'ventures', 'careers', 'default') NOT NULL DEFAULT 'default',
    content JSON NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(500) NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_slug (site_id, slug)
);

-- Projects
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    one_liner VARCHAR(500) NOT NULL,
    client_name VARCHAR(255) NULL,
    industry VARCHAR(255) NULL,
    url VARCHAR(500) NULL,
    challenge TEXT NULL,
    solution TEXT NULL,
    outcome TEXT NULL,
    tech_stack JSON NULL,
    images JSON NULL,
    tags JSON NULL,
    featured BOOLEAN NOT NULL DEFAULT FALSE,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_project_slug (site_id, slug)
);

-- Blog Posts
CREATE TABLE blog_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,
    body LONGTEXT NOT NULL,
    cover_image VARCHAR(500) NULL,
    tags JSON NULL,
    author VARCHAR(255) NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(500) NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_blog_slug (site_id, slug)
);

-- Testimonials
CREATE TABLE testimonials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(255) NULL,
    company VARCHAR(255) NULL,
    quote TEXT NOT NULL,
    avatar VARCHAR(500) NULL,
    is_published BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Team Members
CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    bio TEXT NULL,
    photo VARCHAR(500) NULL,
    linkedin VARCHAR(500) NULL,
    email VARCHAR(255) NULL,
    is_founder BOOLEAN NOT NULL DEFAULT FALSE,
    is_published BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Site Configs
CREATE TABLE site_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL UNIQUE,
    nav_items JSON NOT NULL,
    footer_links JSON NULL,
    footer_text VARCHAR(255) NULL,
    cta_text VARCHAR(255) NULL,
    cta_url VARCHAR(255) NULL,
    social_links JSON NULL,
    contact_email VARCHAR(255) NULL,
    contact_phone VARCHAR(50) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Sync Log (tracks what was sent to API)
CREATE TABLE sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    content_slug VARCHAR(255) NOT NULL,
    status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
    attempt_count INT NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_site_type (site_key, content_type)
);

-- Spatie Media Library (auto-created by package)
-- media table will be created by: php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

### 6.2 DB #2: `api_zeplow` (API App)

```sql name=api_zeplow_schema.sql
-- ============================================
-- DATABASE: api_zeplow
-- Used by: API Laravel app (api.zeplow.com)
-- ============================================

-- Site Content (flat store — receives synced content from CMS)
CREATE TABLE site_content (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    data JSON NOT NULL,
    published_at TIMESTAMP NULL,
    synced_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_site_type_slug (site_key, content_type, slug),
    INDEX idx_site_key (site_key),
    INDEX idx_content_type (content_type),
    INDEX idx_published (published_at)
);

-- Site Configs (synced from CMS)
CREATE TABLE site_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL UNIQUE,
    config JSON NOT NULL,
    synced_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Contact Submissions (from frontend contact forms)
CREATE TABLE contact_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    company VARCHAR(255) NULL,
    message TEXT NOT NULL,
    budget_range VARCHAR(100) NULL,
    source VARCHAR(255) NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_site_key (site_key),
    INDEX idx_is_read (is_read)
);

-- Deploy Log (tracks Cloudflare deploy hook triggers)
CREATE TABLE deploy_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,
    trigger_source VARCHAR(50) NOT NULL,
    status ENUM('triggered', 'success', 'failed', 'debounced') NOT NULL DEFAULT 'triggered',
    response_code INT NULL,
    response_body TEXT NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_site_key (site_key),
    INDEX idx_status (status)
);

-- API Keys (for CMS → API authentication)
-- IMPORTANT: The `key_hash` column stores a SHA-256 hash of the API key, NOT the plaintext key.
-- The plaintext key is shown ONCE at generation time (like GitHub personal access tokens) and never stored.
CREATE TABLE api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    key_hash VARCHAR(64) NOT NULL UNIQUE COMMENT 'SHA-256 hash of the API key',
    key_prefix VARCHAR(8) NOT NULL COMMENT 'First 8 chars of the key for identification (e.g., "zplw_a1b...")',
    scope ENUM('internal', 'build') NOT NULL DEFAULT 'internal',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_key_hash_active (key_hash, is_active)
);
-- Note: ValidateApiKey middleware only accepts 'internal' scope. ResolveBuildAgent middleware only accepts 'build' scope.

-- Future tables (structure reserved, not created now):
-- erp_*
-- billing_*
-- client_*
```

---

## 7. CMS → API SYNC SYSTEM

### 7.1 Sync Architecture

```
┌─────────────────────────────────────────────┐
│ CMS App                                      │
│                                              │
│ Model Observer (saved/deleted event)         │
│     ↓                                        │
│ SyncService dispatches a Job                 │
│     ↓                                        │
│ Job builds payload (site_key, type, slug)    │
│     ↓                                        │
│ Job sends HTTP POST to api.zeplow.com        │
│     ↓                                        │
│ Retry up to 3 times (5s backoff)             │
│     ↓                                        │
│ Log result in sync_logs table                │
│                                              │
│ Note: With QUEUE_CONNECTION=sync (current),  │
│ jobs execute immediately and synchronously.  │
│ Switch to database/redis queue later for     │
│ async processing without code changes.       │
└─────────────────────────────────────────────┘
```

### 7.2 CMS Sync Jobs

```php name=cms-app/app/Jobs/SyncContentJob.php
<?php

namespace App\Jobs;

use App\Models\SyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    /**
     * IMPORTANT: The sync_log_id is created BEFORE dispatch (in SyncService)
     * and passed to the job. This prevents orphaned "pending" log entries
     * on retries — every retry updates the SAME sync_log record.
     */
    public function __construct(
        private string $siteKey,
        private string $contentType,
        private string $slug,
        private array $data,
        private int $syncLogId,
        private ?string $publishedAt = null,
    ) {}

    public function handle(): void
    {
        $apiUrl = config('services.zeplow_api.url');
        $apiKey = config('services.zeplow_api.key');

        $log = SyncLog::findOrFail($this->syncLogId);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$apiUrl}/internal/v1/content/sync", [
                    'site_key'     => $this->siteKey,
                    'content_type' => $this->contentType,
                    'slug'         => $this->slug,
                    'data'         => $this->data,
                    'published_at' => $this->publishedAt,
                ]);

            if ($response->successful()) {
                $log->update([
                    'status'        => 'success',
                    'attempt_count' => $this->attempts(),
                    'synced_at'     => now(),
                ]);
                return;
            }

            $log->update([
                'attempt_count' => $this->attempts(),
                'last_error'    => "HTTP {$response->status()}: {$response->body()}",
            ]);

            throw new \RuntimeException("API returned {$response->status()}");

        } catch (\Exception $e) {
            $log->update([
                'attempt_count' => $this->attempts(),
                'last_error'    => $e->getMessage(),
                'status'        => $this->attempts() >= $this->tries ? 'failed' : 'pending',
            ]);

            Log::error("Sync failed (attempt {$this->attempts()}/{$this->tries})", [
                'site_key'     => $this->siteKey,
                'content_type' => $this->contentType,
                'slug'         => $this->slug,
                'error'        => $e->getMessage(),
            ]);

            throw $e; // Let the queue retry
        }
    }
}
```

```php name=cms-app/app/Jobs/SyncConfigJob.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private string $siteKey,
        private array $configData,
    ) {}

    public function handle(): void
    {
        $apiUrl = config('services.zeplow_api.url');
        $apiKey = config('services.zeplow_api.key');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$apiUrl}/internal/v1/config/sync", [
                    'site_key' => $this->siteKey,
                    'config'   => $this->configData,
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("API returned {$response->status()}");
            }

        } catch (\Exception $e) {
            Log::error("Config sync failed (attempt {$this->attempts()}/{$this->tries})", [
                'site_key' => $this->siteKey,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

```php name=cms-app/app/Jobs/DeleteContentJob.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private string $siteKey,
        private string $contentType,
        private string $slug,
    ) {}

    public function handle(): void
    {
        $apiUrl = config('services.zeplow_api.url');
        $apiKey = config('services.zeplow_api.key');

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept'        => 'application/json',
                ])
                ->delete("{$apiUrl}/internal/v1/content/sync", [
                    'site_key'     => $this->siteKey,
                    'content_type' => $this->contentType,
                    'slug'         => $this->slug,
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("API returned {$response->status()}");
            }

        } catch (\Exception $e) {
            Log::error("Content delete sync failed (attempt {$this->attempts()}/{$this->tries})", [
                'site_key'     => $this->siteKey,
                'content_type' => $this->contentType,
                'slug'         => $this->slug,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

### 7.3 CMS Sync Service

```php name=cms-app/app/Services/SyncService.php
<?php

namespace App\Services;

use App\Jobs\SyncContentJob;
use App\Jobs\SyncConfigJob;
use App\Jobs\DeleteContentJob;
use App\Models\SyncLog;

class SyncService
{
    /**
     * Dispatch a content sync job (non-blocking with async queue, immediate with sync queue).
     *
     * IMPORTANT: The sync_log entry is created HERE (before dispatch), not inside the job.
     * This ensures that retries update the SAME log record instead of creating orphaned
     * "pending" entries on each retry attempt.
     */
    public function syncContent(string $siteKey, string $contentType, string $slug, array $data, ?string $publishedAt = null): void
    {
        $log = SyncLog::create([
            'site_key'     => $siteKey,
            'content_type' => $contentType,
            'content_slug' => $slug,
            'status'       => 'pending',
        ]);

        SyncContentJob::dispatch($siteKey, $contentType, $slug, $data, $log->id, $publishedAt);
    }

    /**
     * Dispatch a config sync job.
     */
    public function syncConfig(string $siteKey, array $configData): void
    {
        SyncConfigJob::dispatch($siteKey, $configData);
    }

    /**
     * Dispatch a content delete job.
     */
    public function deleteContent(string $siteKey, string $contentType, string $slug): void
    {
        DeleteContentJob::dispatch($siteKey, $contentType, $slug);
    }
}
```

### 7.4 Model Observers (CMS App)

Each content model in the CMS has an Observer that triggers sync:

```php name=cms-app/app/Observers/PageObserver.php
<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\SyncService;

class PageObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(Page $page): void
    {
        if ($page->is_published) {
            $this->syncService->syncContent(
                siteKey: $page->site->key,
                contentType: 'page',
                slug: $page->slug,
                data: [
                    'title'    => $page->title,
                    'template' => $page->template,
                    'content'  => $page->content,
                    'seo'      => [
                        'title'       => $page->seo_title ?? $page->title,
                        'description' => $page->seo_description,
                        'og_image'    => $page->getFirstMediaUrl('og_image'),
                    ],
                    'sort_order' => $page->sort_order,
                ],
                publishedAt: $page->published_at?->toISOString(),
            );
        } elseif ($page->isDirty('is_published') && !$page->is_published) {
            // Just unpublished — remove from API
            $this->syncService->deleteContent(
                siteKey: $page->site->key,
                contentType: 'page',
                slug: $page->slug,
            );
        }
    }

    public function deleted(Page $page): void
    {
        $this->syncService->deleteContent(
            siteKey: $page->site->key,
            contentType: 'page',
            slug: $page->slug,
        );
    }
}
```

```php name=cms-app/app/Observers/ProjectObserver.php
<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\SyncService;

class ProjectObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(Project $project): void
    {
        if ($project->is_published) {
            $this->syncService->syncContent(
                siteKey: $project->site->key,
                contentType: 'project',
                slug: $project->slug,
                data: [
                    'title'      => $project->title,
                    'one_liner'  => $project->one_liner,
                    'client_name'=> $project->client_name,
                    'industry'   => $project->industry,
                    'url'        => $project->url,
                    'challenge'  => $project->challenge,
                    'solution'   => $project->solution,
                    'outcome'    => $project->outcome,
                    'tech_stack' => $project->tech_stack,
                    'images'     => $project->getMedia('images')->map->getUrl()->toArray(),
                    'tags'       => $project->tags,
                    'featured'   => $project->featured,
                    'sort_order' => $project->sort_order,
                ],
                publishedAt: now()->toISOString(),
            );
        } elseif ($project->isDirty('is_published') && !$project->is_published) {
            // Just unpublished — remove from API
            $this->syncService->deleteContent(
                siteKey: $project->site->key,
                contentType: 'project',
                slug: $project->slug,
            );
        }
    }

    public function deleted(Project $project): void
    {
        $this->syncService->deleteContent(
            siteKey: $project->site->key,
            contentType: 'project',
            slug: $project->slug,
        );
    }
}
```

**The same Observer pattern applies to:**
- `BlogPostObserver` (type: `blog_post`) — syncs only when `is_published` is true. When `is_published` is toggled to false (`isDirty('is_published') && !$blogPost->is_published`), the observer calls `deleteContent` to remove the blog post from the API.
- `TestimonialObserver` (type: `testimonial`) — syncs only when `is_published` is true. When `is_published` is toggled to false (`isDirty('is_published') && !$testimonial->is_published`), the observer calls `deleteContent` to remove the testimonial from the API.
- `TeamMemberObserver` (type: `team_member`) — syncs only when `is_published` is true (consistent with all other content types). When `is_published` is toggled to false, the observer calls `deleteContent` to remove the team member from the API.
- `SiteConfigObserver` (calls `syncConfig` instead of `syncContent`)

### 7.5 Observer Registration

```php name=cms-app/app/Providers/AppServiceProvider.php
<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Project;
use App\Models\BlogPost;
use App\Models\Testimonial;
use App\Models\TeamMember;
use App\Models\SiteConfig;
use App\Observers\PageObserver;
use App\Observers\ProjectObserver;
use App\Observers\BlogPostObserver;
use App\Observers\TestimonialObserver;
use App\Observers\TeamMemberObserver;
use App\Observers\SiteConfigObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Page::observe(PageObserver::class);
        Project::observe(ProjectObserver::class);
        BlogPost::observe(BlogPostObserver::class);
        Testimonial::observe(TestimonialObserver::class);
        TeamMember::observe(TeamMemberObserver::class);
        SiteConfig::observe(SiteConfigObserver::class);
    }
}
```

### 7.6 Manual Resync (Filament Action)

```php name=cms-app/app/Filament/Actions/ResyncAllAction.php
<?php

// Filament custom action — available on dashboard
// Resends ALL published content for a given site to the API

namespace App\Filament\Actions;

use App\Models\Site;
use App\Services\SyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ResyncAllAction
{
    public static function make(): Action
    {
        return Action::make('resync_all')
            ->label('Resync All Content')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
                \Filament\Forms\Components\Select::make('site_id')
                    ->label('Site')
                    ->options(Site::pluck('name', 'id'))
                    ->required(),
            ])
            ->action(function (array $data) {
                $site = Site::findOrFail($data['site_id']);
                $total = 0;

                // Sync all published pages
                foreach ($site->pages()->where('is_published', true)->get() as $page) {
                    $page->touch(); // triggers Observer
                    $total++;
                }

                // Sync all published projects
                foreach ($site->projects()->where('is_published', true)->get() as $project) {
                    $project->touch();
                    $total++;
                }

                // Sync all published blog posts
                foreach ($site->blogPosts()->where('is_published', true)->get() as $post) {
                    $post->touch();
                    $total++;
                }

                // Sync all published testimonials
                foreach ($site->testimonials()->where('is_published', true)->get() as $testimonial) {
                    $testimonial->touch();
                    $total++;
                }

                // Sync all published team members
                foreach ($site->teamMembers()->where('is_published', true)->get() as $member) {
                    $member->touch();
                    $total++;
                }

                // Sync site config
                if ($site->config) {
                    $site->config->touch();
                    $total++;
                }

                Notification::make()
                    ->title("Resync complete: {$total} items synced for {$site->name}")
                    ->success()
                    ->send();
            });
    }
}
```

---

## 8. BUILD & DEPLOY PIPELINE

### 8.1 Cloudflare Pages Configuration

**Project 1: zeplow-parent**

| Setting | Value |
|:---|:---|
| Production branch | `main` |
| Build command | `npx pnpm install --frozen-lockfile && npx pnpm run build:parent` |
| Build output directory | `apps/parent/out` |
| Root directory | `/` |
| Node.js version | `18` |
| Environment variable | `NEXT_PUBLIC_API_URL` = `https://api.zeplow.com` |
| Environment variable | `NEXT_PUBLIC_SITE_KEY` = `parent` |
| Custom domain | `zeplow.com` |
| Custom domain | `www.zeplow.com` (redirect to apex) |

**Project 2: zeplow-narrative**

| Setting | Value |
|:---|:---|
| Production branch | `main` |
| Build command | `npx pnpm install --frozen-lockfile && npx pnpm run build:narrative` |
| Build output directory | `apps/narrative/out` |
| Root directory | `/` |
| Node.js version | `18` |
| Environment variable | `NEXT_PUBLIC_API_URL` = `https://api.zeplow.com` |
| Environment variable | `NEXT_PUBLIC_SITE_KEY` = `narrative` |
| Custom domain | `narrative.zeplow.com` |

**Project 3: zeplow-logic**

| Setting | Value |
|:---|:---|
| Production branch | `main` |
| Build command | `npx pnpm install --frozen-lockfile && npx pnpm run build:logic` |
| Build output directory | `apps/logic/out` |
| Root directory | `/` |
| Node.js version | `18` |
| Environment variable | `NEXT_PUBLIC_API_URL` = `https://api.zeplow.com` |
| Environment variable | `NEXT_PUBLIC_SITE_KEY` = `logic` |
| Custom domain | `logic.zeplow.com` |

### 8.2 Deploy Hook Trigger (API App)

```php name=api-app/app/Services/DeployService.php
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

### 8.3 Build Behavior

When Cloudflare Pages builds a site:

1. Clones the GitHub monorepo
2. Installs all dependencies (`npm ci` / `pnpm install`)
3. Runs the build command for the specific site
4. During `next build`, Next.js executes all `generateStaticParams` and page data fetches
5. Each fetch hits `api.zeplow.com` (your shared hosting, proxied through Cloudflare)
6. API responds with JSON (cached at Laravel level for 1 hour)
7. Next.js renders all pages to static HTML in the `/out` directory
8. Cloudflare deploys the `/out` directory to its global CDN

**Expected build time:** 60–120 seconds per site
**Expected API calls during build:** 15–30 per site (depends on number of pages, projects, blog posts)

---

## 9. AUTHENTICATION & SECURITY

### 9.1 CMS App Authentication

| Concern | Implementation |
|:---|:---|
| Admin login | Filament built-in auth (session-based) |
| Password hashing | bcrypt (Laravel default) |
| Session driver | `file` (cPanel compatible) |
| CSRF protection | Enabled (Filament default) |
| Force HTTPS | Yes (via `.htaccess` on cPanel) |

### 9.2 API App Authentication

**Internal endpoints (CMS → API):**

| Concern | Implementation |
|:---|:---|
| Auth method | Bearer token (API key) |
| Key storage | `api_keys` table in DB #2 — **stores SHA-256 hash only, never the plaintext key** |
| Key format | 64-character random string (generated via `Str::random(64)`) |
| Key hashing | `hash('sha256', $key)` — stored in `key_hash` column |
| Key display | The plaintext key is shown **only once** at generation time (like GitHub personal access tokens). It cannot be recovered after that. |
| Key identification | First 8 characters stored in `key_prefix` for identification in admin UI (e.g., `zplw_a1b...`) |
| Header | `Authorization: Bearer {api_key}` |
| Validation | Middleware hashes the incoming bearer token with SHA-256, then checks the hash exists, is active, and scope matches |

**API Key Generation (Artisan Command):**

```php name=api-app/app/Console/Commands/GenerateApiKey.php
<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    protected $signature = 'api-key:generate {name} {--scope=internal}';
    protected $description = 'Generate a new API key (shown once, then only the hash is stored)';

    public function handle(): void
    {
        $plaintext = Str::random(64);

        ApiKey::create([
            'name'       => $this->argument('name'),
            'key_hash'   => hash('sha256', $plaintext),
            'key_prefix' => substr($plaintext, 0, 8),
            'scope'      => $this->option('scope'),
            'is_active'  => true,
        ]);

        $this->warn('⚠️  Copy this key now — it will NOT be shown again:');
        $this->info($plaintext);
        $this->info('Store this in the CMS .env file as ZEPLOW_API_KEY.');
    }
}
```

**ValidateApiKey Middleware:**

```php name=api-app/app/Http/Middleware/ValidateApiKey.php
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

        // Hash the incoming token and compare against stored hash
        $tokenHash = hash('sha256', $token);

        $apiKey = ApiKey::where('key_hash', $tokenHash)
            ->where('is_active', true)
            ->where('scope', $scope)
            ->first();

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API key'], 403);
        }

        $apiKey->update(['last_used_at' => now()]);

        return $next($request);
    }
}
```

**Public endpoints (Next.js → API):**

| Concern | Implementation |
|:---|:---|
| Auth method | None (public read-only + contact POST) |
| Rate limiting | Laravel throttle middleware: 60 requests/minute per IP |
| CORS | Allow origins: `zeplow.com`, `narrative.zeplow.com`, `logic.zeplow.com`, `localhost:3000`, `localhost:3001`, `localhost:3002` |

```php name=api-app/config/cors.php
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
    'allowed_headers' => ['Accept', 'Content-Type'],
    'max_age' => 86400,
];
```

### 9.3 Frontend Security

| Concern | Implementation |
|:---|:---|
| XSS | React auto-escapes by default. Blog body HTML rendered via `dangerouslySetInnerHTML` — sanitized at API level before storage |
| HTTPS | Enforced by Cloudflare (automatic) |
| Content Security Policy | Set via Cloudflare Pages headers (custom `_headers` file) |
| No secrets in frontend | `NEXT_PUBLIC_API_URL` is the only env var — it's a public URL |
| Spam protection | Honeypot field on contact form (hidden from humans, catches bots) |

```text name=apps/parent/public/_headers
/*
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  Permissions-Policy: camera=(), microphone=(), geolocation=()
```

---

## 10. CACHING STRATEGY

### 10.1 API Response Caching (Laravel)

```php name=api-app/app/Http/Controllers/Sites/SitePageController.php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Support\Facades\Cache;

class SitePageController extends Controller
{
    public function index(string $siteKey)
    {
        $cacheKey = "site:{$siteKey}:pages:list";

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

```php name=api-app/app/Http/Controllers/Sites/SiteProjectController.php
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

        $cacheKey = "site:{$siteKey}:projects:list:{$featured}:{$limit}:{$page}:{$perPage}";

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

```php name=api-app/app/Http/Controllers/Sites/SiteBlogController.php
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

        $cacheKey = "site:{$siteKey}:blog:list:{$tag}:{$limit}:{$page}:{$perPage}";

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

### 10.2 Cache Invalidation

When the API receives synced content, it must clear **all** relevant caches — including parameterized list query variants. The base `Cache::forget("site:{$siteKey}:{$prefix}:list")` alone is insufficient because parameterized cache keys like `list:true:3:1:50` (for featured/limit/page/perPage variations) survive until TTL expiry. Since the deploy hook triggers an immediate Cloudflare rebuild, the build would fetch stale cached data.

**Solution:** Use prefix-based cache flushing via the file cache store. A `CacheService` scans the cache directory for keys matching the pattern and deletes all variants.

```php name=api-app/app/Services/CacheService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CacheService
{
    /**
     * Flush all cache keys matching a given prefix.
     * This clears both the base list key AND all parameterized variants
     * (e.g., list:true:3:1:50, list:false:0:2:20, etc.).
     *
     * Works with the file cache driver by scanning cache files.
     * If migrating to Redis in the future, replace with Cache::getRedis()->keys("prefix*").
     */
    public function flushByPrefix(string $prefix): void
    {
        $cachePath = config('cache.stores.file.path', storage_path('framework/cache/data'));
        $cachePrefix = config('cache.prefix', 'zeplow_api');

        // For file-based cache, iterate through cache files and clear matching entries.
        // This is acceptable for our scale (~10-20 cache keys per content type per site).
        $fullPrefix = $cachePrefix . ':' . $prefix;

        // Clear exact match and known patterns
        Cache::forget($prefix);

        // Scan file cache store for matching keys
        $this->clearFileCacheByPrefix($cachePath, $fullPrefix);
    }

    private function clearFileCacheByPrefix(string $path, string $prefix): void
    {
        if (!File::isDirectory($path)) {
            return;
        }

        foreach (File::allFiles($path) as $file) {
            try {
                $contents = File::get($file->getPathname());
                // File cache stores serialized data with the key embedded
                if (str_contains($contents, $prefix)) {
                    File::delete($file->getPathname());
                }
            } catch (\Exception $e) {
                // Skip unreadable files
                continue;
            }
        }
    }
}
```

**ContentSyncController (updated with prefix-based cache flush):**

```php name=api-app/app/Http/Controllers/Internal/ContentSyncController.php
<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\CacheService;
use App\Services\DeployService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentSyncController extends Controller
{
    /**
     * Map content_type from CMS to the cache prefix used by public controllers.
     * CMS sends "blog_post", but the public endpoint is /blog, so cache key is "blog".
     */
    private const TYPE_TO_CACHE_PREFIX = [
        'page'        => 'pages',
        'project'     => 'projects',
        'blog_post'   => 'blog',
        'testimonial' => 'testimonials',
        'team_member' => 'team',
    ];

    public function __construct(
        private DeployService $deployService,
        private CacheService $cacheService,
    ) {}

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

        // Clear ALL relevant caches — individual slug key + ALL list variants
        $siteKey     = $validated['site_key'];
        $type        = $validated['content_type'];
        $slug        = $validated['slug'];
        $cachePrefix = self::TYPE_TO_CACHE_PREFIX[$type] ?? $type . 's';

        // Flush the specific item cache
        Cache::forget("site:{$siteKey}:{$cachePrefix}:{$slug}");

        // Flush ALL list variants (base + parameterized: list, list:true:3:1:50, etc.)
        $this->cacheService->flushByPrefix("site:{$siteKey}:{$cachePrefix}:list");

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

        Cache::forget("site:{$validated['site_key']}:{$cachePrefix}:{$validated['slug']}");
        $this->cacheService->flushByPrefix("site:{$validated['site_key']}:{$cachePrefix}:list");

        $this->deployService->trigger($validated['site_key'], 'content_delete');

        return response()->json(['status' => 'deleted']);
    }
}
```

> **Note:** If the API migrates to Redis in the future, replace `CacheService` with `Redis::del(Redis::keys("site:{$siteKey}:{$prefix}:list*"))` for a simpler and more performant wildcard flush.

### 10.3 Cache Configuration

```php name=api-app/config/cache.php
<?php

// File-based cache (cPanel compatible, no Redis needed)
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

---

## 11. ERROR HANDLING & LOGGING

### 11.1 CMS App Error Handling

| Scenario | Handling |
|:---|:---|
| Sync job fails (all 3 retries) | Log error in `sync_logs` table with status `failed`. With sync queue: editor sees delay. With async queue (future): editor doesn't notice, failure is logged silently. |
| API unreachable | Same as above. Content is safe in CMS DB. Can be resynced via "Resync All Content" action later. |
| Image upload fails | Filament shows default error. No custom handling needed. |
| Validation error | Filament shows inline field errors. Default behavior. |

### 11.2 API App Error Handling

| Scenario | Handling |
|:---|:---|
| Invalid API key on internal endpoint | Return 403 with `{"error": "Invalid API key"}` |
| Missing API key | Return 401 with `{"error": "Missing API key"}` |
| Content not found (public endpoint) | Return 404 with `{"error": "Not found"}` |
| Invalid site_key | Return 404 with `{"error": "Site not found"}` |
| Validation error on sync | Return 422 with field-level errors |
| Deploy hook fails | Log in `deploy_logs` table. Content is still stored — site will update on next successful deploy. |
| Database connection failure | Return 500 with `{"error": "Internal server error"}`. Log full error. |
| Rate limit exceeded | Return 429 with `{"error": "Too many requests"}` |
| Honeypot triggered on contact form | Return fake 200 success (don't alert bots) |

```php name=api-app/app/Exceptions/Handler.php
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

### 11.3 API ContactController

```php name=api-app/app/Http/Controllers/Sites/ContactController.php
<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request, string $siteKey)
    {
        // Layer 1: Honeypot check — reject silently if filled
        if ($request->filled('website_url')) {
            return response()->json([
                'status'  => 'received',
                'message' => 'Thank you. We\'ll be in touch within 24 hours.',
            ]);
        }

        // Layer 2: Cloudflare Turnstile verification
        $turnstileToken = $request->input('cf_turnstile_response');
        if (!$turnstileToken || !$this->verifyTurnstile($turnstileToken, $request->ip())) {
            // Return fake success to not alert bots
            Log::info('Turnstile verification failed', [
                'site_key' => $siteKey,
                'ip'       => $request->ip(),
            ]);
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

    private function verifyTurnstile(string $token, string $ip): bool
    {
        $secret = config('services.cloudflare.turnstile_secret');

        if (!$secret) {
            // If Turnstile is not configured, skip verification (dev/staging)
            return true;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return $response->json('success', false);
        } catch (\Exception $e) {
            Log::error('Turnstile verification request failed', ['error' => $e->getMessage()]);
            // On network failure, allow the submission (don't block legitimate users)
            return true;
        }
    }
}
```

### 11.4 Frontend Error Handling

| Scenario | Handling |
|:---|:---|
| API unreachable during build | Build fails. Cloudflare keeps serving the previous successful build. No downtime. |
| API returns 404 for a page | `generateStaticParams` won't include it. Page won't be generated. No crash. |
| API returns 500 | Build fails. Previous version stays live. |
| Contact form submission fails | Show user-friendly error: "Something went wrong. Please try again or email us directly at hello@zeplow.com" |
| Image URL broken | `<img>` shows alt text. No crash. |

### 11.5 Logging

**CMS App:** Laravel default file logging (`storage/logs/laravel.log`). Sync failures also logged in `sync_logs` table.

**API App:** Laravel default file logging. Deploy results in `deploy_logs` table. Contact submissions in `contact_submissions` table.

**Frontend:** No server-side logging (static site). Client-side errors visible in browser console only.

### 11.6 Monitoring & Alerting

| Monitor | Tool | Frequency | Alert Method |
|:---|:---|:---|:---|
| API health check | UptimeRobot (free tier) | Every 5 minutes | Email to shakib@zeplow.com |
| Failed sync log check | CMS Artisan command | Weekly (cron) | Log + Filament dashboard widget |
| Failed deploy log check | API Artisan command | Weekly (cron) | Log + email if failures found |
| Cloudflare Pages build failures | Cloudflare Pages notifications | On failure | Email (Cloudflare built-in) |

**UptimeRobot Setup:**
- Monitor URL: `https://api.zeplow.com/health`
- Check interval: 5 minutes
- Alert contacts: shakib@zeplow.com, shadman@zeplow.com
- Expected response: HTTP 200 with `{"status": "ok"}`

**CMS: Weekly Sync Failure Check (Artisan Command):**

```php name=cms-app/app/Console/Commands/CheckFailedSyncs.php
<?php

namespace App\Console\Commands;

use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckFailedSyncs extends Command
{
    protected $signature = 'monitor:check-failed-syncs';
    protected $description = 'Check for failed sync_log entries in the past week and alert if found';

    public function handle(): void
    {
        $failedCount = SyncLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        if ($failedCount > 0) {
            $message = "⚠️ {$failedCount} failed sync(s) in the past week. Check sync_logs table in cms_zeplow.";
            Log::warning($message);

            Mail::raw($message, function ($mail) use ($failedCount) {
                $mail->to('shakib@zeplow.com')
                    ->subject("Zeplow CMS: {$failedCount} failed sync(s) this week");
            });

            $this->warn($message);
        } else {
            $this->info('No failed syncs in the past week.');
        }
    }
}
```

**API: Weekly Deploy Failure Check (Artisan Command):**

```php name=api-app/app/Console/Commands/CheckFailedDeploys.php
<?php

namespace App\Console\Commands;

use App\Models\DeployLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckFailedDeploys extends Command
{
    protected $signature = 'monitor:check-failed-deploys';
    protected $description = 'Check for failed deploy_log entries in the past week and alert if found';

    public function handle(): void
    {
        $failedCount = DeployLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        if ($failedCount > 0) {
            $message = "⚠️ {$failedCount} failed deploy(s) in the past week. Check deploy_logs table in api_zeplow.";
            Log::warning($message);

            Mail::raw($message, function ($mail) use ($failedCount) {
                $mail->to('shakib@zeplow.com')
                    ->subject("Zeplow API: {$failedCount} failed deploy(s) this week");
            });

            $this->warn($message);
        } else {
            $this->info('No failed deploys in the past week.');
        }
    }
}
```

**Cron Schedule (both apps):**

```php name=cms-app/app/Console/Kernel.php (schedule method)
$schedule->command('monitor:check-failed-syncs')->weeklyOn(1, '09:00'); // Monday 9 AM
```

```php name=api-app/app/Console/Kernel.php (schedule method)
$schedule->command('monitor:check-failed-deploys')->weeklyOn(1, '09:00'); // Monday 9 AM
```

**Cloudflare Pages Build Failure Notifications:**
- Navigate to each Cloudflare Pages project → Settings → Notifications
- Enable "Build failed" email notifications to shakib@zeplow.com and shadman@zeplow.com

---

## 12. SEO REQUIREMENTS

### 12.1 Meta Tags Per Page

Every page must have:

| Tag | Source | Required |
|:---|:---|:---|
| `<title>` | `seo.title` from API (fallback: page title + site name) | Yes |
| `<meta name="description">` | `seo.description` from API | Yes |
| `<meta property="og:title">` | Same as title | Yes |
| `<meta property="og:description">` | Same as description | Yes |
| `<meta property="og:image">` | `seo.og_image` from API (fallback: site default) | Yes |
| `<meta property="og:url">` | Canonical URL of the page | Yes |
| `<meta property="og:type">` | `website` for pages, `article` for blog posts | Yes |
| `<link rel="canonical">` | Full URL of the page | Yes |

### 12.2 Sitemap Generation

Each site generates a `sitemap.xml` at build time:

```typescript name=apps/parent/app/sitemap.ts
import { getPages, getBlogPosts } from '@zeplow/api';
import type { MetadataRoute } from 'next';

const BASE_URL = 'https://zeplow.com';
const SITE_KEY = 'parent';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const pages = await getPages(SITE_KEY);
  const blogPosts = await getBlogPosts(SITE_KEY);

  const pageEntries = pages.map((page) => ({
    url: `${BASE_URL}/${page.slug === 'home' ? '' : page.slug}`,
    lastModified: page.published_at,
    changeFrequency: 'monthly' as const,
    priority: page.slug === 'home' ? 1.0 : 0.8,
  }));

  const blogEntries = blogPosts.map((post) => ({
    url: `${BASE_URL}/insights/${post.slug}`,
    lastModified: post.published_at,
    changeFrequency: 'weekly' as const,
    priority: 0.6,
  }));

  return [...pageEntries, ...blogEntries];
}
```

### 12.3 robots.txt

```text name=apps/parent/public/robots.txt
User-agent: *
Allow: /
Sitemap: https://zeplow.com/sitemap.xml
```

```text name=apps/narrative/public/robots.txt
User-agent: *
Allow: /
Sitemap: https://narrative.zeplow.com/sitemap.xml
```

```text name=apps/logic/public/robots.txt
User-agent: *
Allow: /
Sitemap: https://logic.zeplow.com/sitemap.xml
```

### 12.4 Structured Data (JSON-LD)

Each site's homepage includes Organization schema:

```typescript name=packages/ui/src/OrganizationSchema.tsx
interface Props {
  name: string;
  url: string;
  description: string;
  logo: string;
  email: string;
  sameAs: string[];
}

export function OrganizationSchema({ name, url, description, logo, email, sameAs }: Props) {
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name,
    url,
    description,
    logo,
    email,
    sameAs,
  };

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
    />
  );
}
```

Blog posts include Article schema:

```typescript name=packages/ui/src/ArticleSchema.tsx
interface Props {
  title: string;
  description: string;
  url: string;
  image: string | null;
  author: string;
  publishedAt: string;
  siteName: string;
}

export function ArticleSchema({ title, description, url, image, author, publishedAt, siteName }: Props) {
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: title,
    description,
    url,
    image: image || undefined,
    author: { '@type': 'Person', name: author },
    datePublished: publishedAt,
    publisher: { '@type': 'Organization', name: siteName },
  };

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
    />
  );
}
```

---

## 13. PERFORMANCE REQUIREMENTS

### 13.1 Target Metrics

| Metric | Target | Measurement |
|:---|:---|:---|
| Lighthouse Performance Score | ≥ 95 | Google Lighthouse |
| Time to First Byte (TTFB) | < 50ms | WebPageTest |
| First Contentful Paint (FCP) | < 0.8s | Lighthouse |
| Largest Contentful Paint (LCP) | < 1.2s | Lighthouse |
| Total Blocking Time (TBT) | < 50ms | Lighthouse |
| Cumulative Layout Shift (CLS) | < 0.05 | Lighthouse |
| Total page weight (HTML+CSS+JS) | < 200 KB (excluding images) | DevTools Network |
| Total page weight (with images) | < 1 MB | DevTools Network |

### 13.2 Performance Implementation Rules

| Rule | Implementation |
|:---|:---|
| Self-host all fonts | Download font files into `public/fonts/`. No Google Fonts CDN calls. Use `next/font/local`. |
| Preload critical fonts | `<link rel="preload" as="font">` for heading and body fonts |
| Optimize images | All images served from CMS are cached at Cloudflare edge (cms subdomain proxied). Use `<img loading="lazy">` for below-fold images. |
| Minimal JavaScript | Use Server Components (default in App Router). Only use `'use client'` for interactive elements (contact form, mobile nav toggle). |
| Purge CSS | Tailwind purges unused CSS at build time automatically. Expected CSS size: 5–15 KB. |
| No third-party scripts | No analytics, no chat widgets, no tracking pixels. Add later if needed. |
| Prefetch links | Next.js `<Link>` component auto-prefetches on hover. Use it for all internal links. |
| Compress static assets | Cloudflare Pages auto-applies gzip/brotli compression. No config needed. |

---

## 14. DNS & DOMAIN CONFIGURATION

### 14.1 Cloudflare DNS Records

| Type | Name | Content | Proxy |
|:---|:---|:---|:---|
| A | `zeplow.com` | Cloudflare Pages (auto-configured) | Yes |
| CNAME | `www` | `zeplow.com` | Yes (redirect to apex) |
| CNAME | `narrative` | `zeplow-narrative.pages.dev` | Yes |
| CNAME | `logic` | `zeplow-logic.pages.dev` | Yes |
| A | `cms` | cPanel server IP | **Yes (orange cloud — enables CDN caching for images)** |
| A | `api` | cPanel server IP | **Yes (orange cloud — enables CDN caching for API responses)** |

**Note:** Proxying `cms` and `api` through Cloudflare (orange cloud) means all image requests to `cms.zeplow.com/storage/...` are cached at Cloudflare's edge — free CDN for media files. Also provides free SSL, DDoS protection, and the ability to add Cloudflare Cache Rules for `/storage/*` with long TTLs.

### 14.2 SSL

| Domain | SSL Provider | Notes |
|:---|:---|:---|
| zeplow.com | Cloudflare (auto) | Universal SSL, free |
| narrative.zeplow.com | Cloudflare (auto) | Universal SSL, free |
| logic.zeplow.com | Cloudflare (auto) | Universal SSL, free |
| cms.zeplow.com | Cloudflare (auto, proxied) | Universal SSL, free |
| api.zeplow.com | Cloudflare (auto, proxied) | Universal SSL, free |

---

## 15. ENVIRONMENT VARIABLES

### 15.1 CMS App (.env)

```env name=cms-app/.env
APP_NAME="Zeplow CMS"
APP_ENV=production
APP_KEY=base64:... (generate via php artisan key:generate)
APP_DEBUG=false
APP_URL=https://cms.zeplow.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cms_zeplow
DB_USERNAME=cms_user
DB_PASSWORD=... (strong password)

FILESYSTEM_DISK=public

ZEPLOW_API_URL=https://api.zeplow.com
ZEPLOW_API_KEY=... (64-character random string, shared with API app)

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=... (cPanel SMTP host)
MAIL_PORT=465
MAIL_USERNAME=hello@zeplow.com
MAIL_PASSWORD=... (email password)
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=hello@zeplow.com
MAIL_FROM_NAME="Zeplow"
```

### 15.2 API App (.env)

```env name=api-app/.env
APP_NAME="Zeplow API"
APP_ENV=production
APP_KEY=base64:... (generate via php artisan key:generate)
APP_DEBUG=false
APP_URL=https://api.zeplow.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=api_zeplow
DB_USERNAME=api_user
DB_PASSWORD=... (strong password, different from CMS)

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=array

CF_DEPLOY_HOOK_PARENT=https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/...
CF_DEPLOY_HOOK_NARRATIVE=https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/...
CF_DEPLOY_HOOK_LOGIC=https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/...

MAIL_MAILER=smtp
MAIL_HOST=... (cPanel SMTP host)
MAIL_PORT=465
MAIL_USERNAME=hello@zeplow.com
MAIL_PASSWORD=...
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=hello@zeplow.com
MAIL_FROM_NAME="Zeplow"

CF_BUILD_TOKEN=... (shared secret for build agent rate limit exemption)
CF_TURNSTILE_SECRET_KEY=... (Cloudflare Turnstile secret key, server-side)
```

### 15.3 Frontend (Cloudflare Pages Environment Variables)

| Variable | Value | Used By |
|:---|:---|:---|
| `NEXT_PUBLIC_API_URL` | `https://api.zeplow.com` | All 3 sites |
| `NEXT_PUBLIC_SITE_KEY` | `parent` / `narrative` / `logic` | Site-specific |
| `CF_BUILD_TOKEN` | `... (build agent token)` | Build scripts (rate limit exemption) |
| `NEXT_PUBLIC_CF_TURNSTILE_SITE_KEY` | `... (Cloudflare Turnstile site key)` | Contact form (client-side) |
| `NODE_VERSION` | `18` | Build environment |

---

## 16. THIRD-PARTY SERVICES

| Service | Purpose | Tier | Cost |
|:---|:---|:---|:---|
| **Cloudflare** | DNS, CDN, Pages hosting, image caching for CMS/API | Free | $0 |
| **GitHub** | Code repository (monorepo) | Free (private repo) | $0 |
| **cPanel Hosting** | Laravel CMS + API hosting | Existing plan | $0 (already paid) |
| **Cloudflare Turnstile** | Contact form bot protection | Free | Same Cloudflare account |

**Total monthly cost: $0**

---

## 17. IMPLEMENTATION ORDER

### Phase 1: Infrastructure Setup (Days 1–3)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 1.1 | Create GitHub repository `zeplow-sites` | Private monorepo | Nothing |
| 1.2 | Initialize Turborepo structure | Root package.json, turbo.json, pnpm-workspace.yaml | 1.1 |
| 1.3 | Create `packages/config` | colors.ts, fonts.ts | 1.2 |
| 1.4 | Create `packages/api` | client.ts, types.ts | 1.2 |
| 1.5 | Create `packages/ui` | Empty component stubs (Container, Button, Nav, Footer) | 1.2 |
| 1.6 | Create `apps/parent` | Minimal Next.js app with `output: 'export'`, Tailwind configured | 1.2 |
| 1.7 | Create `apps/narrative` | Same as above | 1.2 |
| 1.8 | Create `apps/logic` | Same as above | 1.2 |
| 1.9 | Set up Cloudflare DNS | Add CNAME/A records for all subdomains (all proxied/orange cloud) | Nothing |
| 1.10 | Create 3 Cloudflare Pages projects | Connect to GitHub repo, configure build commands | 1.1, 1.9 |
| 1.11 | Configure custom domains on Cloudflare Pages | zeplow.com, narrative.zeplow.com, logic.zeplow.com | 1.9, 1.10 |
| 1.12 | Deploy "Hello World" to all 3 domains | Verify build + deploy pipeline works | 1.6–1.11 |

### Phase 2: CMS App (Days 4–8)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 2.1 | Create MySQL database `cms_zeplow` on cPanel | Via cPanel MySQL interface | Nothing |
| 2.2 | Install fresh Laravel 11 project for CMS | `composer create-project laravel/laravel cms-app` | Nothing |
| 2.3 | Install Filament v3 | `composer require filament/filament` + `php artisan filament:install` | 2.2 |
| 2.4 | Install Spatie MediaLibrary + Filament plugin | Both packages + publish migrations | 2.3 |
| 2.5 | Create all database migrations | sites, pages, projects, blog_posts, testimonials, team_members, site_configs, sync_logs | 2.2 |
| 2.6 | Run migrations | `php artisan migrate` | 2.1, 2.5 |
| 2.7 | Create Eloquent models | Site, Page, Project, BlogPost, Testimonial, TeamMember, SiteConfig, SyncLog | 2.5 |
| 2.8 | Define model relationships | Site hasMany Pages, Projects, etc. | 2.7 |
| 2.9 | Create Filament resources | All 7 resources with forms, tables, filters | 2.3, 2.7 |
| 2.10 | Create seed data | 3 sites + site configs with nav/footer data | 2.7 |
| 2.11 | Run seeders | `php artisan db:seed` | 2.6, 2.10 |
| 2.12 | Create admin users | Shakib (super_admin) + Shadman (admin) via `php artisan make:filament-user` | 2.6 |
| 2.13 | Create SyncService + all Jobs | SyncContentJob, SyncConfigJob, DeleteContentJob, SyncService | 2.7 |
| 2.14 | Create all Observers | Page, Project, BlogPost, Testimonial, TeamMember, SiteConfig observers | 2.7, 2.13 |
| 2.15 | Register Observers in AppServiceProvider | Boot method registration | 2.14 |
| 2.16 | Create Filament dashboard widgets | Content Overview, Last Deploy Status, Quick Actions | 2.9 |
| 2.17 | Create Resync All action | Filament custom action | 2.13 |
| 2.18 | Deploy CMS to cPanel | Upload via Git or FTP, configure .env, run migrations | 2.1–2.17 |
| 2.19 | Configure Cloudflare proxy for cms.zeplow.com | Verify orange cloud is on, SSL works | 2.18 |
| 2.20 | Test: Login, create content, verify Filament works | Manual testing | 2.18 |

### Phase 3: API App (Days 9–12)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 3.1 | Create MySQL database `api_zeplow` on cPanel | Via cPanel MySQL interface | Nothing |
| 3.2 | Install fresh Laravel 11 project for API | Lean setup, remove unnecessary providers | Nothing |
| 3.3 | Install Sanctum | `composer require laravel/sanctum` | 3.2 |
| 3.4 | Create all database migrations | site_content, site_configs, contact_submissions, deploy_logs, api_keys | 3.2 |
| 3.5 | Run migrations | `php artisan migrate` | 3.1, 3.4 |
| 3.6 | Create Eloquent models | SiteContent, SiteConfig, ContactSubmission, DeployLog, ApiKey | 3.4 |
| 3.7 | Create ValidateApiKey middleware | Bearer token validation (compares SHA-256 hash) | 3.6 |
| 3.7b | Create ValidateSiteKey middleware | Whitelist check for `{siteKey}` route parameter | 3.2 |
| 3.8 | Create DeployService + CacheService | Cloudflare deploy hook trigger with logging + prefix-based cache flush | 3.6 |
| 3.9 | Create ContentSyncController | Internal endpoint: receive, store, invalidate cache (with TYPE_TO_CACHE_PREFIX mapping + prefix-based list flush), trigger deploy | 3.6, 3.7, 3.8 |
| 3.10 | Create ConfigSyncController | Internal endpoint: receive and store site configs | 3.6, 3.7 |
| 3.11 | Create SitePageController | Public endpoint: list pages, show page (with caching) | 3.6 |
| 3.12 | Create SiteProjectController | Public endpoint: list projects (with featured/limit filters + pagination), show project | 3.6 |
| 3.13 | Create SiteBlogController | Public endpoint: list posts (with tag/limit filters + pagination), show post | 3.6 |
| 3.14 | Create SiteTestimonialController | Public endpoint: list testimonials | 3.6 |
| 3.15 | Create SiteTeamController | Public endpoint: list team members | 3.6 |
| 3.16 | Create SiteConfigController | Public endpoint: show site config | 3.6 |
| 3.17 | Create ContactController | Public endpoint: receive form submission (with honeypot), store, send email | 3.6 |
| 3.18 | Create HealthController | Health check endpoint | 3.2 |
| 3.19 | Define all routes | routes/api.php with public and internal groups | 3.9–3.18 |
| 3.20 | Configure CORS | Allow frontends + localhost, allow GET + POST | 3.2 |
| 3.21 | Configure rate limiting | 60 requests/minute on public endpoints | 3.2 |
| 3.22 | Generate API key via `php artisan api-key:generate` | Key shown once, SHA-256 hash stored in api_keys table, plaintext copied to CMS .env | 3.6 |
| 3.23 | Deploy API to cPanel | Upload, configure .env, run migrations | 3.1–3.22 |
| 3.24 | Configure Cloudflare proxy for api.zeplow.com | Verify orange cloud is on, SSL works | 3.23 |
| 3.25 | Test: CMS publish → API receives → content stored | End-to-end sync test | 2.18, 3.23 |
| 3.26 | Test: Public API endpoints return correct JSON | curl/Postman testing | 3.23 |
| 3.27 | Get Cloudflare deploy hook URLs | From Cloudflare Pages dashboard | 1.10 |
| 3.28 | Add deploy hook URLs to API .env | CF_DEPLOY_HOOK_PARENT, etc. | 3.27 |
| 3.29 | Test: Content sync → deploy hook fires → Cloudflare rebuilds | Full pipeline test | 3.25, 3.28 |
| 3.30 | Create ResolveBuildAgent middleware | Validates `CF_BUILD_TOKEN`, applies elevated rate limit tier | 3.2 |
| 3.31 | Add build token rate limit tier | 300 req/min for build agents vs 60 req/min for public | 3.21, 3.30 |
| 3.32 | Add Turnstile verification to ContactController | Server-side `cf_turnstile_response` validation via Cloudflare API | 3.17 |

### Phase 4: Frontend Data Layer (Days 13–15)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 4.1 | Implement API client | `packages/api/src/client.ts` with all fetch functions | 3.26 |
| 4.2 | Implement TypeScript types | `packages/api/src/types.ts` matching all API responses (including PaginatedResponse) | 3.26 |
| 4.3 | Create ContentRenderer component | Block type → component mapping (stub implementations) | 4.2 |
| 4.4 | Create shared layout components | Navigation (data-driven), Footer (data-driven) — structural only, no design | 4.2 |
| 4.5 | Create SEO component | Metadata generation from API data | 4.2 |
| 4.6 | Create ContactForm component | Client component with form submission to API + honeypot | 4.1 |
| 4.7 | Create sitemap generators | sitemap.ts for each site | 4.1 |
| 4.8 | Create robots.txt files | One per site | Nothing |
| 4.9 | Create `_headers` files | Security headers for Cloudflare | Nothing |
| 4.10 | Wire up all pages in `apps/parent` | All 8 routes fetching data from API | 4.1–4.5 |
| 4.11 | Wire up all pages in `apps/narrative` | All 8 routes fetching data from API | 4.1–4.5 |
| 4.12 | Wire up all pages in `apps/logic` | All 8 routes fetching data from API | 4.1–4.5 |
| 4.13 | Create `generateStaticParams` for all dynamic routes | Blog [slug], Project [slug] for each site | 4.10–4.12 |
| 4.14 | Create JSON-LD schema components | OrganizationSchema, ArticleSchema | Nothing |
| 4.15 | Test: Local build succeeds for all 3 sites | `pnpm build:all` | 4.10–4.13 |
| 4.16 | Test: Deploy to Cloudflare Pages works | Push to GitHub, verify all 3 sites build and deploy | 4.15 |

### Phase 5: Content Seeding (Days 16–17)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 5.1 | Seed all page content for parent site | Home, About, Ventures (x3), Insights, Careers, Contact | 4.16, 2.18 |
| 5.2 | Seed all page content for Narrative site | Home, About, Services, Work, Process, Insights, Contact | 4.16, 2.18 |
| 5.3 | Seed all page content for Logic site | Home, About, Services, Work, Process, Insights, Contact | 4.16, 2.18 |
| 5.4 | Seed all 17 projects (Logic site) | From company profile document | 2.18 |
| 5.5 | Seed team members (all sites) | Shadman + Shakib | 2.18 |
| 5.6 | Seed site configs (all sites) | Navigation, footer, CTAs, social links | 2.18 |
| 5.7 | Verify all content synced to API | Check api_zeplow database | 5.1–5.6 |
| 5.8 | Trigger deploy for all 3 sites | Via Resync All in Filament | 5.7 |
| 5.9 | Verify all 3 sites show content | Visit each domain | 5.8 |

### Phase 6: Frontend Design & Polish (Days 18–30)

*This phase is intentionally left open — frontend design will be done separately. The data layer, API, CMS, and build pipeline will all be complete and functional by the end of Phase 5. Phase 6 is purely visual implementation.*

| # | Task | Details |
|:---|:---|:---|
| 6.1 | Design and implement all block components | Hero, Text, Cards, CTA, Gallery, Stats, etc. |
| 6.2 | Design and implement Navigation component | Per-site visual treatment |
| 6.3 | Design and implement Footer component | Per-site visual treatment |
| 6.4 | Design and implement page layouts | Per-site templates |
| 6.5 | Design and implement project detail pages | Feature Story (Narrative) vs Incident Report (Logic) |
| 6.6 | Design and implement blog listing and detail | Consistent across sites |
| 6.7 | Add Framer Motion animations | Page transitions, scroll reveals |
| 6.8 | Implement self-hosted fonts | Download, configure next/font/local |
| 6.9 | Lighthouse audit | Target 95+ on all pages |
| 6.10 | Cross-browser testing | Chrome, Firefox, Safari, mobile |
| 6.11 | Final QA | All links, all forms, all images |
| 6.12 | Create `scripts/test-api.sh` automated test script | Automated curl-based API endpoint verification |
| 6.13 | Run automated tests against production | Execute `scripts/test-api.sh` and verify all endpoints pass |

---

## 18. TESTING CHECKLIST

### 18.1 CMS Testing

| Test | Expected Result | Pass |
|:---|:---|:---|
| Login as Shakib (super_admin) | Access to all Filament resources + site creation | ☐ |
| Login as Shadman (admin) | Access to all content resources, no site creation/deletion | ☐ |
| Create a new page for Narrative site | Page saved in cms_zeplow DB | ☐ |
| Publish the page | Observer dispatches job, sync_logs shows "success" | ☐ |
| Check API received the content | api_zeplow.site_content has the record | ☐ |
| Upload an image to a project | Image saved in storage, URL accessible via Cloudflare CDN | ☐ |
| Edit and re-publish a page | API receives updated content, cache invalidated | ☐ |
| Delete a project | API receives delete command, content removed | ☐ |
| Use "Resync All Content" action | All published content re-sent to API | ☐ |
| API is down during publish | Sync job fails after 3 retries, sync_logs shows "failed" (single sync_log entry, not one per retry) | ☐ |
| Unpublish a team member | Observer triggers deleteContent, team member removed from API | ☐ |
| Resync All only syncs published team members | Unpublished team members are not sent to API | ☐ |
| Unpublish a published page | Observer triggers deleteContent, page removed from API | ☐ |
| Unpublish a published project | Observer triggers deleteContent, project removed from API | ☐ |
| Unpublish a published blog post | Observer triggers deleteContent, blog post removed from API | ☐ |

### 18.2 API Testing

| Test | Expected Result | Pass |
|:---|:---|:---|
| GET /health | Returns `{"status": "ok"}` with 200 | ☐ |
| GET /sites/v1/narrative/pages | Returns list of published pages for Narrative | ☐ |
| GET /sites/v1/narrative/pages/about | Returns full page content with blocks | ☐ |
| GET /sites/v1/logic/projects?featured=true | Returns only featured projects | ☐ |
| GET /sites/v1/logic/projects (no limit) | Returns paginated response with data + meta | ☐ |
| GET /sites/v1/parent/blog | Returns published blog posts | ☐ |
| GET /sites/v1/narrative/config | Returns nav, footer, CTA, socials | ☐ |
| GET /sites/v1/nonexistent/pages | Returns 404 (ValidateSiteKey middleware rejects before DB query) | ☐ |
| GET /sites/v1/garbage123/config | Returns 404 immediately, no cache entry created | ☐ |
| POST /internal/v1/content/sync without API key | Returns 401 | ☐ |
| POST /internal/v1/content/sync with invalid key | Returns 403 | ☐ |
| POST /internal/v1/content/sync with valid key | Returns 200, content stored | ☐ |
| POST /sites/v1/narrative/contact with valid data | Returns 200, email sent, stored in DB | ☐ |
| POST /sites/v1/narrative/contact with missing fields | Returns 422 with validation errors | ☐ |
| POST /sites/v1/narrative/contact with honeypot filled | Returns fake 200, nothing stored | ☐ |
| Hit rate limit (60+ requests/minute) | Returns 429 | ☐ |
| Same endpoint called twice within 1 hour | Second response served from cache (faster) | ☐ |
| Sync blog_post content type → check cache key | Cache key uses "blog" prefix, not "blog_posts" | ☐ |
| Sync project → verify parameterized list caches are cleared | Cache keys like `list:true:3:1:50` must be flushed, not just the base `list` key | ☐ |
| Verify API key is stored as SHA-256 hash in api_keys table | `key_hash` column contains a 64-char hex string, no plaintext key column exists | ☐ |
| Request with valid build token at 60-300 req/min | No 429 | ☐ |
| Contact form without Turnstile token | Fake 200, nothing stored | ☐ |

### 18.3 Frontend Testing

| Test | Expected Result | Pass |
|:---|:---|:---|
| `pnpm build:parent` succeeds | Static files in `apps/parent/out/` | ☐ |
| `pnpm build:narrative` succeeds | Static files in `apps/narrative/out/` | ☐ |
| `pnpm build:logic` succeeds | Static files in `apps/logic/out/` | ☐ |
| zeplow.com loads | Shows parent homepage | ☐ |
| narrative.zeplow.com loads | Shows narrative homepage | ☐ |
| logic.zeplow.com loads | Shows logic homepage | ☐ |
| All internal links work | No 404s on any site | ☐ |
| All project detail pages load | /work/[slug] renders correctly | ☐ |
| All blog post pages load | /insights/[slug] renders correctly | ☐ |
| Contact form submits successfully | API receives submission, user sees success message | ☐ |
| Contact form shows validation errors | Missing required fields highlighted | ☐ |
| Contact form honeypot works | Bot-filled form shows fake success, nothing stored | ☐ |
| View page source: meta tags present | title, description, og tags, canonical | ☐ |
| /sitemap.xml accessible | Valid XML with all pages listed | ☐ |
| /robots.txt accessible | Correct content | ☐ |
| HTTPS enforced | HTTP redirects to HTTPS | ☐ |
| Images from cms.zeplow.com load fast | Served via Cloudflare CDN (check cf-cache-status header) | ☐ |

### 18.4 End-to-End Pipeline Testing

| Test | Expected Result | Pass |
|:---|:---|:---|
| Publish new blog post in CMS | Post appears on live site within 2 minutes | ☐ |
| Edit existing page in CMS | Changes reflected on live site within 2 minutes | ☐ |
| Delete a project in CMS | Project removed from live site after next build | ☐ |
| Change navigation in Site Config | Nav updates on live site after rebuild | ☐ |
| CMS server goes down | Live sites continue working (static files on CDN) | ☐ |
| API server goes down | Live sites continue working (already built). New builds fail gracefully. | ☐ |
| Sync 2 items for same site within 10 seconds | Only 1 deploy hook fires | ☐ |

---

## 19. POST-LAUNCH CHECKLIST

| # | Task | When |
|:---|:---|:---|
| 1 | Verify all 3 sites load correctly on mobile + desktop | Day 1 |
| 2 | Submit all 3 sitemaps to Google Search Console | Day 1 |
| 3 | Verify Cloudflare SSL is active on all domains (including cms + api) | Day 1 |
| 4 | Verify Cloudflare proxy is active for cms.zeplow.com (orange cloud, cf-cache-status header on images) | Day 1 |
| 5 | Verify Cloudflare proxy is active for api.zeplow.com (orange cloud) | Day 1 |
| 6 | Run Lighthouse audit on all pages | Day 1 |
| 7 | Test contact form end-to-end (submit → email received) | Day 1 |
| 8 | Set up Cloudflare Pages email notifications for failed builds | Day 1 |
| 9 | Set up database backup strategy (see details below) | Day 1 (then weekly) |
| 10 | Document the API key in a secure location (not in Git) | Day 1 |
| 11 | Test "Resync All" recovery after simulated failure | Day 2 |
| 12 | Monitor API response times for first week | Week 1 |
| 13 | Monitor Cloudflare build times for first week | Week 1 |
| 14 | Check sync_logs for any failed syncs | Weekly |
| 15 | Check deploy_logs for any failed deploys | Weekly |
| 16 | Add Cloudflare Cache Rule for `cms.zeplow.com/storage/*` with 30-day TTL | Day 1 |
| 17 | Set up UptimeRobot health check monitor (see Section 11.6) | Day 1 |
| 18 | Set up weekly monitoring cron jobs (see Section 11.6) | Day 1 |
| 19 | Create Cloudflare Turnstile widget, add keys to env | Day 1 |
| 20 | Run `scripts/test-api.sh` against production | Day 1, then after every deployment |

### 19.1 Database Backup Strategy

**Critical note:** The API database (`api_zeplow`) is fully reconstructable from the CMS using the "Resync All" action — the CMS database (`cms_zeplow`) is the critical one to back up. Both should be backed up, but `cms_zeplow` is the priority.

**Backup methods (use both):**

| Method | Tool | Frequency | Retention |
|:---|:---|:---|:---|
| Full database backup | cPanel's built-in Backup Wizard | Weekly (manual or scheduled) | Per cPanel retention policy |
| Automated mysqldump | Cron job (see below) | Weekly | Last 4 backups (rolling) |

**Automated backup cron job:**

```bash
# Add to cPanel Cron Jobs (runs every Sunday at 2 AM server time)
# Backs up both databases to a directory OUTSIDE the web root

# CMS database backup (CRITICAL — this is the source of truth)
0 2 * * 0 mysqldump -u cms_user -p'YOUR_PASSWORD' cms_zeplow | gzip > /home/cpanel_user/backups/cms_zeplow_$(date +\%Y\%m\%d).sql.gz

# API database backup (reconstructable, but backup saves time)
5 2 * * 0 mysqldump -u api_user -p'YOUR_PASSWORD' api_zeplow | gzip > /home/cpanel_user/backups/api_zeplow_$(date +\%Y\%m\%d).sql.gz

# Clean up backups older than 28 days (keep last 4 weekly backups)
10 2 * * 0 find /home/cpanel_user/backups/ -name "*.sql.gz" -mtime +28 -delete
```

**Important:**
- The `/home/cpanel_user/backups/` directory must be created manually and must be **outside** the `public_html` directory (not web-accessible)
- Replace `cpanel_user` with the actual cPanel username
- Replace `YOUR_PASSWORD` with the actual database passwords
- Test the restore process at least once: `gunzip < backup.sql.gz | mysql -u user -p database_name`
- For disaster recovery: restore `cms_zeplow` from backup, then use "Resync All" to repopulate `api_zeplow`