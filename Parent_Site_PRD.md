# ZEPLOW PARENT SITE (zeplow.com) — PRODUCT REQUIREMENTS DOCUMENT (PRD)

**Version:** 1.2
**Date:** March 27, 2026
**Derived From:** Zeplow Platform Central PRD v1.2, Company Profile, Brand Documents
**Original Author:** Shakib Bin Kabir
**Status:** Final — Ready for Implementation

---

## TABLE OF CONTENTS

1. Site Overview & Purpose
2. Site Identity & Brand System
3. Information Architecture — Routes & Pages
4. Monorepo Context — Where This Site Lives
5. Shared Packages (API Client, UI, Config)
6. Next.js Configuration
7. Root Layout
8. Page-by-Page Specifications
9. Content Block Rendering
10. Contact Form
11. SEO Strategy
12. Performance Requirements
13. Cloudflare Pages — Build & Deploy
14. DNS & Domain Configuration
15. Security Headers
16. Content Seeding — What to Publish in CMS
17. Environment Variables
18. Directory Structure
19. Error Handling
20. Implementation Order
21. Testing Checklist
22. Post-Launch Checklist
23. Known Limitations

---

## 1. SITE OVERVIEW & PURPOSE

### 1.1 What This Site Is

zeplow.com is the **parent holding company website** for the Zeplow group. It is not a service site — it does not sell creative or tech services directly. It is the authority and credibility hub: the place investors, partners, potential hires, and curious visitors land to understand what Zeplow is, who runs it, and what companies sit under it.

Think of it like the Berkshire Hathaway website, but for a two-venture holding company built by two co-founders in Dhaka.

### 1.2 Properties

| Property | Value |
|:---|:---|
| Domain | `zeplow.com` (+ `www.zeplow.com` redirect to apex) |
| Framework | Next.js 14+ (App Router, static export) |
| Styling | Tailwind CSS v3 |
| Animation | Framer Motion |
| Output mode | Static export (`output: 'export'`) |
| Hosting | Cloudflare Pages (free tier) |
| Data source | `api.zeplow.com` (fetched at build time) |
| site_key | `parent` |
| Repository | `zeplow-sites` monorepo → `apps/parent/` |

### 1.3 What Makes This Site Unique vs. Narrative & Logic

| Aspect | Parent (zeplow.com) | Narrative / Logic |
|:---|:---|:---|
| Purpose | Group overview, venture hub | Service-specific agency/company site |
| Has /services | No | Yes |
| Has /work (portfolio) | No | Yes |
| Has /process | No | Yes |
| Has /ventures | Yes (unique to parent) | No |
| Has /careers | Yes (placeholder) | No |
| Tone | Foundational, quiet confidence | Narrative: provocative storyteller / Logic: calm architect |
| Content focus | Who we are, what we believe, our ventures | What we do, how we do it, proof |

### 1.4 Target Audience

- **Partners & investors** evaluating the Zeplow group
- **Potential clients** who found Zeplow before finding Narrative or Logic specifically
- **Potential hires** exploring the company culture
- **Industry peers** assessing credibility
- **Referral traffic** from Narrative/Logic sites linking back to the parent

### 1.5 Non-Goals

- Selling creative services directly (that's narrative.zeplow.com)
- Selling tech services directly (that's logic.zeplow.com)
- Client portal or dashboard
- Job application system (careers is a placeholder page)
- E-commerce or payment processing

---

## 2. SITE IDENTITY & BRAND SYSTEM

### 2.1 Brand Position

| Element | Value |
|:---|:---|
| Name | Zeplow |
| Tagline | "Story. Systems. Ventures." |
| Origin | Zephyr (wind/movement) + Plow (foundation/discipline) |
| Personality | Foundational, quiet confidence, empire builder |
| Role | "The company behind companies." |

### 2.2 Color Palette

| Color | Hex | CSS Variable | Usage |
|:---|:---|:---|:---|
| Pine Teal | `#034c3c` | `--color-primary` | Primary, headers, hero backgrounds |
| White Smoke | `#f4f4f4` | `--color-background` | Page backgrounds |
| Coffee Bean | `#140004` | `--color-text` | Body text (warmer than pure black) |
| Vibrant Coral | `#ff6f59` | `--color-accent` | CTAs, accents, hover states |

The parent site shares the Narrative color palette. This is intentional — Narrative is the "creative DNA" of the parent brand. Logic has its own sharper palette.

### 2.3 Typography

| Role | Font | Weight | Fallback |
|:---|:---|:---|:---|
| Headings (H1–H3) | Playfair Display | 700 (Bold) | Georgia, serif |
| Body / UI | Manrope | 400, 500, 600 | system-ui, sans-serif |

**Self-hosted fonts only.** Download font files into `public/fonts/`. No Google Fonts CDN calls. Use `next/font/local` for loading.

### 2.4 Tailwind Configuration

```typescript
// apps/parent/tailwind.config.ts
import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
    '../../packages/ui/src/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#034c3c',
        background: '#f4f4f4',
        text: '#140004',
        accent: '#ff6f59',
      },
      fontFamily: {
        heading: ['var(--font-playfair)', 'Georgia', 'serif'],
        body: ['var(--font-manrope)', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
};

export default config;
```

---

## 3. INFORMATION ARCHITECTURE — ROUTES & PAGES

### 3.1 Complete Route Map

zeplow.com has **9 routes** (8 static + 1 dynamic):

| Route | Page | Template | Description |
|:---|:---|:---|:---|
| `/` | Home | `home` | Hero, belief statement, featured ventures, featured projects, CTA |
| `/about` | About | `about` | Company story, vision/mission, values, team members |
| `/ventures` | Ventures Overview | `ventures` | Overview of both venture arms with links |
| `/ventures/narrative` | Narrative Overview | `ventures` | What Zeplow Narrative does, from the parent's perspective |
| `/ventures/logic` | Logic Overview | `ventures` | What Zeplow Logic does, from the parent's perspective |
| `/insights` | Blog Listing | `insights` | All published blog posts for parent site |
| `/insights/[slug]` | Blog Post Detail | — | Individual blog post (dynamic route) |
| `/careers` | Careers | `careers` | Placeholder page ("We're growing. Stay tuned.") |
| `/contact` | Contact | `contact` | Contact form + company info |

### 3.2 Data Source Per Route

| Route | API Calls at Build Time |
|:---|:---|
| `/` | `getPage('parent', 'home')` + `getProjects('parent', { featured: true, limit: 3 })` |
| `/about` | `getPage('parent', 'about')` + `getTeamMembers('parent')` |
| `/ventures` | `getPage('parent', 'ventures')` |
| `/ventures/narrative` | `getPage('parent', 'ventures-narrative')` |
| `/ventures/logic` | `getPage('parent', 'ventures-logic')` |
| `/insights` | `getBlogPosts('parent')` |
| `/insights/[slug]` | `getBlogPost('parent', slug)` — uses `generateStaticParams` |
| `/careers` | `getPage('parent', 'careers')` |
| `/contact` | `getPage('parent', 'contact')` |

### 3.3 Navigation Structure

Driven by API config (`getSiteConfig('parent')`). Expected nav items:

| Label | URL | External |
|:---|:---|:---|
| About | /about | No |
| Ventures | /ventures | No |
| Insights | /insights | No |
| Careers | /careers | No |
| Contact | /contact | No |

CTA button in nav: "Get in Touch" → `/contact`

---

## 4. MONOREPO CONTEXT — WHERE THIS SITE LIVES

### 4.1 Repository Structure

The parent site is one of three apps in a Turborepo monorepo:

```
zeplow-sites/
├── apps/
│   ├── parent/          ← THIS SITE (zeplow.com)
│   ├── narrative/       ← narrative.zeplow.com
│   └── logic/           ← logic.zeplow.com
├── packages/
│   ├── ui/              ← Shared React components
│   ├── api/             ← Shared API client + TypeScript types
│   └── config/          ← Shared brand colors + font mappings
├── turbo.json
├── package.json
└── pnpm-workspace.yaml
```

### 4.2 Package Manager & Build Tool

| Tool | Version | Purpose |
|:---|:---|:---|
| pnpm | 9.0.0 | Package manager (workspaces) |
| Turborepo | ^2.0.0 | Monorepo build orchestrator |
| Node.js | 18 | Runtime (Cloudflare Pages build environment) |

### 4.3 Root Scripts (from `package.json`)

```json
{
  "dev:parent": "turbo run dev --filter=parent",
  "build:parent": "turbo run build --filter=parent",
  "build:all": "turbo run build",
  "lint": "turbo run lint"
}
```

### 4.4 Internal Package References

The parent app imports from three shared packages:

```json
// apps/parent/package.json (dependencies)
{
  "@zeplow/ui": "workspace:*",
  "@zeplow/api": "workspace:*",
  "@zeplow/config": "workspace:*"
}
```

---

## 5. SHARED PACKAGES (API CLIENT, UI, CONFIG)

### 5.1 @zeplow/api — API Client

**Location:** `packages/api/src/`

Provides typed fetch functions that call `api.zeplow.com`. These run at **build time only** during static export — not at runtime in the browser (except the contact form POST).

**Key Functions Used by Parent Site:**

| Function | Returns | Used By |
|:---|:---|:---|
| `getSiteConfig('parent')` | `SiteConfig` | Root layout (nav, footer, CTA) |
| `getPage('parent', slug)` | `Page` | Every static page |
| `getPages('parent')` | `PageListItem[]` | Sitemap generation |
| `getProjects('parent', opts)` | `ProjectListItem[]` | Home page (featured projects) |
| `getTeamMembers('parent')` | `TeamMember[]` | About page |
| `getBlogPosts('parent')` | `BlogPostListItem[]` | Insights listing |
| `getBlogPost('parent', slug)` | `BlogPost` | Individual blog post |
| `getTestimonials('parent')` | `Testimonial[]` | If used on any page via content blocks |

**Complete TypeScript interfaces:** See Central PRD Section 5.4 or API PRD. All types are defined in `packages/api/src/types.ts` and include `SiteConfig`, `Page`, `ContentBlock`, `SEO`, `ProjectListItem`, `Project`, `BlogPostListItem`, `BlogPost`, `Testimonial`, `TeamMember`, `PaginatedResponse<T>`.

**API Client Implementation:**

```typescript
// packages/api/src/client.ts

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://api.zeplow.com';

async function fetchApi<T>(path: string): Promise<T> {
  const url = `${API_BASE}${path}`;
  const res = await fetch(url, {
    headers: { 'Accept': 'application/json' },
  });

  if (!res.ok) {
    throw new Error(`API Error: ${res.status} ${res.statusText} for ${url}`);
  }

  return res.json();
}

export function getSiteConfig(siteKey: string) {
  return fetchApi<SiteConfig>(`/sites/v1/${siteKey}/config`);
}

export function getPage(siteKey: string, slug: string) {
  return fetchApi<Page>(`/sites/v1/${siteKey}/pages/${slug}`);
}

// ... (all other functions — see Central PRD Section 5.4)
```

### 5.2 @zeplow/ui — Shared Components

**Location:** `packages/ui/src/`

Components used by the parent site:

| Component | Purpose | Server/Client |
|:---|:---|:---|
| `Navigation` | Top nav bar, data-driven from API config | Server |
| `Footer` | Site footer, data-driven from API config | Server |
| `Button` | CTA buttons (primary/secondary styles) | Server |
| `Container` | Max-width content wrapper | Server |
| `SectionHeading` | Consistent section headings | Server |
| `ContentRenderer` | Maps content blocks from API to React components | Server |
| `BlogCard` | Blog post listing card | Server |
| `TeamCard` | Team member card | Server |
| `ProjectCard` | Portfolio grid card (for featured projects on home) | Server |
| `TestimonialCard` | Testimonial display | Server |
| `StatsStrip` | Metrics/numbers display | Server |
| `ContactForm` | Contact form with honeypot spam protection | **Client** (`'use client'`) |
| `SEO` | Metadata generation component | Server |
| `OrganizationSchema` | JSON-LD Organization structured data | Server |
| `ArticleSchema` | JSON-LD Article structured data | Server |

**Only `ContactForm` is a client component.** Everything else is a React Server Component (default in App Router). This minimizes JavaScript sent to the browser.

### 5.3 @zeplow/config — Brand Configuration

**Location:** `packages/config/src/`

```typescript
// packages/config/src/colors.ts
export const colors = {
  parent: {
    primary: '#034c3c',       // Pine Teal
    background: '#f4f4f4',    // White Smoke
    text: '#140004',          // Coffee Bean
    accent: '#ff6f59',        // Vibrant Coral
  },
  // narrative and logic also defined here
} as const;

// packages/config/src/fonts.ts
export const fonts = {
  parent: {
    heading: 'Playfair Display',
    body: 'Manrope',
  },
  // narrative and logic also defined here
} as const;
```

---

## 6. NEXT.JS CONFIGURATION

### 6.1 next.config.js

```javascript
// apps/parent/next.config.js

/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',           // Static HTML export (no Node.js runtime needed)
  images: {
    unoptimized: true,        // Required for static export (no image optimization server)
  },
  transpilePackages: ['@zeplow/ui', '@zeplow/api', '@zeplow/config'],
}

module.exports = nextConfig
```

**Key implications of `output: 'export'`:**

- All pages are pre-rendered to static HTML at build time
- No API routes, no middleware, no server-side rendering at runtime
- Dynamic routes must use `generateStaticParams` to list all slugs at build time
- Images use standard `<img>` tags (not Next.js `<Image>` optimization)
- The only runtime JavaScript is client components (ContactForm, mobile nav toggle)

### 6.2 package.json

```json
{
  "name": "parent",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "next dev -p 3000",
    "build": "next build",
    "lint": "next lint"
  },
  "dependencies": {
    "next": "^14.0.0",
    "react": "^18.0.0",
    "react-dom": "^18.0.0",
    "@zeplow/ui": "workspace:*",
    "@zeplow/api": "workspace:*",
    "@zeplow/config": "workspace:*",
    "framer-motion": "^11.0.0"
  },
  "devDependencies": {
    "tailwindcss": "^3.0.0",
    "postcss": "^8.0.0",
    "autoprefixer": "^10.0.0",
    "typescript": "^5.0.0",
    "@types/react": "^18.0.0",
    "@types/node": "^20.0.0"
  }
}
```

**Dev server port:** 3000 (parent). Narrative uses 3001, Logic uses 3002.

---

## 7. ROOT LAYOUT

The root layout fetches the site config from the API and wraps all pages with Navigation and Footer.

```typescript
// apps/parent/app/layout.tsx

import { getSiteConfig } from '@zeplow/api';
import { Navigation, Footer } from '@zeplow/ui';
import localFont from 'next/font/local';
import type { Metadata } from 'next';
import './globals.css';

const SITE_KEY = 'parent';

const playfair = localFont({
  src: '../public/fonts/PlayfairDisplay-Bold.woff2',
  variable: '--font-playfair',
  display: 'swap',
});

const manrope = localFont({
  src: [
    { path: '../public/fonts/Manrope-Regular.woff2', weight: '400' },
    { path: '../public/fonts/Manrope-Medium.woff2', weight: '500' },
    { path: '../public/fonts/Manrope-SemiBold.woff2', weight: '600' },
    { path: '../public/fonts/Manrope-Bold.woff2', weight: '700' },
  ],
  variable: '--font-manrope',
  display: 'swap',
});

export const metadata: Metadata = {
  metadataBase: new URL('https://zeplow.com'),
};

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const config = await getSiteConfig(SITE_KEY);

  return (
    <html lang="en" className={`${playfair.variable} ${manrope.variable}`}>
      <body className="bg-background text-text font-body antialiased">
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

**Note:** `getSiteConfig` runs at build time. The config data (nav, footer, CTA, socials) is baked into every page's static HTML.

---

## 8. PAGE-BY-PAGE SPECIFICATIONS

### 8.1 Home Page (`/`)

**File:** `apps/parent/app/page.tsx`
**Template:** `home`
**Purpose:** First impression. Establish authority, introduce the group, show featured ventures and projects.

**Data Sources:**

```typescript
const page = await getPage('parent', 'home');
const featuredProjects = await getProjects('parent', { featured: true, limit: 3 });
```

**Expected Content Blocks (from CMS):**
1. `hero` — "Story. Systems. Ventures." with CTA "Get in Touch" → /contact
2. `text` — Welcome / belief statement (adapted from company profile: "At Zeplow, we believe that lasting impact comes from two forces working together...")
3. `cards` — The two ventures (Narrative + Logic) as cards linking to /ventures/narrative and /ventures/logic
4. `projects` — Featured projects block (count: 3, featured_only: true)
5. `cta` — "If this feels like your kind of thinking, we should talk." → /contact

**Additional data rendered outside content blocks:**
- Featured projects grid using `featuredProjects` array and `ProjectCard` component

**SEO metadata from page.seo:** title, description, og_image
**JSON-LD:** `OrganizationSchema` with Zeplow name, URL, description, social links

**Implementation:**

```typescript
// apps/parent/app/page.tsx

import { getPage, getProjects, getSiteConfig } from '@zeplow/api';
import { ContentRenderer, ProjectCard, OrganizationSchema } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'home');
  return {
    title: page.seo.title,
    description: page.seo.description,
    openGraph: {
      title: page.seo.title,
      description: page.seo.description,
      images: page.seo.og_image ? [page.seo.og_image] : [],
      url: 'https://zeplow.com',
      type: 'website',
    },
  };
}

export default async function HomePage() {
  const [page, featuredProjects, config] = await Promise.all([
    getPage(SITE_KEY, 'home'),
    getProjects(SITE_KEY, { featured: true, limit: 3 }),
    getSiteConfig(SITE_KEY),
  ]);

  return (
    <main>
      <OrganizationSchema
        name="Zeplow"
        url="https://zeplow.com"
        description="Story. Systems. Ventures."
        logo="https://zeplow.com/logo.png"
        email={config.contact_email}
        sameAs={Object.values(config.social_links)}
      />
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
      {/* Featured projects section — rendered alongside content blocks */}
      {featuredProjects.length > 0 && (
        <section>
          {featuredProjects.map((project) => (
            <ProjectCard key={project.id} project={project} siteKey={SITE_KEY} />
          ))}
        </section>
      )}
    </main>
  );
}
```

---

### 8.2 About Page (`/about`)

**File:** `apps/parent/app/about/page.tsx`
**Template:** `about`
**Purpose:** Company story, vision/mission, values, team.

**Data Sources:**

```typescript
const page = await getPage('parent', 'about');
const team = await getTeamMembers('parent');
```

**Expected Content Blocks (from CMS):**
1. `hero` — "About Zeplow"
2. `text` — Vision statement (from company profile: "To build an ecosystem where businesses don't just survive — they become household names...")
3. `text` — Mission statement ("To help businesses unlock their full potential through two disciplines: Narrative and Logic...")
4. `stats` — Key metrics (e.g., number of projects, countries served, ventures)
5. `team` — Team display block (use_all: true)

**Additional data rendered outside blocks:**
- Team members from `getTeamMembers('parent')` using `TeamCard` component
- Two founders: Shadman Sakib (CEO) and Shakib Bin Kabir (CTO)

**Implementation follows the same pattern as Home:**

```typescript
// apps/parent/app/about/page.tsx

import { getPage, getTeamMembers } from '@zeplow/api';
import { ContentRenderer, TeamCard } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

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
  const [page, team] = await Promise.all([
    getPage(SITE_KEY, 'about'),
    getTeamMembers(SITE_KEY),
  ]);

  return (
    <main>
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
      {team.length > 0 && (
        <section>
          {team.map((member) => (
            <TeamCard key={member.id} member={member} />
          ))}
        </section>
      )}
    </main>
  );
}
```

---

### 8.3 Ventures Overview (`/ventures`)

**File:** `apps/parent/app/ventures/page.tsx`
**Template:** `ventures`
**Purpose:** Overview of both venture arms with prominent links to detail pages.

**Data Source:**

```typescript
const page = await getPage('parent', 'ventures');
```

**Expected Content Blocks:**
1. `hero` — "Our Ventures"
2. `text` — Intro: "Zeplow operates through two specialized arms — each with its own expertise, but united by a single standard of quality."
3. `cards` — Two venture cards:
   - **Zeplow Narrative**: "Brand Storytelling, Identity & Content Systems" → /ventures/narrative
   - **Zeplow Logic**: "Technology, Automation & AI Systems" → /ventures/logic
4. `cta` — "Ready to work with us?" → /contact

**Parent-Specific Component:** `VentureCard.tsx` in `apps/parent/components/`. A larger, more prominent card than the generic `cards` block — shows venture name, tagline, description, and links to both the venture detail page and the external venture site.

---

### 8.4 Venture Detail: Narrative (`/ventures/narrative`)

**File:** `apps/parent/app/ventures/narrative/page.tsx`
**Template:** `ventures`
**Purpose:** What Zeplow Narrative does, told from the parent's authoritative perspective. Not a copy of narrative.zeplow.com — it's the parent saying "here's what our creative arm does."

**Data Source:**

```typescript
const page = await getPage('parent', 'ventures-narrative');
```

**Expected Content Blocks:**
1. `hero` — "Zeplow Narrative" / "Stories that sell."
2. `text` — Description of what Narrative does (from company profile: "We help brands stop being invisible. Through strategy, visual identity, content production, and ongoing management — we turn businesses into stories worth following.")
3. `cards` — Service categories: Brand Strategy & Positioning, Visual Identity Systems, Video & Photo Production, Content Direction & Calendars, Social Media Management, Campaign Creative
4. `cta` — "Visit Zeplow Narrative" → external link to narrative.zeplow.com + "Get in Touch" → /contact

---

### 8.5 Venture Detail: Logic (`/ventures/logic`)

**File:** `apps/parent/app/ventures/logic/page.tsx`
**Template:** `ventures`

**Data Source:**

```typescript
const page = await getPage('parent', 'ventures-logic');
```

**Expected Content Blocks:**
1. `hero` — "Zeplow Logic" / "Build once. Run forever."
2. `text` — Description (from company profile: "We replace spreadsheets, manual processes, and operational chaos with systems that run boringly well.")
3. `cards` — Service categories: Workflow Audits & Process Design, Custom Dashboards, ERP/CRM Systems, API Integrations, AI-Native Automation, MVP Development, Fractional CTO Services
4. `cta` — "Visit Zeplow Logic" → external link to logic.zeplow.com + "Get in Touch" → /contact

---

### 8.6 Insights — Blog Listing (`/insights`)

**File:** `apps/parent/app/insights/page.tsx`
**Template:** `insights`

**Data Source:**

```typescript
const posts = await getBlogPosts('parent');
```

**Renders:** A grid of `BlogCard` components. Each card shows: title, excerpt, cover image, tags, author, published date. Links to `/insights/{slug}`.

**Implementation:**

```typescript
// apps/parent/app/insights/page.tsx

import { getBlogPosts } from '@zeplow/api';
import { BlogCard } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export const metadata: Metadata = {
  title: 'Insights — Zeplow',
  description: 'Thoughts on building ventures, brand storytelling, and systems that scale.',
};

export default async function InsightsPage() {
  const posts = await getBlogPosts(SITE_KEY);

  return (
    <main>
      <section>
        <h1>Insights</h1>
        <div>
          {posts.map((post) => (
            <BlogCard key={post.id} post={post} basePath="/insights" />
          ))}
        </div>
      </section>
    </main>
  );
}
```

---

### 8.7 Insights — Blog Post Detail (`/insights/[slug]`)

**File:** `apps/parent/app/insights/[slug]/page.tsx`
**Dynamic route** — uses `generateStaticParams` to enumerate all blog slugs at build time.

**Data Sources:**

```typescript
// At build time — list all slugs
const posts = await getBlogPosts('parent');
// Per page — fetch full post
const post = await getBlogPost('parent', slug);
```

**Renders:** Full blog post with: title, cover image, author, published date, body HTML (via `dangerouslySetInnerHTML`), tags. Plus `ArticleSchema` JSON-LD.

**Implementation:**

```typescript
// apps/parent/app/insights/[slug]/page.tsx

import { getBlogPosts, getBlogPost } from '@zeplow/api';
import { ArticleSchema } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export async function generateStaticParams() {
  const posts = await getBlogPosts(SITE_KEY);
  return posts.map((post) => ({ slug: post.slug }));
}

export async function generateMetadata({ params }: { params: { slug: string } }): Promise<Metadata> {
  const post = await getBlogPost(SITE_KEY, params.slug);
  return {
    title: post.seo.title,
    description: post.seo.description,
    openGraph: {
      title: post.seo.title,
      description: post.seo.description,
      images: post.cover_image ? [post.cover_image] : [],
      type: 'article',
    },
  };
}

export default async function BlogPostPage({ params }: { params: { slug: string } }) {
  const post = await getBlogPost(SITE_KEY, params.slug);

  return (
    <main>
      <ArticleSchema
        title={post.title}
        description={post.seo.description}
        url={`https://zeplow.com/insights/${post.slug}`}
        image={post.cover_image}
        author={post.author || 'Zeplow'}
        publishedAt={post.published_at}
        siteName="Zeplow"
      />
      <article>
        <h1>{post.title}</h1>
        {post.cover_image && <img src={post.cover_image} alt={post.title} loading="lazy" />}
        <div dangerouslySetInnerHTML={{ __html: post.body }} />
      </article>
    </main>
  );
}
```

**Security note:** Blog body HTML is rendered via `dangerouslySetInnerHTML`. The HTML is authored by trusted admins in the CMS RichEditor and should be sanitized at the CMS level before sync.

---

### 8.8 Careers (`/careers`)

**File:** `apps/parent/app/careers/page.tsx`
**Template:** `careers`
**Purpose:** Placeholder page. No job listings system — just a CMS-managed page.

**Data Source:**

```typescript
const page = await getPage('parent', 'careers');
```

**Expected Content Blocks:**
1. `hero` — "Careers at Zeplow"
2. `text` — "We're a small, focused team building something ambitious. If you're interested in joining us, reach out directly."
3. `cta` — "Send us a message" → /contact

---

### 8.9 Contact (`/contact`)

**File:** `apps/parent/app/contact/page.tsx`
**Template:** `contact`
**Purpose:** Contact form + company information.

**Data Source:**

```typescript
const page = await getPage('parent', 'contact');
```

**Renders:** Content blocks from CMS (hero, text) PLUS the `ContactForm` client component.

**Implementation:**

```typescript
// apps/parent/app/contact/page.tsx

import { getPage } from '@zeplow/api';
import { ContentRenderer, ContactForm } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'contact');
  return {
    title: page.seo.title,
    description: page.seo.description,
  };
}

export default async function ContactPage() {
  const page = await getPage(SITE_KEY, 'contact');

  return (
    <main>
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
      <ContactForm siteKey={SITE_KEY} siteDomain="zeplow.com" />
    </main>
  );
}
```

The `ContactForm` component is a **client component** (`'use client'`). It is the only runtime JavaScript interaction on the site. It POSTs to `api.zeplow.com/sites/v1/parent/contact` with honeypot spam protection. See Central PRD Section 5.9 for the full implementation.

---

## 9. CONTENT BLOCK RENDERING

### 9.1 How It Works

The `ContentRenderer` component receives the `content` array from the API page response and maps each block's `type` to a React component:

```
'hero'         → <HeroBlock />
'text'         → <TextBlock />
'cards'        → <CardsBlock />
'cta'          → <CTABlock />
'image'        → <ImageBlock />
'gallery'      → <GalleryBlock />
'testimonials' → <TestimonialsBlock />
'team'         → <TeamBlock />
'projects'     → <ProjectsBlock />
'stats'        → <StatsBlock />
'divider'      → <DividerBlock />
'raw_html'     → <RawHTMLBlock />
```

Unknown block types are skipped with a `console.warn` in development.

### 9.2 Block Components Used by Parent Site

The parent site primarily uses these blocks:

| Block | Usage on Parent Site |
|:---|:---|
| `hero` | Every page — page-level hero banners |
| `text` | Welcome copy, vision/mission, descriptions |
| `cards` | Venture cards (Narrative + Logic), service categories |
| `cta` | Bottom-of-page call-to-action on every page |
| `stats` | About page — key metrics |
| `team` | About page — founder profiles |
| `projects` | Home page — featured projects grid |
| `image` | Occasional inline images |
| `divider` | Visual separators between sections |

### 9.3 Visual Implementation

Block component visual design is handled in **Phase 6** (frontend design phase). During the data layer phase (Phase 4), block components are implemented as structural stubs that render the correct data in basic HTML. Visual styling, animations (Framer Motion), and responsive design are layered on afterward.

### 9.4 Responsive Image Strategy

Since the Next.js static export uses `unoptimized: true` (no built-in image optimization server), the site must explicitly use the correct Spatie MediaLibrary conversion for each rendering context. The API returns base image URLs; the frontend is responsible for selecting the appropriate conversion size.

**Conversion Selection by Context:**

| Context | Conversion | Resolution | Example Usage |
|:---|:---|:---|:---|
| Hero images and full-width backgrounds | `large` | 1920×1080 | `HeroBlock`, page-level hero banners |
| Project cards on homepage and grid views | `medium` | 800×600 | `ProjectCard`, venture cards, portfolio grids |
| Thumbnail previews | `thumbnail` | 300×300 | `BlogCard` cover images, small team photos |

**API Client Image Helper:**

The shared `@zeplow/api` package should expose a helper that constructs the correct conversion URL:

```typescript
// packages/api/src/images.ts

/**
 * Returns the URL for a specific Spatie MediaLibrary conversion.
 * Replaces the filename in the base URL with conversions/{original-filename}-{conversion}.{ext}
 *
 * Example:
 *   getImageUrl('https://cms.zeplow.com/storage/media/1/hero.jpg', 'medium')
 *   → 'https://cms.zeplow.com/storage/media/1/conversions/hero-medium.jpg'
 */
export function getImageUrl(baseUrl: string, conversion: 'large' | 'medium' | 'thumbnail'): string {
  const url = new URL(baseUrl);
  const pathParts = url.pathname.split('/');
  const filename = pathParts.pop()!;
  const [name, ext] = [filename.substring(0, filename.lastIndexOf('.')), filename.substring(filename.lastIndexOf('.'))];
  pathParts.push('conversions', `${name}-${conversion}${ext}`);
  url.pathname = pathParts.join('/');
  return url.toString();
}
```

**Image Tag Requirements:**

All `<img>` tags must include the following attributes:

| Attribute | Requirement |
|:---|:---|
| `width` | Explicit width matching the conversion dimensions |
| `height` | Explicit height matching the conversion dimensions |
| `alt` | Descriptive alt text (from API data) |
| `loading` | `"lazy"` for below-the-fold images; `"eager"` or omitted for above-the-fold hero images |

Above-the-fold hero images should use `loading="eager"` (or omit the attribute) and may include a `fetchpriority="high"` attribute to prioritize loading. All other images must use `loading="lazy"`.

---

## 10. CONTACT FORM

### 10.1 Behavior

1. Visitor fills out form fields (name, email, company, message, budget_range)
2. Hidden honeypot field (`website_url`) catches bots
3. On submit, JavaScript POSTs to `api.zeplow.com/sites/v1/parent/contact`
4. Success → "Thank you. We'll be in touch within 24 hours."
5. Error → friendly error message with fallback email
6. Honeypot filled → fake success (bot doesn't know it was caught)

### 10.2 Form Fields

| Field | Type | Required | Placeholder |
|:---|:---|:---|:---|
| name | text | Yes | "Your Name" |
| email | email | Yes | "Email" |
| company | text | No | "Company (optional)" |
| message | textarea | Yes | "Tell us about your project..." |
| budget_range | select | No | "Budget Range (optional)" with options: Under $3,000 / $3,000–$5,000 / $5,000–$10,000 / $10,000+ |
| website_url | text (hidden) | No | Honeypot — invisible to humans |

### 10.3 Client Component

This is the only `'use client'` component on the parent site. See full implementation in Central PRD Section 5.9 (`packages/ui/src/ContactForm.tsx`).

---

## 11. SEO STRATEGY

### 11.1 Meta Tags Per Page

Every page must have (generated from API `page.seo` data):

| Tag | Source |
|:---|:---|
| `<title>` | `seo.title` (fallback: `{page.title} — Zeplow`) |
| `<meta name="description">` | `seo.description` |
| `<meta property="og:title">` | Same as title |
| `<meta property="og:description">` | Same as description |
| `<meta property="og:image">` | `seo.og_image` (fallback: site default) |
| `<meta property="og:url">` | Canonical URL |
| `<meta property="og:type">` | `website` for pages, `article` for blog posts |
| `<link rel="canonical">` | Full URL |

### 11.2 Sitemap

Generated at build time via `apps/parent/app/sitemap.ts`:

```typescript
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

  // Static venture pages (not in CMS pages list since they use different slugs)
  const ventureEntries = [
    { url: `${BASE_URL}/ventures`, changeFrequency: 'monthly' as const, priority: 0.8 },
    { url: `${BASE_URL}/ventures/narrative`, changeFrequency: 'monthly' as const, priority: 0.7 },
    { url: `${BASE_URL}/ventures/logic`, changeFrequency: 'monthly' as const, priority: 0.7 },
  ];

  return [...pageEntries, ...ventureEntries, ...blogEntries];
}
```

### 11.3 robots.txt

```
User-agent: *
Allow: /
Sitemap: https://zeplow.com/sitemap.xml
```

**File:** `apps/parent/public/robots.txt`

### 11.4 Structured Data (JSON-LD)

| Page | Schema Type | Component |
|:---|:---|:---|
| Home | Organization | `OrganizationSchema` — name, url, description, logo, email, sameAs (socials) |
| Blog posts | Article | `ArticleSchema` — headline, description, url, image, author, datePublished, publisher |

---

## 12. PERFORMANCE REQUIREMENTS

### 12.1 Target Metrics

| Metric | Target |
|:---|:---|
| Lighthouse Performance Score | ≥ 95 |
| Time to First Byte (TTFB) | < 50ms |
| First Contentful Paint (FCP) | < 0.8s |
| Largest Contentful Paint (LCP) | < 1.2s |
| Total Blocking Time (TBT) | < 50ms |
| Cumulative Layout Shift (CLS) | < 0.05 |
| Total page weight (HTML+CSS+JS, excl. images) | < 200 KB |
| Total page weight (with images) | < 1 MB |

### 12.2 Implementation Rules

| Rule | How |
|:---|:---|
| Self-host all fonts | `next/font/local` — no Google Fonts CDN calls |
| Preload critical fonts | Heading + body fonts preloaded in layout |
| Lazy-load images | `<img loading="lazy">` for below-fold images |
| Minimal JavaScript | Server Components by default. Only `ContactForm` and mobile nav toggle use `'use client'` |
| Purge CSS | Tailwind purges unused CSS at build. Expected: 5–15 KB |
| No third-party scripts | No analytics, no chat widgets, no tracking pixels at launch |
| Prefetch links | Next.js `<Link>` auto-prefetches on hover |
| Compression | Cloudflare Pages auto-applies gzip/brotli |

### 12.3 Image Strategy

Images are stored on the CMS (`cms.zeplow.com/storage/`) and served through Cloudflare's CDN (orange cloud proxy). The frontend references absolute image URLs from the API response. All images load from Cloudflare edge nodes.

- Hero images: loaded eagerly (above fold)
- Project thumbnails, blog covers, team photos: `loading="lazy"`
- Alt text is always provided from API data

---

## 13. CLOUDFLARE PAGES — BUILD & DEPLOY

### 13.1 Project Configuration

| Setting | Value |
|:---|:---|
| Project name | `zeplow-parent` |
| Production branch | `main` |
| Build command | `npx pnpm install --frozen-lockfile && npx pnpm run build:parent` |
| Build output directory | `apps/parent/out` |
| Root directory | `/` |
| Node.js version | `18` |
| Custom domain | `zeplow.com` |
| Custom domain | `www.zeplow.com` (redirect to apex) |

### 13.2 Environment Variables (Cloudflare Pages)

| Variable | Value |
|:---|:---|
| `NEXT_PUBLIC_API_URL` | `https://api.zeplow.com` |
| `NEXT_PUBLIC_SITE_KEY` | `parent` |
| `NODE_VERSION` | `18` |

### 13.3 Build Process

```
1. Cloudflare receives deploy hook (from API after content sync)
       OR
   Developer pushes to `main` branch on GitHub

2. Cloudflare clones the zeplow-sites monorepo from GitHub

3. Installs dependencies: npm ci / pnpm install

4. Runs: npx pnpm install --frozen-lockfile && npx pnpm run build:parent

5. During next build:
   a. Root layout calls getSiteConfig('parent') → API returns nav/footer/CTA config
   b. Each page calls getPage('parent', slug) → API returns content blocks + SEO
   c. Home calls getProjects('parent', {featured:true, limit:3}) → API returns projects
   d. About calls getTeamMembers('parent') → API returns team
   e. Insights listing calls getBlogPosts('parent') → API returns all posts
   f. generateStaticParams enumerates blog slugs → each calls getBlogPost('parent', slug)

6. Next.js renders all pages to static HTML in apps/parent/out/

7. Cloudflare deploys the /out directory to its global CDN (330+ edge nodes)

8. Site is live. Total build time: ~60-120 seconds.
```

### 13.4 Deploy Triggers

| Trigger | How It Happens |
|:---|:---|
| Content change in CMS | Editor publishes → CMS syncs to API → API fires deploy hook → Cloudflare rebuilds |
| Code change | Developer pushes to `main` → Cloudflare auto-builds (connected to GitHub) |
| Manual | Cloudflare Pages dashboard → trigger deploy |

### 13.5 Rollback

If a build fails, Cloudflare keeps serving the previous successful build. No downtime. Failed builds are visible in the Cloudflare Pages dashboard with build logs.

---

## 14. DNS & DOMAIN CONFIGURATION

### 14.1 DNS Records

| Type | Name | Content | Proxy |
|:---|:---|:---|:---|
| A | `zeplow.com` | Cloudflare Pages (auto-configured when custom domain is added) | Yes |
| CNAME | `www` | `zeplow.com` | Yes (redirect to apex) |

### 14.2 SSL

Automatic via Cloudflare — Universal SSL, free. HTTPS enforced on all requests.

### 14.3 www Redirect

`www.zeplow.com` redirects to `zeplow.com` (apex domain). Configured via Cloudflare Pages custom domain settings or a Cloudflare redirect rule.

---

## 15. SECURITY HEADERS

**File:** `apps/parent/public/_headers`

```
/*
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  Permissions-Policy: camera=(), microphone=(), geolocation=()
```

These are applied by Cloudflare Pages to all responses. They prevent clickjacking, MIME type sniffing, and restrict browser permissions.

---

## 16. CONTENT SEEDING — WHAT TO PUBLISH IN CMS

This section defines the exact content that must be created in the CMS (cms.zeplow.com) for the parent site to render correctly. All content below is created as CMS records for site_key `parent`.

### 16.1 Pages to Create

| slug | title | template | Content Source |
|:---|:---|:---|:---|
| `home` | Home | `home` | Hero + welcome text + venture cards + featured projects CTA |
| `about` | About | `about` | Vision/mission from company profile + values (Stewardship, Transparency, Systems Over Heroes) |
| `ventures` | Our Ventures | `ventures` | Intro text + Narrative card + Logic card |
| `ventures-narrative` | Zeplow Narrative | `ventures` | Narrative arm description + service list from company profile |
| `ventures-logic` | Zeplow Logic | `ventures` | Logic arm description + service list from company profile |
| `careers` | Careers | `careers` | Placeholder text |
| `contact` | Contact | `contact` | Hero + intro text (form is rendered by code, not content blocks) |

### 16.2 Team Members to Create

| name | role | bio | is_founder | sort_order |
|:---|:---|:---|:---|:---|
| Shadman Sakib | Co-Founder & CEO | Strategy, direction, and brand & venture leadership. Leads strategy, client relationships, and brand direction across the Zeplow group. | true | 0 |
| Shakib Bin Kabir | Co-Founder & CTO | Systems, automation, AI & technical architecture. Leads technology, product development, and infrastructure decisions across the Zeplow group. | true | 1 |

### 16.3 Site Config to Create

| Field | Value |
|:---|:---|
| nav_items | About (/about), Ventures (/ventures), Insights (/insights), Careers (/careers), Contact (/contact) |
| footer_text | © 2026 Zeplow LTD. All rights reserved. |
| cta_text | Get in Touch |
| cta_url | /contact |
| social_links | linkedin, instagram, whatsapp URLs |
| contact_email | hello@zeplow.com |
| footer_links | Group: "Ventures" → Zeplow Narrative (https://narrative.zeplow.com), Zeplow Logic (https://logic.zeplow.com); Group: "Company" → About (/about), Insights (/insights), Careers (/careers), Contact (/contact) |

### 16.4 Content Tone for Parent Site

The parent site speaks as **the holding company**, not as a service provider. The tone is:

- **Foundational, not salesy** — "We believe..." not "We offer..."
- **Quiet confidence** — no exclamation marks, no hype
- **Third-person about the ventures** — "Through Zeplow Narrative, we help brands..." not "We are a creative agency..."
- **Empire builder** — the long view, the ecosystem, the vision

Key phrases from the brand documents to use:

- "The company behind companies."
- "Story. Systems. Ventures."
- "Lasting impact comes from two forces working together."
- "If this feels like your kind of thinking, we should talk."
- "We're not an agency. We're not a dev shop. We're the partner behind the scenes."

### 16.5 Selected Projects for Parent Site

The parent site shows a curated selection of projects across both ventures. These are the 6 strongest from the company profile, to be created as Project records with `site_key: parent`:

| title | one_liner | industry | featured |
|:---|:---|:---|:---|
| Tututor.ai | AI-powered tutoring platform that personalizes learning and accelerates student success | EdTech | true |
| CAPEC AI | Platform connecting emerging market businesses with global non-dilutive funding | FinTech | true |
| Aditio ERP | Custom all-in-one ERP — project management, invoicing, team allocation in a single dashboard | SaaS | true |
| RAT'S Vault | Secure digital platform for managing and protecting critical business data | Enterprise | false |
| ATME Cards | Modern digital business card platform — share identity, links, and presence instantly | SaaS | false |
| CentrePoint Shop | Full e-commerce store for a Canadian postal service | E-Commerce | false |

Only the top 3 (featured: true) appear on the home page. All 6 are available via the API for any page that uses a `projects` content block.

---

## 17. ENVIRONMENT VARIABLES

### 17.1 Local Development (.env.local)

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_SITE_KEY=parent
```

(Assumes the API app runs locally on port 8000 during development.)

### 17.2 Production (Cloudflare Pages)

| Variable | Value |
|:---|:---|
| `NEXT_PUBLIC_API_URL` | `https://api.zeplow.com` |
| `NEXT_PUBLIC_SITE_KEY` | `parent` |
| `NODE_VERSION` | `18` |
| `CF_BUILD_TOKEN` | Build agent token for API rate limit exemption. Must match the unhashed value stored in the API. Set in Cloudflare Pages environment variables (not in `.env.local` for local dev — local dev uses the default 60/min public limit, which is sufficient for single-site dev). |
| `NEXT_PUBLIC_CF_TURNSTILE_SITE_KEY` | Cloudflare Turnstile site key (client-side, safe to expose). |

---

## 18. DIRECTORY STRUCTURE

```
apps/parent/
├── app/
│   ├── globals.css                    # Tailwind imports + global styles
│   ├── layout.tsx                     # Root layout (fonts, nav, footer from API config)
│   ├── not-found.tsx                  # Custom 404 page (branded "Page not found")
│   ├── page.tsx                       # Home page (/)
│   ├── about/
│   │   └── page.tsx                   # About page (/about)
│   ├── ventures/
│   │   ├── page.tsx                   # Ventures overview (/ventures)
│   │   ├── narrative/
│   │   │   └── page.tsx               # Narrative venture detail (/ventures/narrative)
│   │   └── logic/
│   │       └── page.tsx               # Logic venture detail (/ventures/logic)
│   ├── insights/
│   │   ├── page.tsx                   # Blog listing (/insights)
│   │   └── [slug]/
│   │       └── page.tsx               # Individual blog post (/insights/[slug])
│   ├── careers/
│   │   └── page.tsx                   # Careers placeholder (/careers)
│   ├── contact/
│   │   └── page.tsx                   # Contact form (/contact)
│   ├── sitemap.ts                     # Dynamic sitemap generation
│   └── robots.ts                      # Or public/robots.txt
├── components/                        # Parent-site-specific components
│   ├── VentureCard.tsx                # Large venture card (Narrative/Logic)
│   └── BeliefBlock.tsx                # Stylized belief/value statement block
├── public/
│   ├── fonts/
│   │   ├── PlayfairDisplay-Bold.woff2
│   │   ├── Manrope-Regular.woff2
│   │   ├── Manrope-Medium.woff2
│   │   ├── Manrope-SemiBold.woff2
│   │   └── Manrope-Bold.woff2
│   ├── robots.txt
│   ├── 404.html                       # Static 404 fallback for Cloudflare Pages
│   ├── _headers                       # Security headers for Cloudflare
│   ├── favicon.ico
│   ├── logo.png                       # Zeplow logo (for JSON-LD schema)
│   └── og-default.jpg                 # Default Open Graph image
├── next.config.js
├── tailwind.config.ts
├── postcss.config.js
├── tsconfig.json
└── package.json
```

---

## 19. ERROR HANDLING

### 19.1 Build-Time Errors

| Scenario | Behavior |
|:---|:---|
| API unreachable during build | Build fails. Cloudflare keeps serving the previous successful build. No downtime. |
| API returns 404 for a page | `getPage` throws, build fails. Fix the CMS content and re-trigger build. |
| API returns 500 | Build fails. Previous version stays live. |
| Blog slug doesn't exist | `generateStaticParams` won't include it. No crash. |

### 19.2 Custom Error Pages

| Page | File | Behavior |
|:---|:---|:---|
| 404 (Not Found) | `apps/parent/app/not-found.tsx` | Branded "Page not found" message with navigation back to the home page. Uses the site config for consistent navigation and footer (fetched at build time via `getSiteConfig('parent')`). |
| 404 static fallback | `apps/parent/public/404.html` | A static HTML fallback that Cloudflare Pages will serve for unknown routes not handled by the App Router. Should match the branded 404 styling. |

**Implementation:**

```typescript
// apps/parent/app/not-found.tsx

import { getSiteConfig } from '@zeplow/api';
import { Container, Button } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export const metadata: Metadata = {
  title: 'Page Not Found — Zeplow',
};

export default async function NotFound() {
  return (
    <main>
      <Container>
        <h1>Page not found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <Button href="/">Back to Home</Button>
      </Container>
    </main>
  );
}
```

For the static export, also generate a `404.html` in `apps/parent/public/` that mirrors this design. Cloudflare Pages automatically serves `404.html` for any route that doesn't match a static file.

### 19.3 Runtime Errors (Browser)

| Scenario | Behavior |
|:---|:---|
| Contact form API unreachable | Shows "Something went wrong. Please try again or email us directly at hello@zeplow.com" |
| Contact form validation error | Shows field-level error messages from API 422 response |
| Image URL broken | `<img>` shows alt text. No crash. |
| JavaScript disabled | All content is pre-rendered static HTML. Site is fully functional without JS (except contact form submission). |

---

## 20. IMPLEMENTATION ORDER

### Mock Data Strategy

During frontend development (Phases 1–6), the `@zeplow/api` client uses **mock data fallbacks**. When the API at `api.zeplow.com` (or `localhost:8000`) is unreachable, every fetch function returns hardcoded mock data defined in `packages/api/src/mock-data.ts`. This allows the frontend to be developed, styled, and tested independently of the CMS and API.

The mock data includes:
- Parent site config (nav items, footer links, CTA, social links, contact email)
- All 7 parent pages with content blocks matching the CMS content spec (Section 16)
- 3 featured projects (Tututor.ai, CAPEC AI, Aditio ERP)
- 2 team members (Shadman Sakib, Shakib Bin Kabir)
- Empty blog posts array (blog content added via CMS later)

**When the API is available**, the client fetches live data and the mocks are never used. No code changes are needed to switch — the try/catch fallback handles it automatically.

### Phase 1: Full App Scaffolding (Day 1) ✅

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 1.1 | Create monorepo infrastructure | Root `package.json`, `turbo.json`, `pnpm-workspace.yaml` | — |
| 1.2 | Create `@zeplow/config` package | Brand colors and font mappings for all 3 sites | 1.1 |
| 1.3 | Create `@zeplow/api` package | TypeScript types, API client with mock fallbacks, image helper | 1.1 |
| 1.4 | Create `@zeplow/ui` package | 14 shared components (Navigation, Footer, ContentRenderer, ContactForm, etc.) | 1.1 |
| 1.5 | Create `apps/parent` | Next.js 14 app with App Router, TypeScript, Tailwind | 1.1 |
| 1.6 | Configure `next.config.js` | `output: 'export'`, `unoptimized` images, `transpilePackages` | 1.5 |
| 1.7 | Configure `tailwind.config.ts` | Parent brand colors, font families, content paths | 1.5 |
| 1.8 | Download and self-host fonts | Playfair Display Bold + Manrope 400/500/600/700 into `public/fonts/` | 1.5 |
| 1.9 | Create `globals.css` | Tailwind directives, base styles, CSS variables, prose styles | 1.7 |
| 1.10 | Create root layout (`layout.tsx`) | `next/font/local`, `getSiteConfig('parent')`, Navigation + Footer | 1.4, 1.8 |
| 1.11 | Create all 9 page routes | Home, About, Ventures (×3), Insights (×2), Careers, Contact | 1.10 |
| 1.12 | Create `sitemap.ts` | Dynamic sitemap from API/mock data | 1.11 |
| 1.13 | Create `not-found.tsx` | Branded 404 page | 1.10 |
| 1.14 | Create public assets | `_headers`, `robots.txt`, `404.html` (static fallback) | 1.5 |
| 1.15 | Create `VentureCard` component | Parent-specific large venture card | 1.5 |
| 1.16 | Install dependencies | `pnpm install` — all workspace packages resolved | 1.1–1.15 |
| 1.17 | Verify `pnpm dev:parent` runs | Dev server on localhost:3000, all routes return 200 with mock data | 1.16 |

### Phase 2: Design & Polish (Days 2–4)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 2.1 | Design and implement all block components | Hero, Text, Cards, CTA, Stats, Team, etc. — apply Tailwind styling | 1.17 |
| 2.2 | Design and implement VentureCard component | Large venture card for /ventures page | 1.17 |
| 2.3 | Design and implement Navigation | Desktop + mobile responsive | 1.17 |
| 2.4 | Design and implement Footer | Links, socials, copyright | 1.17 |
| 2.5 | Add Framer Motion animations | Page transitions, scroll reveals | 2.1–2.4 |
| 2.6 | Responsive design pass | Mobile, tablet, desktop breakpoints | 2.1–2.4 |

### Phase 3: Build Verification (Day 5)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 3.1 | Run `pnpm build:parent` locally | Verify static export succeeds → files in `apps/parent/out/` | 2.1–2.6 |
| 3.2 | Inspect generated HTML | Check meta tags, JSON-LD, content rendering | 3.1 |
| 3.3 | Lighthouse audit | Target 95+ on Performance, Accessibility, SEO | 3.1 |
| 3.4 | Cross-browser testing | Chrome, Firefox, Safari, mobile | 3.1 |

### Phase 4: Cloudflare Deployment (Day 5–6)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 4.1 | Create Cloudflare Pages project `zeplow-parent` | Connect to GitHub repo | Monorepo on GitHub |
| 4.2 | Configure build settings | Build command, output dir, env vars | 4.1 |
| 4.3 | Add custom domain `zeplow.com` | Plus `www.zeplow.com` redirect | DNS configured |
| 4.4 | Push to `main` and verify first deploy | Site loads on zeplow.com (with mock data initially) | 4.1–4.3 |

### Phase 5: Content Seeding (Days 6–7)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 5.1 | Seed all 7 parent pages in CMS | home, about, ventures, ventures-narrative, ventures-logic, careers, contact | CMS deployed |
| 5.2 | Seed 2 team members | Shadman + Shakib | CMS deployed |
| 5.3 | Seed 6 projects (3 featured) | From company profile | CMS deployed |
| 5.4 | Seed parent site config | Nav, footer, CTA, socials | CMS deployed |
| 5.5 | Trigger resync from CMS | Resync All → parent | 5.1–5.4 |
| 5.6 | Verify site shows content | Visit zeplow.com, check all pages | 5.5 |

### Phase 6: Final QA (Day 8)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 6.1 | Test all internal links | No 404s on any page | 5.6 |
| 6.2 | Test cross-site links | /ventures/narrative → narrative.zeplow.com, etc. | 5.6 |
| 6.3 | Test contact form end-to-end | Submit, validation, honeypot, API errors | 5.6 |
| 6.4 | Validate JSON-LD schemas | Google Rich Results Test — no errors | 5.6 |
| 6.5 | Final Lighthouse audit | 95+ on all pages with real content | 6.1–6.4 |

### Phase 7: API Wiring & Deploy Hook Integration (Day 9)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 7.1 | Verify API endpoints serve correct data | `curl api.zeplow.com/sites/v1/parent/config`, etc. | API deployed |
| 7.2 | Update `NEXT_PUBLIC_API_URL` in Cloudflare Pages env | Point to `https://api.zeplow.com` | 7.1 |
| 7.3 | Get deploy hook URL from Cloudflare Pages | From dashboard → Settings → Builds → Deploy hooks | 4.1 |
| 7.4 | Add deploy hook URL to API `.env` | `CF_DEPLOY_HOOK_PARENT=<url>` | 7.3, API deployed |
| 7.5 | Test full publish cycle | CMS publish → API sync → deploy hook → Cloudflare rebuild → live | 7.2, 7.4 |
| 7.6 | Verify mock fallbacks are not triggered | Check that all data comes from live API, not mocks | 7.5 |

---

## 21. TESTING CHECKLIST

### 21.1 Build Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| `pnpm build:parent` succeeds | Static files in `apps/parent/out/` | ☐ |
| All 9 routes generate HTML files | Check `out/` directory | ☐ |
| Blog dynamic routes generate for all published posts | One HTML file per blog slug | ☐ |

### 21.2 Page Load Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| `zeplow.com` loads | Shows parent home page with hero | ☐ |
| `/about` loads | Shows vision, mission, values, team | ☐ |
| `/ventures` loads | Shows both venture cards | ☐ |
| `/ventures/narrative` loads | Shows Narrative arm details | ☐ |
| `/ventures/logic` loads | Shows Logic arm details | ☐ |
| `/insights` loads | Shows blog post grid | ☐ |
| `/insights/{slug}` loads | Shows full blog post with body HTML | ☐ |
| `/careers` loads | Shows placeholder content | ☐ |
| `/contact` loads | Shows content + contact form | ☐ |
| Visit `www.zeplow.com` | 301 redirect to `zeplow.com` (apex domain) | ☐ |

### 21.3 SEO Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| View page source: meta tags present | title, description, og tags, canonical | ☐ |
| Home page has OrganizationSchema JSON-LD | Valid JSON-LD in `<script type="application/ld+json">` | ☐ |
| Blog post has ArticleSchema JSON-LD | Valid JSON-LD with headline, author, date | ☐ |
| `/sitemap.xml` accessible | Valid XML with all page + blog URLs | ☐ |
| `/robots.txt` accessible | Correct content with sitemap URL | ☐ |
| Validate JSON-LD using Google's Rich Results Test tool or Schema.org validator | No errors or warnings for OrganizationSchema (home) and ArticleSchema (blog posts) | ☐ |

### 21.4 Contact Form Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Submit with valid data | Success message, API receives submission | ☐ |
| Submit with missing required fields | Validation error shown | ☐ |
| Submit with honeypot filled | Fake success, nothing stored in API | ☐ |
| API unreachable | Error message with fallback email | ☐ |
| Turnstile widget renders on contact page | Cloudflare challenge visible below form fields | ☐ |
| Complete Turnstile challenge, submit form | Form submits successfully | ☐ |
| Turnstile site key missing (local dev without key) | Form renders without Turnstile widget, still submits | ☐ |

### 21.5 Performance Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Lighthouse score ≥ 95 on home page | Performance, Accessibility, SEO | ☐ |
| No Google Fonts CDN calls | Fonts loaded from /fonts/ via next/font/local | ☐ |
| Total JS < 100 KB | Minimal client-side JS (only ContactForm) | ☐ |
| Images served from Cloudflare CDN | Check `cf-cache-status` header on cms.zeplow.com images | ☐ |
| HTTPS enforced | HTTP redirects to HTTPS | ☐ |
| Block font files in DevTools Network tab | Fallback fonts (Georgia for headings, system-ui for body) render correctly and layout doesn't break | ☐ |

### 21.6 Cross-Site Link Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| /ventures/narrative links to narrative.zeplow.com | External link opens correctly | ☐ |
| /ventures/logic links to logic.zeplow.com | External link opens correctly | ☐ |
| Footer links to Narrative and Logic sites work | External links open correctly | ☐ |
| All internal links work | No 404s on any page | ☐ |

### 21.7 Pipeline Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Publish new blog post in CMS | Post appears on zeplow.com/insights within 2 minutes | ☐ |
| Edit existing page in CMS | Changes reflected on live site within 2 minutes | ☐ |
| Change navigation in Site Config | Nav updates on live site after rebuild | ☐ |
| CMS/API server goes down | Live site continues working (static files on CDN) | ☐ |

---

## 22. POST-LAUNCH CHECKLIST

| # | Task | When |
|:---|:---|:---|
| 1 | Verify zeplow.com loads correctly on mobile + desktop | Day 1 |
| 2 | Verify www.zeplow.com redirects to zeplow.com | Day 1 |
| 3 | Submit sitemap to Google Search Console | Day 1 |
| 4 | Verify Cloudflare SSL is active | Day 1 |
| 5 | Run Lighthouse audit on all pages | Day 1 |
| 6 | Test contact form end-to-end (submit → email received) | Day 1 |
| 7 | Verify all cross-site links (to narrative.zeplow.com and logic.zeplow.com) | Day 1 |
| 8 | Set up Cloudflare Pages email notifications for failed builds | Day 1 |
| 9 | Verify deploy hook pipeline: CMS publish → API → deploy hook → rebuild | Day 1 |
| 10 | Monitor Cloudflare build times for first week | Week 1 |
| 11 | Check that content changes from CMS appear within 2 minutes | Day 2 |
| 12 | Test blog post creation end-to-end (CMS → API → build → live) | Day 2 |

---

## 23. KNOWN LIMITATIONS

The following limitations are accepted for V1 and may be addressed in future iterations:

| # | Limitation | Impact | Mitigation |
|:---|:---|:---|:---|
| 1 | No content versioning | If incorrect content is published, there is no built-in way to revert to a previous version | Manually edit and correct content in the CMS; Cloudflare Pages rollback can restore the last successful build |
| 2 | No offline/service worker support | Site requires an internet connection to load; no offline caching of pages | Acceptable for a corporate site — users are expected to have connectivity |
| 3 | No A/B testing capability | Cannot run split tests on page variants, CTAs, or content blocks | Future enhancement if conversion optimization becomes a priority |
| 4 | No build-time image optimization | Static export disables Next.js `<Image>` optimization | Mitigated by 4-stage pipeline: upload validation → Spatie size conversions → Cloudflare CDN/Polish → responsive `<picture>` rendering with `srcSet` and lazy loading |

---

*End of Parent Site PRD.*
