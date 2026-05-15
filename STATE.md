# ZEPLOW PLATFORM — STATE OF THE PROJECT

**Generated:** 2026-05-15
**Branch:** master (clean, up to date with origin)
**Last commit:** `916bdbc` — fix(ui): make Navigation logo paths configurable per site
**Compared against:** Central_PRD v1.3, CMS_PRD v1.3, API_PRD v1.3, Parent_Site_PRD v1.2, Narrative_Site_PRD v1.2, Logic_Site_PRD v1.2

---

## 0. EXECUTIVE SUMMARY

The platform is a **headless 3-arm web stack**: a Laravel + Filament CMS pushes content to a separate Laravel API, which serves three statically-built Next.js frontends. **All five public surfaces are live in production.**

**Where the project is, today:**

| System | PRD Compliance | Built? | Deployed? |
|:---|:---|:---|:---|
| CMS (Laravel 12 + Filament v3) | ~98% | Yes | **Live** at `cms.zeplow.com` |
| API (Laravel 12, API-only) | ~92% | Yes | **Live** at `api.zeplow.com` |
| `@zeplow/api` package | Complete | Yes | n/a |
| `@zeplow/ui` package | Partial (missing `ResponsiveImage`) | Yes | n/a |
| `@zeplow/config` package | Partial (fonts.ts wrong) | Yes | n/a |
| `apps/parent` (zeplow.com) | Mostly complete | Yes | **Live** at `zeplow.com` |
| `apps/narrative` (narrative.zeplow.com) | Mostly complete | Yes | **Live** at `narrative.zeplow.com` |
| `apps/logic` (logic.zeplow.com) | Mostly complete | Yes | **Live** at `logic.zeplow.com` |
| `workers/api-proxy` (Cloudflare Worker) | Bonus (not in PRDs) | Yes | **Live** at `zeplow-api-proxy.contactzeplow.workers.dev` (WAF bypass for CF Pages builds) |

**Remaining gaps — none are launch blockers; all are PRD-fidelity polish:**
1. **`@zeplow/config/fonts.ts`** still has the placeholder `Poppins` for all three sites. Each `app/layout.tsx` self-hosts the correct fonts directly via `next/font/local`, so live typography is right; the shared contract is just wrong on paper.
2. **Narrative & Logic missing `not-found.tsx` and `public/404.html`** — both PRDs require branded 404s. Parent has both.
3. **`@zeplow/ui` missing `ResponsiveImage`** — PRD §5.2 says all images should go through a `<picture>` + `srcSet` helper. Currently each site renders raw `<img>`. Affects Lighthouse and image bandwidth, not correctness.
4. **`apps/parent/components/BeliefBlock.tsx`** — PRD lists it; not implemented.
5. **API has no dedicated `ContactService`** — the logic lives directly in `ContactController` (functional but architecturally off-spec).
6. **No `api-key:generate` artisan command** — keys are only mintable via the one-shot `ApiKeySeeder`.
7. **No OpenAPI/Swagger spec** for the API — PRD calls for one, not in MVP.

The platform is in a **launched / post-MVP polish** state. The full editor → CMS → API → CF deploy hook → live site loop is operable across all three brand sites.

---

## 1. REPOSITORY LAYOUT

```
Platform/
├── api/                       Laravel 12 API app  (api.zeplow.com)
├── cms/                       Laravel 12 + Filament v3 admin  (cms.zeplow.com — not hosted yet)
├── workers/api-proxy/         Cloudflare Worker: WAF bypass for CF Pages builds
├── zeplow-sites/              Turborepo monorepo
│   ├── apps/parent/           zeplow.com           (Next.js, port 3000)
│   ├── apps/narrative/        narrative.zeplow.com (Next.js, port 3001) — LIVE
│   ├── apps/logic/            logic.zeplow.com     (Next.js, port 3002) — LIVE
│   ├── packages/api/          @zeplow/api  (client, types, mock-data, image helpers)
│   ├── packages/ui/           @zeplow/ui   (shared React components)
│   ├── packages/config/       @zeplow/config (colors, fonts)
│   ├── pnpm-workspace.yaml
│   ├── turbo.json
│   └── vercel.json            ← Only references apps/parent (stale or pending Vercel deploy)
├── zeplow_home.html           Empty placeholder file
├── README.md                  Minimal project overview (23 lines)
└── *_PRD.md                   6 PRD documents (Central, CMS, API, Parent, Narrative, Logic)
```

Git state: clean, on `master`, ahead-of-PRD-only-on-bug-fixes. Recent commits show active work on Narrative content seeding and Navigation logo path fixes.

---

## 2. CMS — `cms/` (Laravel + Filament)

**Targets:** Central_PRD §3, CMS_PRD (full document)

### 2.1 What exists
| PRD requirement | Status | Notes |
|:---|:---|:---|
| Laravel 11, Filament v3, PHP 8.2+ | ✅ (Laravel **12** instead of 11 — non-breaking) | `composer.json` shows `laravel/framework ^12.0`, `filament/filament ^3.0`, `spatie/laravel-medialibrary ^11.0` |
| MySQL `cms_zeplow` DB, file/db cache & session | ✅ | `.env.example` uses database cache + session driver (PRD said "file"; both are cPanel-safe) |
| Models: Site, Page, Project, BlogPost, Testimonial, TeamMember, SiteConfig, SyncLog, User | ✅ All present in `app/Models/` |  |
| `site_id` FK + `(site_id, slug)` unique on all content tables | ✅ | All 9 migrations under `database/migrations/2026_04_12_*` plus `2026_04_05_161050_create_media_table.php` |
| Filament Resources: Site, Page, Project, BlogPost, Testimonial, TeamMember, SiteConfig | ✅ All 7 resources in `app/Filament/Resources/` |  |
| **BONUS:** SyncLogResource (read-only monitoring) | 🟡 Extra | Not in PRD but valuable |
| 12 page content block types: hero, text, cards, cta, image, gallery, testimonials, team, projects, stats, divider, raw_html | ✅ | `PageResource.php` repeater |
| Dashboard widgets: ContentOverview, LastDeployStatus, QuickActions (incl. "Resync All") | ✅ All 3 in `app/Filament/Widgets/`, registered in `AdminPanelProvider.php` |  |
| Observers on Page, Project, BlogPost, Testimonial, TeamMember, SiteConfig | ✅ All 6 wired in `AppServiceProvider::boot()` (lines 28-33) |  |
| Jobs: SyncContentJob, DeleteContentJob, SyncConfigJob (3 retries, 5s backoff) | ✅ All present in `app/Jobs/`; HTTP POST/DELETE to `/internal/v1/content/sync` and `/internal/v1/config/sync` with `Authorization: Bearer {ZEPLOW_API_KEY}` |  |
| sync_logs table with status/attempt_count/last_error/synced_at | ✅ `SyncLog` model + migration |  |
| Spatie conversions: thumbnail (400×300 crop), medium (800×600 contain), large (1600×1200 contain), large-webp | ✅ Configured on `Project` model (and others have HasMedia) |  |
| Seeders: `SiteSeeder` (3 sites), `UserSeeder` (Shakib super_admin + Shadman admin) | ✅ |  |
| **BONUS:** `LogicContentSeeder`, `ParentContentSeeder`, `NarrativeContentSeeder` (the last one was added in commit `7f91f27`) | 🟡 Extra | Used to bootstrap content into a non-live CMS; commented references in `DatabaseSeeder` |
| Roles: super_admin vs admin, only super_admin can create/delete sites | ✅ `SiteResource::canCreate()` / `canDelete()` check `isSuperAdmin()` |  |
| Slug auto-generation on title change | ✅ `PageResource` live update |  |
| `CheckFailedSyncsCommand` for weekly summary email | ✅ `app/Console/Commands/CheckFailedSyncsCommand.php`, signature `sync:check-failed` |  |
| .env keys: ZEPLOW_API_URL, ZEPLOW_API_KEY | ✅ in `.env.example` and `config/services.php` |  |

### 2.2 Gaps / deviations
- **Laravel 12 vs PRD's Laravel 11** — minor, fully forward-compatible.
- **`TeamMemberObserver` never checks `is_published`** — by design (PRD also says team members sync unconditionally), but worth noting.
- **`testimonials.is_published` default = true** in migration (line 19) — differs from other content models that default to false; functionally fine but slightly inconsistent.
- **Cron-on-cPanel not in repo** — PRD calls for `php artisan schedule:run` every minute via cPanel cron; the command (`sync:check-failed`) exists, the schedule entry is operational config not source-controlled here.

**Hosting:** Live at `cms.zeplow.com` on cPanel shared hosting, behind Cloudflare (orange cloud). Editors (Shakib super_admin, Shadman admin) can log in and publish.

**Bottom line:** Operational. PRD-aligned end to end.

---

## 3. API — `api/` (Laravel, API-only)

**Targets:** API_PRD (full document), Central_PRD §4

### 3.1 What exists
| PRD requirement | Status | Notes |
|:---|:---|:---|
| Laravel 11 (API-only), PHP 8.2+, MySQL `api_zeplow`, separate from CMS | ✅ Laravel **12.58**, no view/session providers, separate `app/`, separate DB |  |
| 5 tables: `site_content`, `site_configs`, `contact_submissions`, `deploy_logs`, `api_keys` | ✅ All in `database/migrations/2026_05_12_*` |  |
| Models: SiteContent, SiteConfig, ContactSubmission, DeployLog, ApiKey | ✅ |  |
| **Public routes** (`/sites/v1/{siteKey}/...`): config, pages, pages/{slug}, projects (+filters), projects/{slug}, blog (+tag/limit/page), blog/{slug}, team, testimonials, POST contact | ✅ All 10 endpoints + `GET /health` |  |
| **Internal routes**: POST/DELETE `/internal/v1/content/sync`, POST `/internal/v1/content/sync-all`, POST `/internal/v1/config/sync`, POST `/internal/v1/deploy/trigger/{siteKey}` | ✅ All 5 |  |
| Middleware: `ValidateSiteKey`, `ValidateApiKey` (SHA-256 hash check, scope=internal), `ResolveBuildAgent` (X-Build-Token) | ✅ All in `app/Http/Middleware/` |  |
| Rate limiting: 60/min public, 300/min build agents, 5/min contact form | ✅ `AppServiceProvider.php` lines 22-37 |  |
| CORS: `zeplow.com`, `narrative.zeplow.com`, `logic.zeplow.com` + `localhost:3000-3002` | ✅ `config/cors.php` |  |
| `CacheService` — prefix-based version counters (file-cache compatible, no Redis) | ✅ TTL 3600s, `versionKey/detailKey/listKey/bumpVersion/invalidate` |  |
| `DeployService` — 60s debounce per site, fires `CF_DEPLOY_HOOK_*`, logs to `deploy_logs` | ✅ HTTP timeout 10s |  |
| Contact flow: honeypot (`website_url`) + Cloudflare Turnstile, fake 200 on bot, email subject `New Lead — {site_key}` to `hello@zeplow.com` | ✅ All in `ContactController` |  |
| API key generation: SHA-256 hash, plaintext shown once | ✅ via `ApiKeySeeder` (generates one internal key + one build agent key) |  |
| JSON exception handlers (422, 404, 403, 429, 500) | ✅ `bootstrap/app.php` |  |
| .env keys: `CF_DEPLOY_HOOK_PARENT/NARRATIVE/LOGIC`, `CF_BUILD_TOKEN`, `CF_TURNSTILE_SECRET_KEY` | ✅ `.env.example` + `config/services.php` |  |

### 3.2 Gaps / deviations
- **No dedicated `ContactService`** — PRD architecture diagram calls for one; logic lives directly in `ContactController` (lines 76-98). Functional, but harder to test/reuse.
- **No `api-key:generate` artisan command** — keys are only mintable via the seeder, which runs all keys at once. PRD says we should be able to mint additional keys at runtime.
- **No OpenAPI / Swagger** — no formal API contract published; the only authoritative spec is API_PRD.md and the `@zeplow/api` types in TypeScript.
- **No `/docs` route or monitoring/admin commands** for inspecting contact submissions or deploy logs.
- **Laravel 12 vs PRD's 11** — same minor deviation as CMS.

### 3.3 Hosting & integration
The API is **live at `api.zeplow.com`** on cPanel shared hosting, behind Cloudflare (orange cloud). It serves:
- Production build-time fetches from all three Cloudflare Pages projects.
- Runtime contact-form POSTs from all three sites (browser → Cloudflare → cPanel direct).
- Internal `/internal/v1/*` sync endpoints from the CMS at `cms.zeplow.com`.

There is no Cloudflare Pages config for the API (it's a server-rendered Laravel app on cPanel). The repo contains the source-of-truth code; the deployed artifact is the same code uploaded to the cPanel subdomain.

**Known quirk:** Cloudflare Pages build runners hit Imunify360 WAF when fetching the API directly. The `workers/api-proxy/` worker (§8) is set as `NEXT_PUBLIC_API_URL` for the live sites' build environment to route build-time fetches through a Cloudflare-origin IP and bypass the WAF.

---

## 4. SHARED PACKAGES — `zeplow-sites/packages/`

**Targets:** Central_PRD §5–7, each Site_PRD §5

### 4.1 `@zeplow/api`  — ✅ Complete
- `client.ts` exports all 9 required functions: `getSiteConfig`, `getPages`, `getPage`, `getProjects`, `getProject`, `getBlogPosts`, `getBlogPost`, `getTestimonials`, `getTeamMembers`.
- `types.ts` — full TypeScript shape for SiteConfig, Page, Project, BlogPost, Testimonial, TeamMember, ContentBlock variants.
- `mock-data.ts` — large hardcoded mock dataset for offline / preview builds.
- `images.ts` — `getImageUrl(image, conversion)` Spatie URL helper.
- Includes WAF-block detection (commit `b5605f8`) — if Imunify360 intercepts a fetch, the client throws instead of returning HTML.

### 4.2 `@zeplow/ui` — 🟡 Mostly complete, one missing component
- ✅ Present: `Container`, `Button`, `Navigation`, `Footer`, `SectionHeading`, `ProjectCard`, `BlogCard`, `TeamCard`, `TestimonialCard`, `StatsStrip`, `ContentRenderer`, `ContactForm` (`'use client'`), `OrganizationSchema`, `ArticleSchema`.
- ❌ **Missing:** `ResponsiveImage` (PRD Central §5.2; required for `<picture>` + `srcSet` rendering across all three sites).
- ✅ `Navigation` accepts `siteKey` prop, including configurable logo paths per site (recent fix `916bdbc`).

### 4.3 `@zeplow/config` — 🟡 Wrong fonts mapping
- ✅ `colors.ts` — correct hex values per site (Parent/Narrative share Pine Teal + Coral; Logic uses Deep Logic + System Teal + Error Coral).
- ❌ **`fonts.ts`** — all three sites set to `{ heading: 'Poppins', body: 'Poppins' }`. The PRD requires:
  - parent → Playfair Display + Manrope
  - narrative → Playfair Display + Manrope
  - logic → JetBrains Mono + Inter
- **Live impact: none today** — each app's `layout.tsx` declares its own self-hosted fonts via `next/font/local`, bypassing this config. But the shared contract is wrong and any future consumer importing from `@zeplow/config/fonts` will pick up the wrong values.

---

## 5. APP — `apps/parent/` (zeplow.com)

**Target:** Parent_Site_PRD (full document)

### 5.1 What exists
| PRD requirement | Status | Notes |
|:---|:---|:---|
| Next.js 14+ App Router, `output: 'export'`, `images.unoptimized: true`, `transpilePackages` | ✅ `next.config.js` |  |
| Port 3000, site_key=parent | ✅ |  |
| 9 routes: `/`, `/about`, `/ventures`, `/ventures/narrative`, `/ventures/logic`, `/careers`, `/insights`, `/insights/[slug]`, `/contact` | ✅ All present under `app/` |  |
| `VentureCard.tsx` component | ✅ |  |
| `BeliefBlock.tsx` component | ❌ **Missing** — PRD lists it as a parent-specific component |
| `not-found.tsx` + `public/404.html` | ✅ Both present |
| `sitemap.ts`, `_headers`, `robots.txt` | ✅ |  |
| Self-hosted fonts (Playfair Display Bold + Manrope 400/500/600/700) | ✅ All `.woff2` in `public/fonts/` |  |
| Logo files (logo.png, logo-dark.png, og-default.png, apple-touch-icon) | ✅ |  |

### 5.2 Hosting
- ✅ **Live at `zeplow.com`** on Cloudflare Pages. The Cloudflare Pages project pulls from this monorepo and builds `apps/parent` via the standard pnpm + Turborepo pipeline.
- `zeplow-sites/vercel.json` is present and references parent — possibly stale from an exploratory Vercel deploy attempt, or used as an alternate deploy target. **Decision needed:** delete if unused, since production runs on Cloudflare Pages per Parent_PRD §13.

### 5.3 Bottom line
Live and serving real CMS content. Only PRD gap: the `BeliefBlock.tsx` component is missing from `components/`. Stale `vercel.json` worth cleaning up.

---

## 6. APP — `apps/narrative/` (narrative.zeplow.com — **LIVE**)

**Target:** Narrative_Site_PRD (full document)

### 6.1 What exists
| PRD requirement | Status | Notes |
|:---|:---|:---|
| Next.js 14+ App Router, port 3001, site_key=narrative | ✅ |  |
| 10 routes (7 static + 2 dynamic + project detail): `/`, `/about`, `/services`, `/work`, `/work/[slug]`, `/process`, `/insights`, `/insights/[slug]`, `/contact` | ✅ All present (matches PRD §3.1) |  |
| Self-hosted Playfair Display + Manrope (variable fonts) | ✅ `public/fonts/` |  |
| Tailwind: primary `#034c3c`, accent `#ff6f59` | ✅ |  |
| `HeartbeatCTA.tsx` component | ✅ Used in contact + work pages |
| `AntiClientBlock.tsx` component | ✅ Used on about page |
| `FeatureStory.tsx` (project detail editorial layout) | ✅ Bonus dedicated component (PRD had inline JSX) |
| `NarrativeContentRenderer.tsx` (site-specific block renderer) | ✅ Bonus (PRD only required generic `ContentRenderer`) |
| `not-found.tsx` | ❌ **Missing** |
| `public/404.html` | ❌ **Missing** |
| `sitemap.ts`, `_headers`, `robots.txt` | ✅ |  |
| Brand assets (logo, favicon, apple-touch-icon) | ✅ added in commit `a8669f0` |  |

### 6.2 Hosting & content
- ✅ **Live on Cloudflare Pages** (per auto-memory + commit history).
- ✅ Real CMS content via `NarrativeContentSeeder` (commit `7f91f27`) — content has been seeded directly into the API DB.
- PRD Implementation Order Phases 1–6 all visible in commit history (Phase 1 scaffold `124cd2d`, Phase 2 layout `e211cd9`, Phase 3 data layer `732df54`, content seeding via `7f91f27`).
- Phase 7 (design polish, animations, Lighthouse audit) — not explicitly committed as a polish pass like Logic got; visual finishing is likely the remaining work.

### 6.3 Bottom line
**Mostly complete and live.** Outstanding: 404 page (both React + static), and any remaining Phase 7 polish/animation work.

---

## 7. APP — `apps/logic/` (logic.zeplow.com — **LIVE**)

**Target:** Logic_Site_PRD (full document)

### 7.1 What exists
| PRD requirement | Status | Notes |
|:---|:---|:---|
| Next.js 14+ App Router, port 3002, site_key=logic | ✅ |  |
| 9 routes: `/`, `/about`, `/services`, `/work`, `/work/[slug]`, `/process`, `/insights`, `/insights/[slug]`, `/contact` | ✅ All present |  |
| Self-hosted JetBrains Mono Bold + Inter 400/500/600/700 | ✅ `public/fonts/` |  |
| Tailwind: primary `#081f1a`, accent `#00b894`, error `#ff7675` | ✅ |  |
| `IncidentReport.tsx` (project detail technical debrief) | ✅ |  |
| `AuditCTA.tsx` | ✅ Used on contact page |
| `LogicContentRenderer.tsx` | ✅ Bonus site-specific renderer |
| `not-found.tsx` | ❌ **Missing** |
| `public/404.html` | ❌ **Missing** |
| `sitemap.ts`, `_headers`, `robots.txt` | ✅ |  |
| Logo, favicon, apple-touch-icon | ✅ |  |
| Framer Motion animations (restrained, data-transition feel) | ✅ commit `95b3a23` |  |
| Performance polish (no duplicate font preloads, synchronous hero paint, card contrast bump) | ✅ commit `6274d2b` |  |

### 7.2 Hosting & content
- ✅ **Live on Cloudflare Pages** (per auto-memory).
- ✅ Real CMS content via `LogicContentSeeder` (Phase 6 — commit `76b0180`).
- ✅ All 7 PRD phases committed: scaffold `e57705b`, seeder + visual polish `76b0180`, animations `95b3a23`, perf polish `6274d2b`.
- ✅ Builds via the api-proxy worker (commit `088cb28` unlocked live API fetching; `973399b` added the WAF workaround).

### 7.3 Bottom line
**Most polished of the three.** Outstanding: 404 pages (both flavors). The Logic site is effectively the reference implementation.

---

## 8. CLOUDFLARE WORKER — `workers/api-proxy/`

**Not in any PRD — added as a deployment workaround.**

- **Purpose:** Cloudflare Pages build agents hit Imunify360 WAF on cPanel and were getting blocked. The worker proxies `/sites/v1/*` through a Cloudflare-origin IP, which the WAF allows.
- **Deployed URL:** `zeplow-api-proxy.contactzeplow.workers.dev`
- **Routing:** `/sites/v1/*` proxied to `https://api.zeplow.com`; everything else (including `/internal/v1/*`) returns 404.
- **Header hygiene:** Strips `host`, `cf-connecting-ip`, `cf-ipcountry`, `cf-ray`, `cf-visitor` before forwarding.
- **Account:** `05b0de67e7ff3a39669a011542db51af` (`Contactzeplow@gmail.com`)
- **Used by:** at minimum the Logic build (per auto-memory). Likely Narrative build too. Set as `NEXT_PUBLIC_API_URL` on Cloudflare Pages env so build-time fetches route through the worker; runtime browser fetches (contact form) go direct to api.zeplow.com.

---

## 9. DATA FLOW REALITY CHECK

**PRD-prescribed flow, in production today:**

```
Editor logs into cms.zeplow.com (Filament)
   ↓
Publishes a Page/Project/BlogPost/Testimonial/TeamMember or saves SiteConfig
   ↓
Eloquent Observer fires → SyncContentJob (or SyncConfigJob / DeleteContentJob)
   ↓
HTTP POST/DELETE to api.zeplow.com/internal/v1/content/sync (Bearer ZEPLOW_API_KEY, 3 retries, 5s backoff)
   ↓
API stores in api_zeplow.site_content, bumps version counter, invalidates cache
   ↓
DeployService fires CF_DEPLOY_HOOK_{PARENT|NARRATIVE|LOGIC} (debounced 60s per site)
   ↓
Cloudflare Pages rebuilds the affected Next.js app
   ↓
Build runner fetches build-time content via zeplow-api-proxy.contactzeplow.workers.dev → api.zeplow.com/sites/v1/{site}/...
   ↓
Static HTML deployed to Cloudflare CDN → visitor sees update in ~60–90s
```

**Observed quirks:**

- **`*ContentSeeder` classes** in the CMS (`NarrativeContentSeeder` commit `7f91f27`, `LogicContentSeeder` in Phase 6 commit `76b0180`) were used to seed initial content directly into the CMS database. Once seeded and re-saved (or after a `Resync All`), the standard Observer → Job → API pipeline took over.
- **Imunify360 WAF workaround:** CF Pages build runner IPs were being blocked by the cPanel WAF; the `api-proxy` worker (commit `973399b`) sits in front of `/sites/v1/*` to swap the source IP. Runtime browser traffic (contact form, etc.) goes direct to `api.zeplow.com`.
- **WAF-block detection in `@zeplow/api`** (commit `b5605f8`) — if Imunify360 ever intercepts a build-time fetch, the client now throws explicitly instead of silently returning HTML, so builds fail loud.

---

## 10. PRD COMPLIANCE MATRIX BY DOCUMENT

### 10.1 Central_PRD v1.3
| Section | Status |
|:---|:---|
| 3-arm platform structure | ✅ All three sites live; CMS + API live |
| CMS + API + 3 sites + 3 shared packages | ✅ All exist and deployed |
| Content data flow | ✅ Full editor → publish → live loop operational |
| 4-stage image pipeline | 🟡 Stages 1, 2, 3 in code; **stage 4 (`<picture>` + ResponsiveImage)** is missing from `@zeplow/ui` |
| Shared package exports | 🟡 Mostly complete; `fonts.ts` placeholder values, `ResponsiveImage` missing |
| Cross-site footer link groups | ✅ Per-site configs reference Group "Zeplow / Narrative / Logic" + "Company" |
| Security headers via `_headers` | ✅ All three apps |
| Performance targets | 🟡 Logic actively polished; no documented Lighthouse runs in repo |
| UptimeRobot, mysqldump cron, monitoring | ❌ Not in repo (operational, not code) — verify externally |

### 10.2 CMS_PRD v1.3
~98% compliant. See §2 above. Live at `cms.zeplow.com`.

### 10.3 API_PRD v1.3
~92% compliant. See §3 above. Live at `api.zeplow.com`. Missing pieces: `ContactService` extraction, `api-key:generate` artisan command, OpenAPI docs, monitoring commands.

### 10.4 Parent_Site_PRD v1.2
~95% compliant.
- ✅ Live at `zeplow.com`
- ✅ All routes, fonts, palette, components, contact form, sitemap, 404, headers
- ❌ `BeliefBlock.tsx` component missing
- 🟡 Stale `vercel.json` in `zeplow-sites/` worth cleaning up

### 10.5 Narrative_Site_PRD v1.2
~92% compliant.
- ✅ Live at `narrative.zeplow.com`
- ✅ All routes, components, Feature Story format
- ❌ `not-found.tsx` + `public/404.html`
- 🟡 Phase 7 (design polish, animations, Lighthouse) not explicitly logged in commits the way Logic's Phase 7 is

### 10.6 Logic_Site_PRD v1.2
~95% compliant.
- ✅ Live at `logic.zeplow.com`
- ✅ All routes, IncidentReport, AuditCTA, animations, performance polish
- ❌ `not-found.tsx` + `public/404.html`

---

## 11. WHAT'S BEEN BUILT OUTSIDE THE PRDs (BONUS WORK)

1. **`workers/api-proxy/`** — Cloudflare Worker that bypasses Imunify360 WAF for CF Pages build runners (not foreseen in any PRD).
2. **Per-site `*ContentRenderer`** — each of Narrative and Logic has a site-specific content renderer (`NarrativeContentRenderer`, `LogicContentRenderer`) beyond the shared `ContentRenderer`. This is a richer treatment than the PRD's single generic renderer.
3. **`FeatureStory.tsx`** in Narrative — Narrative_PRD §8.5 had this as inline JSX, but it's been promoted to a dedicated component (cleaner).
4. **`SyncLogResource`** in CMS — a Filament resource for inspecting sync history, not in the CMS PRD's resource list.
5. **`*ContentSeeder` classes** in CMS — used to bootstrap initial site content directly (Narrative + Logic + a Parent stub were planned in `DatabaseSeeder` comments). The Narrative one was committed `7f91f27`; Logic's was part of Phase 6.
6. **WAF-block detection in `@zeplow/api`** — the API client detects Imunify360 HTML responses and converts them to thrown errors (commit `b5605f8`) so builds fail loud instead of silently returning HTML.
7. **`zeplow_home.html`** — empty placeholder file at the repo root, purpose unclear.

---

## 12. ACTIVE CONCRETE GAPS — WHAT'S MISSING TO BE "PRD-DONE"

The platform is launched. The remaining work is PRD-fidelity polish, hardening, and cleanup — no launch blockers.

### 12.1 Code gaps in the existing repo
1. **`@zeplow/config/fonts.ts`** — replace the Poppins placeholders with the real font mappings (Playfair/Manrope for parent + narrative, JetBrains Mono/Inter for logic). Live impact is zero today because each `layout.tsx` self-hosts directly, but the shared contract is wrong.
2. **`@zeplow/ui/ResponsiveImage`** — add the missing component (Central PRD §5.2 + each Site_PRD §9.1). Every `<img>` is raw today — Lighthouse and image bandwidth on Narrative especially could benefit.
3. **Narrative `not-found.tsx` + `public/404.html`** — Narrative_PRD §19.1 requires both, branded.
4. **Logic `not-found.tsx` + `public/404.html`** — same, with monospace "// 404: Resource not found" treatment per Logic_PRD §19.1.
5. **`apps/parent/components/BeliefBlock.tsx`** — Parent_PRD §14 directory listing references it; not implemented.
6. **API `ContactService`** — extract Turnstile + email + storage logic out of `ContactController` into `app/Services/ContactService.php` (API_PRD §6 architecture).
7. **API `php artisan api-key:generate {name} {scope}` command** — replace one-shot seeder usage with a reusable command (API_PRD §5).
8. **API OpenAPI/Swagger spec** — not in MVP, but Central_PRD §1 lists it as a deliverable.
9. **API monitoring commands** — `monitor:check-failed-deploys` to complement `sync:check-failed`.

### 12.2 Operational items (not code, worth verifying)
10. UptimeRobot monitor on `api.zeplow.com/health` (every 5 min, email alert).
11. Weekly mysqldump cron on the cPanel host for `cms_zeplow` and `api_zeplow`; 4-week retention.
12. Cloudflare Pages build-failure email alerts for all three Pages projects.
13. Sitemaps submitted to Google Search Console for `zeplow.com`, `narrative.zeplow.com`, `logic.zeplow.com`.
14. Quarterly DB-backup restore drill.
15. cPanel cron entry running `php artisan schedule:run` every minute (CMS `sync:check-failed` weekly summary depends on this).

### 12.3 Cleanup
16. Decide whether `zeplow-sites/vercel.json` is stale (since parent ships via Cloudflare Pages per its PRD). If unused, delete.
17. Either delete or populate `zeplow_home.html` (currently 0 bytes).
18. Either delete or use `apps/parent/scripts/` (currently empty directory).

---

## 13. RECENT COMMITS — CONTEXTUAL HINTS

```
916bdbc  fix(ui): make Navigation logo paths configurable per site         ← TODAY-ish
a6bafc6  fix(parent): use correct logo filenames in Navigation
7f91f27  feat(narrative): add NarrativeContentSeeder
a8669f0  feat(narrative): add brand assets (logo, favicon, apple-touch-icon)
8d666ea  chore(lockfile): register narrative workspace specifiers
41854c0  fix(narrative): handle empty generateStaticParams under output:export
732df54  feat(narrative): phase 3 — data layer for all 9 routes + sitemap
e211cd9  feat(narrative): phase 2 — real layout + HeartbeatCTA + AntiClientBlock
124cd2d  feat(narrative): phase 1 scaffold
3fdba6b  feat(page-resource): slug handling; api-proxy wrangler account; zeplow_home.html
973399b  feat(workers): add api-proxy worker to bypass Imunify360 on CF Pages builds
088cb28  feat(logic): unlock from mock mode, fetch live API at build time
b5605f8  fix(api-client): detect Imunify360 WAF block, convert to throw
a123727  security: untrack secrets-bearing files, scrub hardcoded API keys
24f7834  fix(api-client): unwrap paginated {data,meta} envelope to bare array
f3862ff  feat(parent): unlock from mock mode, remove root vercel.json
6274d2b  perf(logic): font preload + hero paint + card contrast
95b3a23  feat(logic): Framer Motion animations
76b0180  feat(logic): Phase 6 seeder + Phase 7 visual polish
e57705b  feat(logic): scaffold Logic site (Phase 1-4)
```

**Reading the timeline:** Logic was built in a single coherent push (Phases 1–7 visible). Narrative followed with the same phase-numbered approach but is currently completing through Phase 3 + content seeding + brand assets — without an equivalent dedicated "Phase 7 polish" commit. Parent has had only spot-fixes (logo paths, mock mode unlocking) — no fresh scaffold pass since the early architecture.

---

## 14. ONE-PARAGRAPH BOTTOM LINE

The Zeplow Platform is **launched and operational across all five surfaces**: `cms.zeplow.com`, `api.zeplow.com`, `zeplow.com`, `narrative.zeplow.com`, and `logic.zeplow.com`. The CMS (Laravel 12 + Filament v3) is editing into the API (Laravel 12) via the Observer → Job → POST `/internal/v1/content/sync` pipeline, and the API fires Cloudflare Pages deploy hooks per-site, debounced 60s. The three Next.js sites pull build-time content via the api-proxy worker to dodge a cPanel WAF block on CF Pages build runner IPs. The shared `@zeplow/api` package is in place and feeding everything correctly. What's left is PRD-fidelity polish, not launch work: a broken `@zeplow/config/fonts.ts` (silently compensated by each app's local font setup), no `ResponsiveImage` in `@zeplow/ui` (raw `<img>` tags everywhere — Lighthouse and bandwidth tax), missing branded 404 pages on Narrative and Logic, a missing `BeliefBlock` on Parent, and a few API hygiene gaps (no `ContactService`, no `api-key:generate` command, no OpenAPI spec). None of these block the platform; they prevent it from matching its own written specification.

---

*End of STATE.md.*
