# ZEPLOW CMS — PRODUCT REQUIREMENTS DOCUMENT (PRD)

**Version:** 1.3
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
5. Eloquent Models & Relationships
6. Filament Resources — Complete Specifications
7. Content Block System
8. Filament Dashboard Widgets
9. Filament Users, Roles & Permissions
10. Media Storage & Image Handling
11. Sync System — Architecture
12. Sync Jobs
13. Sync Service
14. Model Observers
15. Manual Resync (Filament Action)
16. Authentication & Security
17. Error Handling & Logging
18. Seed Data
19. Environment Variables
20. DNS & SSL
21. cPanel Deployment
22. Directory Structure
23. Implementation Order
24. Testing Checklist
25. Post-Launch Checklist

---

## 1. APPLICATION OVERVIEW

### 1.1 What This App Is

The Zeplow CMS is a **Laravel 11 + Filament v3 admin panel** where two co-founders manage all content for three public websites. It is the single source of truth for every page, project, blog post, testimonial, team member, and site configuration across the Zeplow platform.

It is not a public-facing application. It has no frontend for visitors. Its only users are two admin accounts. Its only output is synced data sent to the API app.

### 1.2 Properties

| Property | Value |
|:---|:---|
| Framework | Laravel 11 |
| Admin Panel | Filament v3 |
| Database | MySQL — `cms_zeplow` |
| Hosting | cPanel shared hosting (existing plan) |
| URL | `https://cms.zeplow.com` |
| PHP Version | 8.2+ |
| Users | 2 admin users (Shakib + Shadman) |
| Purpose | Content creation, management, and sync to API |
| Budget | $0/month additional |

### 1.3 What This App Does

1. Provides a Filament admin panel for managing content across 3 sites
2. Stores all content in its own MySQL database (`cms_zeplow`)
3. On every publish/edit/delete, syncs content to the API app (`api.zeplow.com`) via HTTP
4. Manages media uploads (images, files) via Spatie MediaLibrary
5. Tracks sync status in a `sync_logs` table
6. Provides a "Resync All" action for manual recovery

### 1.4 Sites Managed

The CMS manages content for three distinct websites. Each is represented by a `Site` record:

| Site | key | Domain | Description |
|:---|:---|:---|:---|
| Zeplow | `parent` | zeplow.com | Authority, group overview, venture hub |
| Zeplow Narrative | `narrative` | narrative.zeplow.com | Creative agency — brand storytelling services |
| Zeplow Logic | `logic` | logic.zeplow.com | Tech company — automation & dev services |

### 1.5 Non-Goals (Explicitly Out of Scope)

- Public-facing frontend or visitor UI
- API endpoints for frontends (handled by the API app)
- Client portal / dashboard
- Multi-language support
- Analytics dashboard in Filament
- Newsletter / email capture
- Live chat
- Direct deployment to Cloudflare (handled by the API app's deploy hooks)

---

## 2. SYSTEM CONTEXT & DATA FLOW

### 2.1 Where the CMS Sits in the Architecture

```
┌──────────────────────────────────────────────────────────┐
│  THIS APP: CMS (cms.zeplow.com)                           │
│  Laravel 11 + Filament v3                                 │
│  MySQL DB: cms_zeplow                                     │
│                                                           │
│  Editors manage content here.                             │
│  On publish → Observer dispatches Job →                   │
│  HTTP POST to API app.                                    │
│                                                           │
│  Media files stored locally at cms.zeplow.com/storage/    │
│  Served globally via Cloudflare CDN (orange cloud proxy)  │
└──────────────────────┬────────────────────────────────────┘
                       │
                       │  POST api.zeplow.com/internal/v1/content/sync
                       │  POST api.zeplow.com/internal/v1/config/sync
                       │  DELETE api.zeplow.com/internal/v1/content/sync
                       │  (Authenticated via shared Bearer API key)
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│  API APP (api.zeplow.com)                                 │
│  Laravel 11 (API-only)                                    │
│  MySQL DB: api_zeplow                                     │
│                                                           │
│  Receives content from CMS → stores in own DB.            │
│  Serves public REST API for frontends.                    │
│  On content sync → fires Cloudflare deploy hook.          │
└──────────────────────┬────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────┐
│  CLOUDFLARE PAGES (Free Tier)                             │
│  3 Next.js sites fetch data from API at build time        │
│  Output: pure static HTML/CSS/JS on global CDN            │
└──────────────────────────────────────────────────────────┘
```

### 2.2 Data Flow — Content Publish Cycle

```
Step 1:  Editor logs into cms.zeplow.com (Filament admin panel)
Step 2:  Editor creates or edits content (page, project, blog post, etc.)
Step 3:  Editor clicks "Publish" (sets is_published = true) and saves
Step 4:  Laravel Observer on the model detects the save event
Step 5:  Observer calls SyncService, which dispatches a SyncContentJob
         - With QUEUE_CONNECTION=sync: job executes immediately (synchronous)
         - With database/redis queue (future): job executes asynchronously
         - Payload includes: site_key, content_type, slug, full data payload
         - Authenticated via API key in Authorization header
         - Retry: 3 attempts with 5-second backoff between each
Step 6:  SyncContentJob sends HTTP POST to api.zeplow.com/internal/v1/content/sync
Step 7:  API app validates payload, upserts content, invalidates cache, fires deploy hook
Step 8:  Sync result logged in cms_zeplow.sync_logs table (success or failed)
Step 9:  Cloudflare Pages rebuilds the affected frontend site (~60-90 seconds)
Step 10: Live site updated with new content
```

### 2.3 Data Flow — Content Deletion

```
Step 1:  Editor deletes a project/page/post in Filament
Step 2:  Observer detects the delete event
Step 3:  Observer calls SyncService, which dispatches a DeleteContentJob
Step 4:  DeleteContentJob sends HTTP DELETE to api.zeplow.com/internal/v1/content/sync
Step 5:  API removes content, invalidates cache, fires deploy hook
Step 6:  Content removed from live site after rebuild
```

### 2.4 Data Flow — Config Change

```
Step 1:  Editor updates navigation, footer, or CTA in SiteConfigResource
Step 2:  SiteConfigObserver calls SyncService, which dispatches SyncConfigJob
Step 3:  SyncConfigJob sends HTTP POST to api.zeplow.com/internal/v1/config/sync
Step 4:  API stores updated config, invalidates cache, fires deploy hook
Step 5:  Nav/footer/CTA updates on live site after rebuild
```

---

## 3. LARAVEL CONFIGURATION & PROJECT SETUP

### 3.1 Installation

```bash
composer create-project laravel/laravel cms-app
cd cms-app
composer require filament/filament "^3.0"
php artisan filament:install --panels
composer require spatie/laravel-medialibrary "^11.0"
composer require filament/spatie-laravel-media-library-plugin "^3.0"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

### 3.2 Packages Required

| Package | Purpose | Version |
|:---|:---|:---|
| `filament/filament` | Admin panel framework | ^3.0 |
| `filament/spatie-laravel-media-library-plugin` | Image/file uploads in Filament forms | ^3.0 |
| `spatie/laravel-medialibrary` | Media management (images, files, conversions) | ^11.0 |
| `guzzlehttp/guzzle` | HTTP client for API sync | ^7.0 (included in Laravel) |

**No other packages.** Keep the CMS lean.

### 3.3 Key Config Settings

| Setting | Value | Reason |
|:---|:---|:---|
| `SESSION_DRIVER` | `file` | cPanel compatible, no Redis needed |
| `CACHE_DRIVER` | `file` | cPanel compatible |
| `QUEUE_CONNECTION` | `sync` | Jobs execute immediately. Switch to `database` later for async without code changes. |
| `FILESYSTEM_DISK` | `public` | Media files stored in `storage/app/public/`, symlinked to `public/storage/` |

### 3.4 Services Config

```php
// config/services.php — add this section

'zeplow_api' => [
    'url' => env('ZEPLOW_API_URL', 'https://api.zeplow.com'),
    'key' => env('ZEPLOW_API_KEY'),
],
```

---

## 4. DATABASE SCHEMA

### 4.1 Database Name

`cms_zeplow` — created on cPanel shared hosting.

### 4.2 Complete Schema

```sql
-- ============================================
-- DATABASE: cms_zeplow
-- Used by: CMS Laravel app (cms.zeplow.com)
-- ============================================

-- Users (Filament auth)
-- Two admin users: Shakib (super_admin) and Shadman (admin)
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
-- The 3 website properties managed by this CMS.
-- Each content record belongs to exactly one site.
CREATE TABLE sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,                 -- "Zeplow", "Zeplow Narrative", "Zeplow Logic"
    `key` VARCHAR(50) NOT NULL UNIQUE,          -- "parent", "narrative", "logic"
    domain VARCHAR(255) NOT NULL,               -- "zeplow.com", "narrative.zeplow.com", etc.
    tagline VARCHAR(255) NULL,                  -- "Story. Systems. Ventures."
    description TEXT NULL,                      -- Short site description
    seo_defaults JSON NULL,                     -- Default meta_title, meta_description, og_image
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Pages
-- Static pages (Home, About, Services, etc.) per site.
-- Content is a JSON array of content blocks (see Section 7).
CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,                 -- Auto-generated from title, editable
    template ENUM('home', 'about', 'services', 'work', 'process', 'insights',
                  'contact', 'ventures', 'careers', 'default') NOT NULL DEFAULT 'default',
    content JSON NULL,                          -- Array of content blocks
    seo_title VARCHAR(255) NULL,                -- Override page title for SEO
    seo_description VARCHAR(500) NULL,          -- Meta description
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    sort_order INT NOT NULL DEFAULT 0,          -- Page ordering in navigation
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_slug (site_id, slug)
);

-- Projects
-- Portfolio/case study items per site.
-- Images stored via Spatie MediaLibrary (separate media table).
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    one_liner VARCHAR(500) NOT NULL,            -- Short description for list views
    client_name VARCHAR(255) NULL,
    industry VARCHAR(255) NULL,                 -- "EdTech", "Logistics", etc.
    url VARCHAR(500) NULL,                      -- Live project URL
    challenge TEXT NULL,                         -- Problem/bottleneck (case study)
    solution TEXT NULL,                          -- What was built/delivered
    outcome TEXT NULL,                           -- Results/metrics
    tech_stack JSON NULL,                       -- ["Next.js", "Python", "PostgreSQL"]
    images JSON NULL,                            -- Fallback; primary images via Spatie MediaLibrary
    tags JSON NULL,                             -- ["branding", "web", "automation"]
    featured BOOLEAN NOT NULL DEFAULT FALSE,    -- Show on homepage
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_project_slug (site_id, slug)
);

-- Blog Posts
-- Blog/insights articles per site.
-- Body stored as HTML (from Filament RichEditor).
CREATE TABLE blog_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,                           -- Short preview text for listings
    body LONGTEXT NOT NULL,                     -- Full article content (HTML)
    cover_image VARCHAR(500) NULL,              -- Fallback; primary via Spatie MediaLibrary
    tags JSON NULL,                             -- ["branding", "automation", "strategy"]
    author VARCHAR(255) NULL,                   -- Author display name
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
-- Client quotes per site.
CREATE TABLE testimonials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,                 -- Person's name
    role VARCHAR(255) NULL,                     -- "CEO", "Founder"
    company VARCHAR(255) NULL,
    quote TEXT NOT NULL,                         -- The testimonial text
    avatar VARCHAR(500) NULL,                   -- Fallback; primary via Spatie MediaLibrary
    is_published BOOLEAN NOT NULL DEFAULT TRUE,  -- Default published (unlike other content)
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Team Members
-- Founder/team bios per site.
CREATE TABLE team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,                 -- "Co-Founder & CEO"
    bio TEXT NULL,                               -- Short biography
    photo VARCHAR(500) NULL,                    -- Fallback; primary via Spatie MediaLibrary
    linkedin VARCHAR(500) NULL,
    email VARCHAR(255) NULL,
    is_founder BOOLEAN NOT NULL DEFAULT FALSE,  -- Distinguish founders from team
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Site Configs
-- Per-site configuration: navigation, footer, CTA, social links.
-- One config per site (unique on site_id).
CREATE TABLE site_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL UNIQUE,
    nav_items JSON NOT NULL,                    -- Array of {label, url, is_external}
    footer_links JSON NULL,                     -- Array of {group_title, links: [{label, url}]}
    footer_text VARCHAR(255) NULL,              -- "© 2026 Zeplow LLC."
    cta_text VARCHAR(255) NULL,                 -- "Book a Heartbeat Review"
    cta_url VARCHAR(255) NULL,                  -- "/contact"
    social_links JSON NULL,                     -- {linkedin: "...", instagram: "...", whatsapp: "..."}
    contact_email VARCHAR(255) NULL,
    contact_phone VARCHAR(50) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Sync Log
-- Tracks every content sync attempt to the API.
-- Used for monitoring and debugging sync failures.
CREATE TABLE sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_key VARCHAR(50) NOT NULL,              -- "parent", "narrative", "logic"
    content_type VARCHAR(50) NOT NULL,          -- "page", "project", "blog_post", etc.
    content_slug VARCHAR(255) NOT NULL,         -- The slug of the content item
    status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
    attempt_count INT NOT NULL DEFAULT 0,       -- Number of attempts made
    last_error TEXT NULL,                        -- Error message from last failed attempt
    synced_at TIMESTAMP NULL,                   -- When sync succeeded
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_site_type (site_key, content_type)
);

-- Spatie Media Library table
-- Auto-created by running:
-- php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
-- php artisan migrate
--
-- This table stores metadata for all uploaded images/files.
-- Actual files are stored in storage/app/public/ (symlinked to public/storage/).
```

---

## 5. ELOQUENT MODELS & RELATIONSHIPS

### 5.1 Site

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    protected $fillable = [
        'name', 'key', 'domain', 'tagline', 'description', 'seo_defaults',
    ];

    protected $casts = [
        'seo_defaults' => 'array',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function config(): HasOne
    {
        return $this->hasOne(SiteConfig::class);
    }
}
```

### 5.2 Page

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'site_id', 'title', 'slug', 'template', 'content',
        'seo_title', 'seo_description', 'is_published', 'published_at', 'sort_order',
    ];

    protected $casts = [
        'content'      => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('og_image')->singleFile();
    }
}
```

### 5.3 Project

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Project extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'site_id', 'title', 'slug', 'one_liner', 'client_name', 'industry',
        'url', 'challenge', 'solution', 'outcome', 'tech_stack', 'images',
        'tags', 'featured', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'tech_stack'   => 'array',
        'images'       => 'array',
        'tags'         => 'array',
        'featured'     => 'boolean',
        'is_published' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(300)->height(300)->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(800)->height(600);

        $this->addMediaConversion('large')
            ->width(1920)->height(1080);
    }
}
```

### 5.4 BlogPost

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BlogPost extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'blog_posts';

    protected $fillable = [
        'site_id', 'title', 'slug', 'excerpt', 'body', 'cover_image',
        'tags', 'author', 'seo_title', 'seo_description', 'is_published', 'published_at',
    ];

    protected $casts = [
        'tags'         => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_image')->singleFile();
    }
}
```

### 5.5 Testimonial

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Testimonial extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'site_id', 'name', 'role', 'company', 'quote', 'avatar',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }
}
```

### 5.6 TeamMember

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TeamMember extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'team_members';

    protected $fillable = [
        'site_id', 'name', 'role', 'bio', 'photo', 'linkedin',
        'email', 'is_founder', 'sort_order',
    ];

    protected $casts = [
        'is_founder' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }
}
```

### 5.7 SiteConfig

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteConfig extends Model
{
    protected $table = 'site_configs';

    protected $fillable = [
        'site_id', 'nav_items', 'footer_links', 'footer_text',
        'cta_text', 'cta_url', 'social_links', 'contact_email', 'contact_phone',
    ];

    protected $casts = [
        'nav_items'    => 'array',
        'footer_links' => 'array',
        'social_links' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
```

### 5.8 SyncLog

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'sync_logs';

    protected $fillable = [
        'site_key', 'content_type', 'content_slug',
        'status', 'attempt_count', 'last_error', 'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
```

### 5.9 User (Modified for Roles)

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Both roles can access the admin panel
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

### 5.10 Known Limitation — No Soft Deletes

No models use soft deletes in V1. When content is deleted in Filament, it is permanently removed from the CMS database and a delete sync is sent to the API. There is no undo mechanism. For V2, consider adding a `content_versions` table or soft deletes with a 30-day retention policy.

---

## 6. FILAMENT RESOURCES — COMPLETE SPECIFICATIONS

### 6.1 SiteResource

**Purpose:** Manage the 3 website properties.
**Permissions:** Only `super_admin` can create/delete sites. Both roles can edit.
**Sidebar Section:** Settings

**Form Fields:**

| Field | Filament Type | Validation | Notes |
|:---|:---|:---|:---|
| `name` | TextInput | required, max:255 | "Zeplow", "Zeplow Narrative", "Zeplow Logic" |
| `key` | TextInput | required, unique, max:50, alpha_dash | "parent", "narrative", "logic" |
| `domain` | TextInput | required, max:255 | "zeplow.com", "narrative.zeplow.com", etc. |
| `tagline` | TextInput | nullable, max:255 | "Story. Systems. Ventures." |
| `description` | Textarea | nullable | Short site description |
| `seo_defaults` | KeyValue | nullable | Default meta_title, meta_description, og_image |

**Table Columns:** name, key, domain, updated_at
**Table Actions:** Edit (both roles), Delete (super_admin only)
**Header Actions:** Create (super_admin only)

---

### 6.2 PageResource

**Purpose:** Manage static pages (Home, About, Services, etc.) per site.
**Sidebar Section:** Content

**Form Fields:**

| Field | Filament Type | Validation | Notes |
|:---|:---|:---|:---|
| `site_id` | Select (relationship) | required | Dropdown of sites |
| `title` | TextInput | required, max:255 | "About Us", "Our Services" |
| `slug` | TextInput | required, max:255, unique per site | Auto-generated from title, editable |
| `template` | Select | required | Options: "home", "about", "services", "work", "process", "insights", "contact", "ventures", "careers", "default" |
| `content` | Repeater (content blocks) | nullable | See Section 7 — Content Block System |
| `seo_title` | TextInput | nullable, max:255 | Override page title for SEO |
| `seo_description` | Textarea | nullable, max:500 | Meta description |
| `og_image` | SpatieMediaLibraryFileUpload | nullable | Open Graph image (single file) |
| `is_published` | Toggle | default: false | Controls visibility on frontend |
| `published_at` | DateTimePicker | nullable | Schedule publishing |
| `sort_order` | TextInput (numeric) | default: 0 | Page ordering in navigation |

**Table Columns:** title, site.name, template, is_published, sort_order, updated_at
**Table Filters:** site_id, is_published, template

**Slug Auto-Generation:** When `title` changes and `slug` is empty or matches the previous auto-generated value, auto-generate slug from title using Laravel's `Str::slug()`. Always allow manual override.

---

### 6.3 ProjectResource

**Purpose:** Manage portfolio/case study items per site.
**Sidebar Section:** Content

**Form Fields:**

| Field | Filament Type | Validation | Notes |
|:---|:---|:---|:---|
| `site_id` | Select (relationship) | required | Which site this project belongs to |
| `title` | TextInput | required, max:255 | "Tututor.ai" |
| `slug` | TextInput | required, max:255, unique per site | Auto-generated from title |
| `one_liner` | Textarea | required, max:500 | "An AI-powered tutoring platform..." |
| `client_name` | TextInput | nullable, max:255 | Client/brand name |
| `industry` | TextInput | nullable, max:255 | "EdTech", "Logistics", etc. |
| `url` | TextInput (url) | nullable | Live project URL |
| `challenge` | Textarea | nullable | Problem/bottleneck (for case study format) |
| `solution` | Textarea | nullable | What was built/delivered |
| `outcome` | Textarea | nullable | Results/metrics |
| `tech_stack` | TagsInput | nullable | ["Next.js", "Python", "PostgreSQL"] |
| `images` | SpatieMediaLibraryFileUpload (multiple) | required, min:1 | Project screenshots. Collection: "images". |
| `tags` | TagsInput | nullable | ["branding", "web", "automation"] |
| `featured` | Toggle | default: false | Show on homepage |
| `is_published` | Toggle | default: false | Controls visibility |
| `sort_order` | TextInput (numeric) | default: 0 | Display ordering |

**Table Columns:** title, site.name, client_name, industry, featured, is_published, sort_order
**Table Filters:** site_id, is_published, featured, industry

---

### 6.4 BlogPostResource

**Purpose:** Manage blog/insights articles per site.
**Sidebar Section:** Content

**Form Fields:**

| Field | Filament Type | Validation | Notes |
|:---|:---|:---|:---|
| `site_id` | Select (relationship) | required | Which site |
| `title` | TextInput | required, max:255 | Article title |
| `slug` | TextInput | required, max:255, unique per site | Auto-generated from title |
| `excerpt` | Textarea | nullable, max:500 | Short preview text for listings |
| `body` | RichEditor | required | Full article content (outputs HTML) |
| `cover_image` | SpatieMediaLibraryFileUpload | nullable | Header image. Collection: "cover_image". |
| `tags` | TagsInput | nullable | ["branding", "automation", "strategy"] |
| `author` | TextInput | nullable, max:255 | Author display name |
| `seo_title` | TextInput | nullable, max:255 | Override title for SEO |
| `seo_description` | Textarea | nullable, max:500 | Meta description |
| `is_published` | Toggle | default: false | Controls visibility |
| `published_at` | DateTimePicker | nullable | Publish date (displayed on frontend) |

**Table Columns:** title, site.name, author, tags, is_published, published_at
**Table Filters:** site_id, is_published, author

---

### 6.5 TestimonialResource

**Sidebar Section:** Content

**Form Fields:**

| Field | Filament Type | Validation | Notes |
|:---|:---|:---|:---|
| `site_id` | Select (relationship) | required | Which site |
| `name` | TextInput | required, max:255 | Person's name |
| `role` | TextInput | nullable, max:255 | "CEO", "Founder" |
| `company` | TextInput | nullable, max:255 | Company name |
| `quote` | Textarea | required, max:1000 | The testimonial text |
| `avatar` | SpatieMediaLibraryFileUpload | nullable | Person's photo. Collection: "avatar". |
| `is_published` | Toggle | default: true | Visibility (default published) |
| `sort_order` | TextInput (numeric) | default: 0 | Display order |

**Table Columns:** name, company, site.name, is_published, sort_order

---

### 6.6 TeamMemberResource

**Sidebar Section:** Content

**Form Fields:**

| Field | Filament Type | Validation | Notes |
|:---|:---|:---|:---|
| `site_id` | Select (relationship) | required | Which site |
| `name` | TextInput | required, max:255 | "Shadman Sakib" |
| `role` | TextInput | required, max:255 | "Co-Founder & CEO" |
| `bio` | Textarea | nullable, max:1000 | Short bio |
| `photo` | SpatieMediaLibraryFileUpload | nullable | Profile photo. Collection: "photo". |
| `linkedin` | TextInput (url) | nullable | LinkedIn profile URL |
| `email` | TextInput (email) | nullable | Contact email |
| `is_founder` | Toggle | default: false | Distinguish founders from team |
| `sort_order` | TextInput (numeric) | default: 0 | Display order |

**Table Columns:** name, role, site.name, is_founder, sort_order

---

### 6.7 SiteConfigResource

**Purpose:** Manage per-site configuration (navigation, footer, global CTA).
**Sidebar Section:** Settings

**Form Fields:**

| Field | Filament Type | Validation | Notes |
|:---|:---|:---|:---|
| `site_id` | Select (relationship) | required, unique | One config per site |
| `nav_items` | Repeater | required | Sub-fields: label (TextInput), url (TextInput), is_external (Toggle) |
| `footer_links` | Repeater | nullable | Sub-fields: group_title (TextInput), links (Repeater: label, url) |
| `footer_text` | TextInput | nullable | "© 2026 Zeplow LLC." |
| `cta_text` | TextInput | nullable | "Book a Heartbeat Review" |
| `cta_url` | TextInput | nullable | "/contact" |
| `social_links` | KeyValue | nullable | { linkedin: "...", instagram: "...", whatsapp: "..." } |
| `contact_email` | TextInput (email) | nullable | "hello@zeplow.com" |
| `contact_phone` | TextInput | nullable | Phone/WhatsApp number |

**Table Columns:** site.name, cta_text, updated_at

---

## 7. CONTENT BLOCK SYSTEM

### 7.1 How It Works

Each Page's `content` field is a Filament **Repeater** component. Each item in the repeater has a `type` selector and type-specific fields. The content is stored as a JSON array in the `pages.content` column.

When synced to the API, this JSON array is sent as-is inside the `data.content` field. The frontend's `ContentRenderer` component maps each block `type` to a React component.

### 7.2 Block Types — Complete Specification

#### `hero` — Hero/Banner Section

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `heading` | TextInput | Yes | Main heading text |
| `subheading` | TextInput | No | Secondary text |
| `cta_text` | TextInput | No | Button label |
| `cta_url` | TextInput | No | Button link |
| `background_color` | ColorPicker | No | Hex color for background |

#### `text` — General Text Section

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `heading` | TextInput | No | Section heading |
| `body` | RichEditor | Yes | HTML content |

#### `cards` — Grid of Cards

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `heading` | TextInput | No | Section heading |
| `cards` | Repeater | Yes | Sub-fields: title (TextInput), description (Textarea), link_text (TextInput), link_url (TextInput) |

#### `cta` — Call-to-Action Block

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `heading` | TextInput | Yes | CTA heading |
| `description` | TextInput | No | Supporting text |
| `button_text` | TextInput | Yes | Button label |
| `button_url` | TextInput | Yes | Button link |
| `style` | Select | Yes | Options: "primary", "secondary" |

#### `image` — Single Image

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `image` | FileUpload | Yes | Image file |
| `alt_text` | TextInput | Yes | Alt text for accessibility |
| `caption` | TextInput | No | Image caption |
| `full_width` | Toggle | No | Stretch to full width |

#### `gallery` — Image Gallery

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `images` | Repeater | Yes | Sub-fields: image (FileUpload), alt_text (TextInput), caption (TextInput) |

#### `testimonials` — Testimonial Display Block

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `heading` | TextInput | No | Section heading |
| `use_all` | Toggle | No | If true, display all published testimonials for the site |
| `selected_ids` | MultiSelect (relationship) | No | Specific testimonials to display (used when use_all is false) |

#### `team` — Team Member Display Block

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `heading` | TextInput | No | Section heading |
| `use_all` | Toggle | No | If true, display all team members for the site |
| `selected_ids` | MultiSelect (relationship) | No | Specific team members to display |

#### `projects` — Project Grid Block

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `heading` | TextInput | No | Section heading |
| `count` | TextInput (numeric) | No | Number of projects to show |
| `featured_only` | Toggle | No | Only show featured projects |

#### `stats` — Statistics/Metrics Display

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `stats` | Repeater | Yes | Sub-fields: number (TextInput), label (TextInput), suffix (TextInput, e.g. "+", "%", "x") |

#### `divider` — Visual Separator

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `style` | Select | Yes | Options: "line", "space", "gradient" |

#### `raw_html` — Custom HTML (Emergency Use)

| Field | Type | Required | Description |
|:---|:---|:---|:---|
| `html` | Textarea | Yes | Raw HTML content. Use sparingly. |

### 7.3 Stored JSON Structure Example

```json
[
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
]
```

### 7.4 Block-Level Validation Rules

Each block type's fields must be validated inside the Filament Repeater using Filament's `->schema()` validation. The Repeater itself is nullable (a page can have zero blocks), but once a block is added, its required fields must be enforced.

**Implementation pattern:**

```php
// In PageResource form() method
Forms\Components\Repeater::make('content')
    ->schema([
        Forms\Components\Select::make('type')
            ->options([
                'hero'         => 'Hero Banner',
                'text'         => 'Text Section',
                'cards'        => 'Card Grid',
                'cta'          => 'Call to Action',
                'image'        => 'Single Image',
                'gallery'      => 'Image Gallery',
                'testimonials' => 'Testimonials',
                'team'         => 'Team Members',
                'projects'     => 'Project Grid',
                'stats'        => 'Statistics',
                'divider'      => 'Divider',
                'raw_html'     => 'Raw HTML',
            ])
            ->required()
            ->reactive()
            ->afterStateUpdated(fn (callable $set) => $set('data', [])),

        // Conditional fields based on block type
        Forms\Components\Group::make()
            ->schema(fn (callable $get) => match ($get('type')) {
                'hero'  => self::heroBlockFields(),
                'text'  => self::textBlockFields(),
                'cards' => self::cardsBlockFields(),
                'cta'   => self::ctaBlockFields(),
                'image' => self::imageBlockFields(),
                // ... etc for all 12 types
                default => [],
            }),
    ])
    ->collapsible()
    ->reorderableWithButtons()
    ->columnSpanFull()
```

**Validation rules per block type:**

| Block Type | Field | Validation Rule |
|:---|:---|:---|
| `hero` | `data.heading` | `required, string, max:255` |
| `hero` | `data.cta_text` | `required_with:data.cta_url, string, max:100` |
| `hero` | `data.cta_url` | `required_with:data.cta_text, string, max:500` |
| `hero` | `data.background_color` | `nullable, regex:/^#[0-9A-Fa-f]{6}$/` |
| `text` | `data.body` | `required, string` |
| `cards` | `data.cards` | `required, array, min:1` |
| `cards` | `data.cards.*.title` | `required, string, max:255` |
| `cards` | `data.cards.*.description` | `required, string, max:1000` |
| `cta` | `data.heading` | `required, string, max:255` |
| `cta` | `data.button_text` | `required, string, max:100` |
| `cta` | `data.button_url` | `required, string, max:500` |
| `cta` | `data.style` | `required, in:primary,secondary` |
| `image` | `data.image` | `required, file, image, max:5120` (5 MB) |
| `image` | `data.alt_text` | `required, string, max:255` |
| `gallery` | `data.images` | `required, array, min:1` |
| `gallery` | `data.images.*.image` | `required, file, image, max:5120` |
| `gallery` | `data.images.*.alt_text` | `required, string, max:255` |
| `stats` | `data.stats` | `required, array, min:1` |
| `stats` | `data.stats.*.number` | `required, string, max:50` |
| `stats` | `data.stats.*.label` | `required, string, max:100` |
| `divider` | `data.style` | `required, in:line,space,gradient` |
| `raw_html` | `data.html` | `required, string, max:10000` |

**Frontend safety net:** Even with CMS validation, the `ContentRenderer` in `packages/ui/src/ContentRenderer.tsx` should defensively check for required fields before rendering each block. If a required field is missing, skip the block and log a warning in development mode. This protects against edge cases where data corruption occurs during sync. See Central PRD Section 5.7 for the `isValidBlock` implementation.

---

## 8. FILAMENT DASHBOARD WIDGETS

### 8.1 Content Overview Widget

**Type:** Stats card
**Content:** Total Pages (published/draft), Total Projects, Total Blog Posts — across all sites.

### 8.2 Last Deploy Status Widget

**Type:** Info card per site
**Content:** Shows timestamp of last successful sync to API for each site (from `sync_logs` table). If the last sync failed, shows a red warning with the error message from `last_error`.

### 8.3 Quick Actions Widget

**Type:** Action buttons
**Content:**
- "Resync All Content" — triggers the ResyncAllAction (see Section 15)
- "View Parent Site" — external link to zeplow.com
- "View Narrative Site" — external link to narrative.zeplow.com
- "View Logic Site" — external link to logic.zeplow.com

---

## 9. FILAMENT USERS, ROLES & PERMISSIONS

### 9.1 Users

| User | Email | Role | Password |
|:---|:---|:---|:---|
| Shakib Bin Kabir | shakib@zeplow.com | `super_admin` | Set during `make:filament-user` |
| Shadman Sakib | shadman@zeplow.com | `admin` | Set during `make:filament-user` |

### 9.2 Role Permissions

| Capability | super_admin | admin |
|:---|:---|:---|
| Access Filament panel | Yes | Yes |
| Create/edit/delete pages | Yes | Yes |
| Create/edit/delete projects | Yes | Yes |
| Create/edit/delete blog posts | Yes | Yes |
| Create/edit/delete testimonials | Yes | Yes |
| Create/edit/delete team members | Yes | Yes |
| Edit site configs | Yes | Yes |
| **Create sites** | **Yes** | **No** |
| **Delete sites** | **Yes** | **No** |
| Edit sites | Yes | Yes |
| Use Resync All action | Yes | Yes |

### 9.3 Implementation

Use Filament's policy system or inline `canCreate()` / `canDelete()` methods on `SiteResource`:

```php
// In SiteResource.php

public static function canCreate(): bool
{
    return auth()->user()->isSuperAdmin();
}

public static function canDelete(Model $record): bool
{
    return auth()->user()->isSuperAdmin();
}
```

### 9.4 Authentication Method

Filament's built-in auth (Laravel session-based). No external SSO, OAuth, or social login. Users are created via `php artisan make:filament-user` or seeders.

---

## 10. MEDIA STORAGE & IMAGE HANDLING

### 10.1 Storage Configuration

| Setting | Value |
|:---|:---|
| Storage disk | `public` (local storage on cPanel) |
| Filesystem path | `storage/app/public/` (symlinked to `public/storage/`) |
| Base URL | `https://cms.zeplow.com/storage/` |
| Max upload size | 5 MB per file |
| Allowed types | jpg, jpeg, png, webp, svg, gif, pdf |

### 10.2 Image Conversions (Spatie MediaLibrary)

Defined on models that have images (Project, etc.):

| Conversion Name | Max Width | Max Height | Fit Mode | Quality | Format |
|:---|:---|:---|:---|:---|:---|
| `thumbnail` | 400 | 300 | crop | 80 | Original |
| `medium` | 800 | 600 | contain | 85 | Original |
| `large` | 1600 | 1200 | contain | 90 | Original |
| `large-webp` | 1600 | 1200 | contain | 85 | WebP |

The `large-webp` conversion provides a WebP alternative for browsers that support it. The frontend uses a `<picture>` element or `srcSet` to serve the most efficient format.

**Note:** If Cloudflare Polish (Pro plan) is enabled on `cms.zeplow.com`, the `large-webp` conversion is redundant — Polish handles WebP conversion at the CDN edge. In that case, remove `large-webp` to save storage space.

### 10.3 How Images Reach the Frontend

1. Editor uploads image in Filament form
2. Spatie MediaLibrary stores the file in `storage/app/public/` and creates conversions
3. File is accessible at `https://cms.zeplow.com/storage/{path}`
4. When content syncs to API, image URLs are sent as **absolute URLs** (e.g., `https://cms.zeplow.com/storage/projects/tututor-1.jpg`)
5. Frontend renders these URLs directly in `<img>` tags
6. Because `cms.zeplow.com` is proxied through Cloudflare (orange cloud), all image requests are cached at Cloudflare's edge CDN — **free global CDN for media files**

### 10.4 Storage Symlink

Must run on cPanel after deployment:

```bash
php artisan storage:link
```

This creates a symlink from `public/storage` → `storage/app/public`.

### 10.5 Future Migration Path

If storage needs grow beyond cPanel limits, migrate media to **Cloudflare R2** (free tier: 10 GB storage, 10 million requests/month). Spatie MediaLibrary supports custom filesystem disks — switch from `public` to an S3-compatible R2 disk without changing application code.

---

## 11. SYNC SYSTEM — ARCHITECTURE

### 11.1 Overview

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

### 11.2 Content Types Synced

| Model | content_type sent to API | Trigger |
|:---|:---|:---|
| Page | `page` | saved (if published), deleted |
| Project | `project` | saved (if published), deleted |
| BlogPost | `blog_post` | saved (if published), deleted |
| Testimonial | `testimonial` | saved (if published), deleted |
| TeamMember | `team_member` | saved, deleted |
| SiteConfig | *(uses config sync endpoint)* | saved |

### 11.3 Sync Rules

- **Only published content is synced.** If `is_published` is false, the observer does NOT dispatch a sync job.
- **Deletes are always synced**, regardless of publish status.
- **Team members are always synced** (no `is_published` filter on team — the CMS controls what gets synced by what exists).
  - **Note:** Team members have no `is_published` field. All team member records are synced on every save. To hide a team member from the frontend, delete the record from the CMS. This is intentional — team visibility is controlled by record existence, not a toggle.
- **Config changes are always synced** (one config per site, always active).

### 11.4 Image URL Construction in Sync Payloads

When the SyncService (via Observers) builds data payloads for models with media (projects, blog posts, testimonials, team members, pages), it must construct **absolute URLs** for images using Spatie MediaLibrary's `getUrl()` and `getUrl('conversion-name')` methods.

**Rules:**
- The sync payload should include the **full-size image URL** as the primary image field.
- Optionally include conversion URLs (thumbnail, medium, large) as additional fields so frontends can choose the appropriate image size without URL manipulation.
- URL pattern: `https://cms.zeplow.com/storage/{path}` (generated automatically by Spatie's public disk URL generation).

**Example — Project sync payload with image conversions:**

```php
// Inside ProjectObserver::saved()
'images' => $project->getMedia('images')->map(fn ($media) => [
    'original'  => $media->getUrl(),
    'thumbnail' => $media->getUrl('thumbnail'),
    'medium'    => $media->getUrl('medium'),
    'large'     => $media->getUrl('large'),
])->toArray(),
```

**Example — BlogPost sync payload with single cover image:**

```php
// Inside BlogPostObserver::saved()
'cover_image' => $post->getFirstMediaUrl('cover_image'),
```

This ensures frontends receive ready-to-use absolute URLs at every available size. All image URLs are served via Cloudflare CDN (see Section 10.3).

---

## 12. SYNC JOBS

### 12.1 SyncContentJob

```php
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

    public function __construct(
        private int $syncLogId,
        private string $siteKey,
        private string $contentType,
        private string $slug,
        private array $data,
        private ?string $publishedAt = null,
    ) {}

    public function handle(): void
    {
        $apiUrl = config('services.zeplow_api.url');
        $apiKey = config('services.zeplow_api.key');

        $log = SyncLog::find($this->syncLogId);

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

**Key change:** The sync_log entry is created by the SyncService (Section 13) *before* the job is dispatched. The job receives the `$syncLogId` and updates the existing record on each attempt. This prevents duplicate sync_log entries on job retries.

### 12.2 SyncConfigJob

```php
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

class SyncConfigJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private int $syncLogId,
        private string $siteKey,
        private array $configData,
    ) {}

    public function handle(): void
    {
        $apiUrl = config('services.zeplow_api.url');
        $apiKey = config('services.zeplow_api.key');

        $log = SyncLog::find($this->syncLogId);

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

            Log::error("Config sync failed (attempt {$this->attempts()}/{$this->tries})", [
                'site_key' => $this->siteKey,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

### 12.3 DeleteContentJob

```php
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

class DeleteContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private int $syncLogId,
        private string $siteKey,
        private string $contentType,
        private string $slug,
    ) {}

    public function handle(): void
    {
        $apiUrl = config('services.zeplow_api.url');
        $apiKey = config('services.zeplow_api.key');

        $log = SyncLog::find($this->syncLogId);

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

---

## 13. SYNC SERVICE

**File:** `app/Services/SyncService.php`

The SyncService creates a `sync_log` entry with `status: 'pending'` **before** dispatching each job, then passes the `$syncLogId` to the job constructor. This ensures exactly one sync_log entry per sync operation, even if the job retries multiple times. The Observers build the payloads and call the service.

```php
<?php

namespace App\Services;

use App\Jobs\SyncContentJob;
use App\Jobs\SyncConfigJob;
use App\Jobs\DeleteContentJob;
use App\Models\SyncLog;

class SyncService
{
    /**
     * Dispatch a content sync job.
     * Creates a sync_log entry first, then passes the ID to the job.
     * With QUEUE_CONNECTION=sync: executes immediately.
     * With database/redis queue: executes asynchronously.
     */
    public function syncContent(
        string $siteKey,
        string $contentType,
        string $slug,
        array $data,
        ?string $publishedAt = null
    ): void {
        $log = SyncLog::create([
            'site_key'     => $siteKey,
            'content_type' => $contentType,
            'content_slug' => $slug,
            'status'       => 'pending',
        ]);

        SyncContentJob::dispatch($log->id, $siteKey, $contentType, $slug, $data, $publishedAt);
    }

    /**
     * Dispatch a config sync job.
     * Creates a sync_log entry first, then passes the ID to the job.
     */
    public function syncConfig(string $siteKey, array $configData): void
    {
        $log = SyncLog::create([
            'site_key'     => $siteKey,
            'content_type' => 'site_config',
            'content_slug' => $siteKey,
            'status'       => 'pending',
        ]);

        SyncConfigJob::dispatch($log->id, $siteKey, $configData);
    }

    /**
     * Dispatch a content delete job.
     * Creates a sync_log entry first, then passes the ID to the job.
     */
    public function deleteContent(string $siteKey, string $contentType, string $slug): void
    {
        $log = SyncLog::create([
            'site_key'     => $siteKey,
            'content_type' => $contentType,
            'content_slug' => $slug,
            'status'       => 'pending',
        ]);

        DeleteContentJob::dispatch($log->id, $siteKey, $contentType, $slug);
    }
}
```

---

## 14. MODEL OBSERVERS

### 14.1 PageObserver

```php
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
                    'title'      => $page->title,
                    'template'   => $page->template,
                    'content'    => $page->content,
                    'seo'        => [
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

### 14.2 ProjectObserver

```php
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
                    'title'       => $project->title,
                    'one_liner'   => $project->one_liner,
                    'client_name' => $project->client_name,
                    'industry'    => $project->industry,
                    'url'         => $project->url,
                    'challenge'   => $project->challenge,
                    'solution'    => $project->solution,
                    'outcome'     => $project->outcome,
                    'tech_stack'  => $project->tech_stack,
                    'images'      => $project->getMedia('images')->map(function ($media) {
                        return [
                            'original'  => $media->getUrl(),
                            'large'     => $media->getUrl('large'),
                            'medium'    => $media->getUrl('medium'),
                            'thumbnail' => $media->getUrl('thumbnail'),
                            'large_webp' => $media->hasGeneratedConversion('large-webp')
                                ? $media->getUrl('large-webp')
                                : null,
                            'alt'       => $media->getCustomProperty('alt', ''),
                        ];
                    })->toArray(),
                    'tags'        => $project->tags,
                    'featured'    => $project->featured,
                    'sort_order'  => $project->sort_order,
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

### 14.3 BlogPostObserver

```php
<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\SyncService;

class BlogPostObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(BlogPost $post): void
    {
        if ($post->is_published) {
            $this->syncService->syncContent(
                siteKey: $post->site->key,
                contentType: 'blog_post',
                slug: $post->slug,
                data: [
                    'title'       => $post->title,
                    'excerpt'     => $post->excerpt,
                    'body'        => $post->body,
                    'cover_image' => $post->getFirstMediaUrl('cover_image'),
                    'tags'        => $post->tags,
                    'author'      => $post->author,
                    'seo'         => [
                        'title'       => $post->seo_title ?? $post->title,
                        'description' => $post->seo_description ?? $post->excerpt,
                    ],
                ],
                publishedAt: $post->published_at?->toISOString(),
            );
        } elseif ($post->isDirty('is_published') && !$post->is_published) {
            // Just unpublished — remove from API
            $this->syncService->deleteContent(
                siteKey: $post->site->key,
                contentType: 'blog_post',
                slug: $post->slug,
            );
        }
    }

    public function deleted(BlogPost $post): void
    {
        $this->syncService->deleteContent(
            siteKey: $post->site->key,
            contentType: 'blog_post',
            slug: $post->slug,
        );
    }
}
```

### 14.4 TestimonialObserver

```php
<?php

namespace App\Observers;

use App\Models\Testimonial;
use App\Services\SyncService;

class TestimonialObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(Testimonial $testimonial): void
    {
        if ($testimonial->is_published) {
            $this->syncService->syncContent(
                siteKey: $testimonial->site->key,
                contentType: 'testimonial',
                slug: \Illuminate\Support\Str::slug($testimonial->name . '-' . $testimonial->id),
                data: [
                    'name'       => $testimonial->name,
                    'role'       => $testimonial->role,
                    'company'    => $testimonial->company,
                    'quote'      => $testimonial->quote,
                    'avatar'     => $testimonial->getFirstMediaUrl('avatar'),
                    'sort_order' => $testimonial->sort_order,
                ],
                publishedAt: now()->toISOString(),
            );
        } elseif ($testimonial->isDirty('is_published') && !$testimonial->is_published) {
            // Just unpublished — remove from API
            $this->syncService->deleteContent(
                siteKey: $testimonial->site->key,
                contentType: 'testimonial',
                slug: \Illuminate\Support\Str::slug($testimonial->name . '-' . $testimonial->id),
            );
        }
    }

    public function deleted(Testimonial $testimonial): void
    {
        $this->syncService->deleteContent(
            siteKey: $testimonial->site->key,
            contentType: 'testimonial',
            slug: \Illuminate\Support\Str::slug($testimonial->name . '-' . $testimonial->id),
        );
    }
}
```

### 14.5 TeamMemberObserver

```php
<?php

namespace App\Observers;

use App\Models\TeamMember;
use App\Services\SyncService;

class TeamMemberObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(TeamMember $member): void
    {
        $this->syncService->syncContent(
            siteKey: $member->site->key,
            contentType: 'team_member',
            slug: \Illuminate\Support\Str::slug($member->name . '-' . $member->id),
            data: [
                'name'       => $member->name,
                'role'       => $member->role,
                'bio'        => $member->bio,
                'photo'      => $member->getFirstMediaUrl('photo'),
                'linkedin'   => $member->linkedin,
                'email'      => $member->email,
                'is_founder' => $member->is_founder,
                'sort_order' => $member->sort_order,
            ],
            publishedAt: now()->toISOString(),
        );
    }

    public function deleted(TeamMember $member): void
    {
        $this->syncService->deleteContent(
            siteKey: $member->site->key,
            contentType: 'team_member',
            slug: \Illuminate\Support\Str::slug($member->name . '-' . $member->id),
        );
    }
}
```

**Note:** TeamMemberObserver does NOT check `is_published` — all team members are synced.

### 14.6 SiteConfigObserver

```php
<?php

namespace App\Observers;

use App\Models\SiteConfig;
use App\Services\SyncService;

class SiteConfigObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(SiteConfig $config): void
    {
        $this->syncService->syncConfig(
            siteKey: $config->site->key,
            configData: [
                'site_name'     => $config->site->name,
                'domain'        => $config->site->domain,
                'tagline'       => $config->site->tagline,
                'nav_items'     => $config->nav_items,
                'footer_links'  => $config->footer_links,
                'footer_text'   => $config->footer_text,
                'cta_text'      => $config->cta_text,
                'cta_url'       => $config->cta_url,
                'social_links'  => $config->social_links,
                'contact_email' => $config->contact_email,
            ],
        );
    }
}
```

### 14.7 Observer Registration

```php
<?php
// app/Providers/AppServiceProvider.php

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

---

## 15. MANUAL RESYNC (FILAMENT ACTION)

**File:** `app/Filament/Actions/ResyncAllAction.php`

Available on the Filament dashboard. Resends ALL published content for a selected site to the API.

```php
<?php

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
                    $page->touch(); // triggers Observer → sync
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

                // Sync all team members (no publish filter)
                foreach ($site->teamMembers()->get() as $member) {
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

**How it works:** Calling `touch()` on a model updates its `updated_at` timestamp, which fires the `saved` event on the model, which triggers the registered Observer, which dispatches the appropriate sync job. This reuses the entire existing sync pipeline without duplicate code.

**Deploy hook behavior during Resync All:**

When "Resync All Content" is triggered, the CMS sends individual sync requests to the API for every published content item. Each sync triggers the API's DeployService, but the API's 60-second debounce window means only the FIRST sync per site actually fires a Cloudflare Pages deploy hook. Subsequent syncs within that 60-second window are debounced — the content is still stored in the API database, but no redundant deploy hook is fired.

This means a full resync of all 3 sites results in exactly 3 Cloudflare builds (one per site), not one-per-content-item.

If the resync takes longer than 60 seconds per site (unlikely with synchronous queue), a maximum of 2 builds per site may be triggered. This is acceptable.

---

## 16. AUTHENTICATION & SECURITY

### 16.1 Admin Login

| Concern | Implementation |
|:---|:---|
| Admin login | Filament built-in auth (session-based) |
| Login URL | `cms.zeplow.com/admin/login` |
| Password hashing | bcrypt (Laravel default) |
| Session driver | `file` (cPanel compatible) |
| CSRF protection | Enabled (Filament default) |
| Force HTTPS | Yes (via `.htaccess` on cPanel) |

### 16.2 API Key for Sync

| Concern | Implementation |
|:---|:---|
| Key format | 64-character random string |
| Storage | `.env` file as `ZEPLOW_API_KEY` |
| Usage | Sent as `Authorization: Bearer {key}` to API internal endpoints |
| Shared with | API app (same key stored in API's `api_keys` table) |

### 16.3 .htaccess for HTTPS

```apache
# Force HTTPS on cPanel
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 17. ERROR HANDLING & LOGGING

### 17.1 Error Scenarios

| Scenario | Handling |
|:---|:---|
| Sync job fails (all 3 retries) | Log error in `sync_logs` with status `failed` and error message. Content is safe in CMS DB. Can be resynced later via "Resync All" action. |
| API unreachable | Same as above. Sync retries 3 times with 5s backoff, then logs failure. |
| Image upload fails | Filament shows default error notification. No custom handling needed. |
| Validation error in form | Filament shows inline field errors. Default behavior. |
| Editor saves unpublished content | No sync dispatched. Content stays in CMS DB only. |

### 17.2 Logging

| Log Type | Location | Content |
|:---|:---|:---|
| Laravel application log | `storage/logs/laravel.log` | All errors, sync failures, exceptions |
| Sync log (database) | `sync_logs` table | Every sync attempt: site_key, content_type, slug, status, attempts, errors, timestamp |

### 17.3 Monitoring Sync Health

The **Last Deploy Status** dashboard widget (Section 8.2) queries the `sync_logs` table and shows:
- Last successful sync timestamp per site
- Red warning if the most recent sync for any site has status `failed`
- The error message from `last_error` for failed syncs

### 17.4 Automated Failed Sync Alerts

A scheduled Artisan command runs weekly to detect and report failed syncs.

**File:** `app/Console/Commands/CheckFailedSyncsCommand.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckFailedSyncsCommand extends Command
{
    protected $signature = 'sync:check-failed';
    protected $description = 'Check for failed syncs in the last 7 days and send a summary email';

    public function handle(): int
    {
        $failedSyncs = SyncLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($failedSyncs->isEmpty()) {
            $this->info('No failed syncs in the last 7 days.');
            return self::SUCCESS;
        }

        $summary = $failedSyncs->map(function ($log) {
            return "[{$log->created_at}] {$log->site_key}/{$log->content_type}/{$log->content_slug} — {$log->last_error}";
        })->implode("\n");

        Mail::raw(
            "Failed Syncs Summary (last 7 days):\n\n{$summary}\n\nTotal: {$failedSyncs->count()} failed sync(s).\n\nUse the 'Resync All' action in the CMS dashboard to retry.",
            function ($message) use ($failedSyncs) {
                $message->to('hello@zeplow.com')
                    ->subject("Zeplow CMS: {$failedSyncs->count()} Failed Sync(s) This Week");
            }
        );

        $this->warn("{$failedSyncs->count()} failed sync(s) found. Summary email sent to hello@zeplow.com.");
        return self::SUCCESS;
    }
}
```

**Scheduler Registration:**

```php
// app/Console/Kernel.php (or bootstrap/app.php in Laravel 11)

protected function schedule(Schedule $schedule): void
{
    $schedule->command('sync:check-failed')->weekly()->mondays()->at('09:00');
}
```

**cPanel Cron Job:** Add the following cron entry to run the Laravel scheduler:

```
* * * * * cd /home/{user}/cms-app && php artisan schedule:run >> /dev/null 2>&1
```

Alternatively, run every 5 minutes if per-minute cron is not available on the hosting plan:

```
*/5 * * * * cd /home/{user}/cms-app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 18. SEED DATA

### 18.1 Site Seeder

```php
<?php
// database/seeders/SiteSeeder.php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'name'    => 'Zeplow',
                'key'     => 'parent',
                'domain'  => 'zeplow.com',
                'tagline' => 'Story. Systems. Ventures.',
            ],
            [
                'name'    => 'Zeplow Narrative',
                'key'     => 'narrative',
                'domain'  => 'narrative.zeplow.com',
                'tagline' => 'Stories that sell.',
            ],
            [
                'name'    => 'Zeplow Logic',
                'key'     => 'logic',
                'domain'  => 'logic.zeplow.com',
                'tagline' => 'Build once. Run forever.',
            ],
        ];

        foreach ($sites as $site) {
            Site::firstOrCreate(['key' => $site['key']], $site);
        }
    }
}
```

### 18.2 User Seeder

```php
<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'shakib@zeplow.com'],
            [
                'name'     => 'Shakib Bin Kabir',
                'password' => Hash::make('CHANGE_ME_ON_FIRST_LOGIN'),
                'role'     => 'super_admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'shadman@zeplow.com'],
            [
                'name'     => 'Shadman Sakib',
                'password' => Hash::make('CHANGE_ME_ON_FIRST_LOGIN'),
                'role'     => 'admin',
            ]
        );
    }
}
```

**Alternative:** Create users via `php artisan make:filament-user` (interactive prompts for name, email, password) and manually set the `role` column in the database.

### 18.3 Database Seeder

```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SiteSeeder::class,
            UserSeeder::class,
        ]);
    }
}
```

---

## 19. ENVIRONMENT VARIABLES

### 19.1 Complete .env File

```env
APP_NAME="Zeplow CMS"
APP_ENV=production
APP_KEY=base64:... (generate via php artisan key:generate)
APP_DEBUG=false
APP_URL=https://cms.zeplow.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cms_zeplow
DB_USERNAME=cms_user
DB_PASSWORD=... (strong password)

# Filesystem
FILESYSTEM_DISK=public

# API Sync
ZEPLOW_API_URL=https://api.zeplow.com
ZEPLOW_API_KEY=... (This is the plaintext API key. It is stored as a SHA-256 hash in the API app's database. Keep this value secret — if lost, a new key must be generated on both the API and CMS.)

# Session, Cache, Queue
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# Email (not currently used by CMS, but configure for future)
MAIL_MAILER=smtp
MAIL_HOST=... (cPanel SMTP host)
MAIL_PORT=465
MAIL_USERNAME=hello@zeplow.com
MAIL_PASSWORD=... (email password)
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=hello@zeplow.com
MAIL_FROM_NAME="Zeplow"
```

### 19.2 Config Files to Create/Modify

| File | What to Add |
|:---|:---|
| `config/services.php` | `zeplow_api.url` and `zeplow_api.key` from .env |

---

## 20. DNS & SSL

### 20.1 DNS Record

| Type | Name | Content | Proxy |
|:---|:---|:---|:---|
| A | `cms` | cPanel server IP | **Yes (orange cloud)** |

### 20.2 SSL

| Domain | SSL Provider | Notes |
|:---|:---|:---|
| cms.zeplow.com | Cloudflare (auto, proxied) | Universal SSL, free |

### 20.3 Why Orange Cloud Matters for the CMS

Proxying `cms.zeplow.com` through Cloudflare (orange cloud) provides:

1. **Free SSL** — Cloudflare handles HTTPS termination
2. **CDN for images** — All requests to `cms.zeplow.com/storage/*` are cached at Cloudflare's 330+ edge nodes. Images uploaded by editors are served globally from CDN.
3. **DDoS protection** — Cloudflare absorbs attacks
4. **IP hiding** — The cPanel server's real IP is not exposed

### 20.4 Cloudflare Cache Rule (Recommended)

Add a Cloudflare Cache Rule for `cms.zeplow.com/storage/*` with a **30-day TTL**. This ensures uploaded images are aggressively cached at the edge since they rarely change.

---

## 21. cPANEL DEPLOYMENT

### 21.1 Deployment Steps

1. Upload code to cPanel via Git or FTP
2. Point the `cms` subdomain to the `public/` directory of the Laravel app
3. Configure `.env` with production values
4. Run `composer install --no-dev --optimize-autoloader`
5. Run `php artisan key:generate` (if not already set)
6. Run `php artisan migrate`
7. Run `php artisan db:seed`
8. Run `php artisan storage:link`
9. Run `php artisan filament:install --panels` (if not already done)
10. Verify login at `cms.zeplow.com/admin/login`

### 21.2 cPanel-Specific Notes

- Ensure PHP 8.2+ is selected for the subdomain
- Ensure `mod_rewrite` is enabled (should be by default)
- Ensure the `.htaccess` file in `public/` is not being blocked
- Max upload size in PHP may need adjustment (`upload_max_filesize`, `post_max_size`) — set to at least 10M via `.user.ini` or cPanel PHP settings

---

## 22. DIRECTORY STRUCTURE

```
cms-app/
├── app/
│   ├── Filament/
│   │   ├── Actions/
│   │   │   └── ResyncAllAction.php            # Manual resync Filament action
│   │   ├── Resources/
│   │   │   ├── SiteResource.php               # Sites management
│   │   │   ├── SiteResource/
│   │   │   │   └── Pages/
│   │   │   │       ├── CreateSite.php
│   │   │   │       ├── EditSite.php
│   │   │   │       └── ListSites.php
│   │   │   ├── PageResource.php               # Pages management
│   │   │   ├── PageResource/Pages/...
│   │   │   ├── ProjectResource.php            # Projects management
│   │   │   ├── ProjectResource/Pages/...
│   │   │   ├── BlogPostResource.php           # Blog posts management
│   │   │   ├── BlogPostResource/Pages/...
│   │   │   ├── TestimonialResource.php        # Testimonials management
│   │   │   ├── TestimonialResource/Pages/...
│   │   │   ├── TeamMemberResource.php         # Team members management
│   │   │   ├── TeamMemberResource/Pages/...
│   │   │   ├── SiteConfigResource.php         # Site configuration management
│   │   │   └── SiteConfigResource/Pages/...
│   │   └── Widgets/
│   │       ├── ContentOverviewWidget.php      # Total pages, projects, posts
│   │       ├── LastDeployStatusWidget.php      # Last sync status per site
│   │       └── QuickActionsWidget.php          # Resync + view site links
│   ├── Console/
│   │   └── Commands/
│   │       └── CheckFailedSyncsCommand.php    # Weekly email alert for failed syncs
│   ├── Jobs/
│   │   ├── SyncContentJob.php                 # Syncs content to API (3 retries, 5s backoff)
│   │   ├── SyncConfigJob.php                  # Syncs site config to API
│   │   └── DeleteContentJob.php               # Deletes content from API
│   ├── Models/
│   │   ├── User.php                           # Filament auth user (with role)
│   │   ├── Site.php                           # Site model (parent, narrative, logic)
│   │   ├── Page.php                           # Page model (HasMedia)
│   │   ├── Project.php                        # Project model (HasMedia, conversions)
│   │   ├── BlogPost.php                       # Blog post model (HasMedia)
│   │   ├── Testimonial.php                    # Testimonial model (HasMedia)
│   │   ├── TeamMember.php                     # Team member model (HasMedia)
│   │   ├── SiteConfig.php                     # Site config model
│   │   └── SyncLog.php                        # Sync log model
│   ├── Observers/
│   │   ├── PageObserver.php                   # Syncs pages on save/delete
│   │   ├── ProjectObserver.php                # Syncs projects on save/delete
│   │   ├── BlogPostObserver.php               # Syncs blog posts on save/delete
│   │   ├── TestimonialObserver.php            # Syncs testimonials on save/delete
│   │   ├── TeamMemberObserver.php             # Syncs team members on save/delete
│   │   └── SiteConfigObserver.php             # Syncs config on save
│   ├── Providers/
│   │   └── AppServiceProvider.php             # Observer registration in boot()
│   └── Services/
│       └── SyncService.php                    # Dispatch layer for sync jobs
├── config/
│   └── services.php                           # zeplow_api.url and zeplow_api.key
├── database/
│   ├── migrations/
│   │   ├── xxxx_create_users_table.php
│   │   ├── xxxx_create_sites_table.php
│   │   ├── xxxx_create_pages_table.php
│   │   ├── xxxx_create_projects_table.php
│   │   ├── xxxx_create_blog_posts_table.php
│   │   ├── xxxx_create_testimonials_table.php
│   │   ├── xxxx_create_team_members_table.php
│   │   ├── xxxx_create_site_configs_table.php
│   │   ├── xxxx_create_sync_logs_table.php
│   │   └── xxxx_create_media_table.php        # From Spatie MediaLibrary
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── SiteSeeder.php                     # 3 sites
│       └── UserSeeder.php                     # 2 admin users
├── public/
│   ├── .htaccess                              # Force HTTPS
│   └── storage -> ../storage/app/public       # Symlink (created by php artisan storage:link)
├── storage/
│   └── app/
│       └── public/                            # Uploaded media files live here
├── .env
└── composer.json
```

---

## 23. IMPLEMENTATION ORDER

### Phase 1: Project Setup (Days 1–2)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 1.1 | Create MySQL database `cms_zeplow` on cPanel | Via cPanel MySQL interface | Nothing |
| 1.2 | Create database user `cms_user` with full privileges on `cms_zeplow` | Via cPanel | 1.1 |
| 1.3 | Install fresh Laravel 11 project | `composer create-project laravel/laravel cms-app` | Nothing |
| 1.4 | Install Filament v3 | `composer require filament/filament` + `php artisan filament:install --panels` | 1.3 |
| 1.5 | Install Spatie MediaLibrary + Filament plugin | Both packages + publish migrations | 1.4 |
| 1.6 | Configure `.env` | Database, session (file), cache (file), queue (sync), API URL/key | 1.1, 1.3 |
| 1.7 | Add `zeplow_api` to `config/services.php` | URL + key from .env | 1.3 |

### Phase 2: Database & Models (Days 2–3)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 2.1 | Create all database migrations | users (with role), sites, pages, projects, blog_posts, testimonials, team_members, site_configs, sync_logs | 1.3 |
| 2.2 | Run migrations | `php artisan migrate` | 1.1, 1.6, 2.1 |
| 2.3 | Create all Eloquent models | Site, Page, Project, BlogPost, Testimonial, TeamMember, SiteConfig, SyncLog, User | 2.1 |
| 2.4 | Define model relationships | Site hasMany Pages, Projects, etc. HasMedia interfaces. | 2.3 |
| 2.5 | Register media collections and conversions | On Project (thumbnail, medium, large), BlogPost, Testimonial, TeamMember, Page | 2.4 |

### Phase 3: Filament Resources (Days 4–6)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 3.1 | Create SiteResource | Form, table, permission checks (super_admin only for create/delete) | 2.4 |
| 3.2 | Create PageResource | Form with content blocks Repeater, slug auto-gen, all filters | 2.4 |
| 3.3 | Create ProjectResource | Form with Spatie uploads, TagsInput, all fields | 2.4 |
| 3.4 | Create BlogPostResource | Form with RichEditor, cover image, tags | 2.4 |
| 3.5 | Create TestimonialResource | Form with avatar upload | 2.4 |
| 3.6 | Create TeamMemberResource | Form with photo upload | 2.4 |
| 3.7 | Create SiteConfigResource | Form with nav Repeater, footer Repeater, social KeyValue | 2.4 |

### Phase 4: Seed Data & Users (Day 6)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 4.1 | Create SiteSeeder | 3 sites (parent, narrative, logic) | 2.3 |
| 4.2 | Create UserSeeder | Shakib (super_admin) + Shadman (admin) | 2.3 |
| 4.3 | Run seeders | `php artisan db:seed` | 2.2, 4.1, 4.2 |
| 4.4 | Test: Login to Filament | Verify both users can access, role permissions work | 4.3 |

### Phase 5: Sync System (Days 7–8)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 5.1 | Create SyncService | Dispatch layer for all 3 job types | 1.3 |
| 5.2 | Create SyncContentJob | HTTP POST to API with retries and sync_log tracking | 5.1 |
| 5.3 | Create SyncConfigJob | HTTP POST config to API with retries | 5.1 |
| 5.4 | Create DeleteContentJob | HTTP DELETE to API with retries | 5.1 |
| 5.5 | Create all 6 Observers | Page, Project, BlogPost, Testimonial, TeamMember, SiteConfig | 5.1, 2.4 |
| 5.6 | Register Observers in AppServiceProvider | boot() method | 5.5 |
| 5.7 | Create ResyncAllAction | Filament dashboard action | 5.1 |

### Phase 6: Dashboard Widgets (Day 8)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 6.1 | Create ContentOverviewWidget | Total pages, projects, blog posts | 3.1–3.7 |
| 6.2 | Create LastDeployStatusWidget | Query sync_logs for last sync per site | 5.2 |
| 6.3 | Create QuickActionsWidget | Resync button + external site links | 5.7 |
| 6.4 | Create CheckFailedSyncsCommand | Weekly Artisan command to email summary of failed syncs (Section 17.4) | 5.2 |

### Phase 7: Deployment & Testing (Days 9–10)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 7.1 | Deploy CMS to cPanel | Upload, .env, migrations, seeders, storage:link | All above |
| 7.2 | Configure Cloudflare proxy for cms.zeplow.com | Orange cloud on, SSL verified | 7.1 |
| 7.3 | Add Cloudflare Cache Rule | `cms.zeplow.com/storage/*` with 30-day TTL | 7.2 |
| 7.4 | Test: Login to production CMS | Both users, role permissions | 7.1 |
| 7.5 | Test: Create page, publish, verify sync_logs | Observer → Job → API sync | 7.1 (requires API deployed) |
| 7.6 | Test: Upload image, verify CDN | Check cf-cache-status header on image URL | 7.2 |
| 7.7 | Test: Resync All action | All published content re-sent | 7.5 |
| 7.8 | Test: API down during publish | sync_logs shows "failed" after 3 retries | 7.5 |
| 7.9 | Set up cPanel cron job for Laravel scheduler | `* * * * * cd /home/{user}/cms-app && php artisan schedule:run >> /dev/null 2>&1` | 7.1 |
| 7.10 | Set up weekly database backup cron job | `mysqldump` to `/home/{user}/backups/` with 28-day retention cleanup | 7.1 |
| 7.11 | Test: Failed sync email alert | Manually create a failed sync_log, run `php artisan sync:check-failed`, verify email | 7.9 |

---

## 24. TESTING CHECKLIST

### 24.1 Authentication & Roles

| Test | Expected Result | Pass |
|:---|:---|:---|
| Login as Shakib (super_admin) | Access to all Filament resources + site creation/deletion | ☐ |
| Login as Shadman (admin) | Access to all content resources, NO site creation/deletion | ☐ |
| Admin tries to create a site | Create button not visible or action blocked | ☐ |
| Admin tries to delete a site | Delete action not available or blocked | ☐ |

### 24.2 Content Management

| Test | Expected Result | Pass |
|:---|:---|:---|
| Create a new page for Narrative site | Page saved in cms_zeplow DB | ☐ |
| Slug auto-generates from title | "About Us" → "about-us" | ☐ |
| Slug can be manually overridden | Custom slug saved correctly | ☐ |
| Content blocks Repeater works | Can add hero, text, CTA blocks; reorder them | ☐ |
| Create a project with images | Images uploaded via Spatie MediaLibrary | ☐ |
| Image conversions generated | Thumbnail, medium, large versions created | ☐ |
| Create a blog post with RichEditor | HTML body saved correctly | ☐ |
| Create a testimonial | Default is_published = true | ☐ |
| Create a team member | is_founder toggle works | ☐ |
| Edit site config navigation | Repeater adds/removes nav items | ☐ |
| Filter pages by site | Only pages for selected site shown | ☐ |
| Filter pages by published status | Correct filtering | ☐ |
| Filter projects by featured | Only featured projects shown | ☐ |
| Add hero block with empty heading, try to save | Filament shows validation error on heading field | ☐ |
| Add cards block with zero cards, try to save | Filament shows "minimum 1 card" error | ☐ |
| Add CTA block with button_text but no button_url | Filament shows validation error on button_url | ☐ |
| Add image block without alt_text | Filament shows validation error on alt_text | ☐ |
| Add stats block with empty stats repeater | Filament shows "minimum 1 stat" error | ☐ |
| Add valid hero block, save page | Saves successfully, content JSON is well-formed | ☐ |

### 24.3 Sync System

| Test | Expected Result | Pass |
|:---|:---|:---|
| Publish a page | Observer dispatches job, sync_logs shows "success" | ☐ |
| Check API received the content | api_zeplow.site_content has the record | ☐ |
| Edit and re-publish a page | API receives updated content | ☐ |
| Delete a project | API receives delete command, content removed | ☐ |
| Save unpublished content | NO sync dispatched (check sync_logs) | ☐ |
| Save a team member (no publish check) | Sync dispatched regardless of is_published | ☐ |
| Update site config | Config synced to API | ☐ |
| Use "Resync All Content" action | All published content re-sent to API | ☐ |
| API is down during publish | Sync job fails after 3 retries, sync_logs shows "failed" | ☐ |
| Publish a page, then unpublish it | deleteContent dispatched, API removes the page | ☐ |
| Unpublish a project | deleteContent dispatched, project removed from API | ☐ |
| Save a draft page (never published) | No sync dispatched at all | ☐ |
| Save a draft page, edit it again (still draft) | No sync dispatched | ☐ |

### 24.4 Media & Images

| Test | Expected Result | Pass |
|:---|:---|:---|
| Upload an image to a project | Image saved in storage/app/public/ | ☐ |
| Image accessible via URL | `cms.zeplow.com/storage/...` returns image | ☐ |
| Image served via Cloudflare CDN | Check `cf-cache-status` header (HIT after first request) | ☐ |
| Image URL included in sync payload | Absolute URL in project data.images array | ☐ |
| Upload exceeds 5 MB | Filament shows validation error | ☐ |
| Upload invalid file type | Filament shows validation error | ☐ |

### 24.5 Dashboard Widgets

| Test | Expected Result | Pass |
|:---|:---|:---|
| Content Overview shows correct counts | Matches actual DB records | ☐ |
| Last Deploy Status shows green for recent success | Correct timestamp from sync_logs | ☐ |
| Last Deploy Status shows red for failure | Error message displayed | ☐ |
| Quick Actions links open correct sites | External links work | ☐ |

---

## 25. POST-LAUNCH CHECKLIST

| # | Task | When |
|:---|:---|:---|
| 1 | Verify cms.zeplow.com/admin/login works | Day 1 |
| 2 | Verify Cloudflare SSL is active on cms.zeplow.com | Day 1 |
| 3 | Verify Cloudflare proxy is active (orange cloud, cf-cache-status header on images) | Day 1 |
| 4 | Add Cloudflare Cache Rule for `cms.zeplow.com/storage/*` with 30-day TTL | Day 1 |
| 5 | Test end-to-end: create content → sync to API → appears on live site | Day 1 |
| 6 | Verify both users can login with correct role permissions | Day 1 |
| 7a | Set up weekly database backup cron job: `mysqldump cms_zeplow > /home/{user}/backups/cms_zeplow_$(date +\%Y\%m\%d).sql` | Day 1 |
| 7b | Set up backup retention: add a cleanup script to delete backups older than 28 days (retain last 4 weekly backups) | Day 1 |
| 7c | Note: The CMS database is the critical backup — the API database can be fully reconstructed using "Resync All" | — |
| 8 | Document the API key in a secure location (not in Git) | Day 1 |
| 9 | Change default passwords if using seeder-generated passwords | Day 1 |
| 10 | Check sync_logs for any failed syncs | Daily for first week, then weekly |
| 11 | Test "Resync All" recovery after simulated failure | Day 2 |
| 12 | Verify image uploads and CDN caching working | Day 2 |
| 13 | Monitor storage/logs/laravel.log for errors | Weekly |
| 14 | Check cPanel disk usage (media uploads) | Monthly |

---

*End of CMS PRD.*
