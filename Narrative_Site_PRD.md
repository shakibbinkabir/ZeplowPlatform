# ZEPLOW NARRATIVE SITE (narrative.zeplow.com) — PRODUCT REQUIREMENTS DOCUMENT (PRD)

**Version:** 1.0
**Date:** March 27, 2026
**Derived From:** Zeplow Platform Central PRD v1.1, Creative Agency Brand Document, Company Profile
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

---

## 1. SITE OVERVIEW & PURPOSE

### 1.1 What This Site Is

narrative.zeplow.com is the public website for **Zeplow Narrative** — the creative agency arm of the Zeplow group. It is a brand storytelling agency that rejects traditional advertising in favour of raw, human narratives. The site must embody this philosophy in every pixel: it should feel like a magazine, not a corporate brochure. It should feel editorial, intimate, and slightly provocative.

The site sells one idea: "We turn businesses into stories worth following." Every page either proves that promise with work or invites the visitor to experience it through a Heartbeat Review.

### 1.2 Properties

| Property | Value |
|:---|:---|
| Domain | `narrative.zeplow.com` |
| Framework | Next.js 14+ (App Router, static export) |
| Styling | Tailwind CSS v3 |
| Animation | Framer Motion |
| Output mode | Static export (`output: 'export'`) |
| Hosting | Cloudflare Pages (free tier) |
| Data source | `api.zeplow.com` (fetched at build time) |
| site_key | `narrative` |
| Repository | `zeplow-sites` monorepo → `apps/narrative/` |

### 1.3 What Makes This Site Unique vs. Parent & Logic

| Aspect | Narrative (narrative.zeplow.com) | Parent / Logic |
|:---|:---|:---|
| Archetype | The Lover / The Intimist | Parent: Empire Builder / Logic: The Ruler / Systems Architect |
| Tone | Provocative, human, unapologetic. Talks to CEOs like real people. | Parent: quiet confidence / Logic: calm, absolute, clinical |
| Typography | Serif headings (Playfair Display) — signals editorial, legacy | Logic: Monospace (JetBrains Mono) — signals code |
| Color accent | Vibrant Coral `#ff6f59` — the "rebel" energy | Logic: System Teal `#00b894` — the "active status" |
| Case study format | "Feature Story" (editorial headline, plot twist, legacy) | Logic: "Incident Report" (technical debrief) |
| Project detail page | FeatureStory-style layout | Logic: IncidentReport component |
| CTA language | "Book a Heartbeat Review" | Logic: "Book a Systems Audit" / Parent: "Get in Touch" |
| Visual imagery | Human-focused, raw photography, collage/texture overlays | Logic: Schematics, flowcharts, no stock photos of people |
| Success metric | Engagement, sentiment, brand love, DMs | Logic: Zero-touch transactions, hours saved, uptime |
| Content strategy | "Anti-Client" positioning, Brand Autopsies, Founder Confessions | Logic: Incident Reports, ROI documentation |

### 1.4 Target Audience

From the brand document — the **Ideal Customer Profile**:

- **Who:** Founders and CMOs of lifestyle brands (Restaurants, Hospitality, D2C, Services, Personal Care)
- **Stage:** Post-survival phase — looking to build a legacy, not just make a sale
- **Geography:** Bangladesh, MENA (UAE), Nordic Countries (Finland, Estonia, Lithuania), Poland, Romania
- **Psychographics:** They look and sound exactly like their competitors. They feel disconnected from their audience despite having a great product. They value authenticity over perfection.
- **The Buy:** They buy **chemistry and philosophy**, not a price list.

**Industries (Bangladesh):** Food & Beverage, Real Estate, Toiletries/Personal Care, Vapes, Agencies (white-label).
**Industries (Global):** Restaurants & Catering, Legal Services, Health & Fitness, Financial Services, Pet Services, Transportation.

### 1.5 Non-Goals

- Selling tech/automation services (that's logic.zeplow.com)
- Group-level overview or venture hub (that's zeplow.com)
- Client portal, dashboards, or login areas
- E-commerce or payment processing
- Public pricing page (pricing is custom per project, follows the "No-Loss Formula" internally)
- Newsletter signup system (out of scope for V1)

---

## 2. SITE IDENTITY & BRAND SYSTEM

### 2.1 Brand Position

| Element | Value |
|:---|:---|
| Name | Zeplow Narrative |
| Tagline | "Stories that sell." |
| Core Belief | "We create a landscape where brands are not just bought, but loved." |
| Positioning | "For businesses that refuse to be seen as just another faceless entity, we are the creative storytelling partner that replaces cold, transactional advertising with raw, intimate narratives." |
| Archetype | The Lover / The Intimist — bringing people closer together |
| Voice | The Honest Storyteller — unapologetic, human, insightful. The smartest friend in the room who tells you your marketing is boring because they want you to win. |
| Promise | "Under-promise and over-deliver. We promise to turn your business into a story worth following." |

### 2.2 Color Palette

| Color | Hex | CSS Variable | Usage | Psychology |
|:---|:---|:---|:---|:---|
| Pine Teal | `#034c3c` | `--color-primary` | Primary / Headers / Hero backgrounds | Depth, wisdom, stability |
| White Smoke | `#f4f4f4` | `--color-background` | Page backgrounds | High-quality paper; soft, premium |
| Coffee Bean | `#140004` | `--color-text` | Body text | Warmth and readability; softer than black |
| Vibrant Coral | `#ff6f59` | `--color-accent` | Accents / CTAs / Hover states | The "Rebel" energy; non-traditional |

### 2.3 Typography

| Role | Font | Weight | Vibe |
|:---|:---|:---|:---|
| Headings (H1–H3) | Playfair Display | 700 (Bold) | Modern serif. Evokes books, journalism, legacy. |
| Body / UI | Manrope | 400, 500, 600 | Geometric sans-serif. High readability, modern cleanliness. |

**Self-hosted fonts only.** Download font files into `public/fonts/`. Use `next/font/local`.

### 2.4 Visual Rules (from Brand Document)

| Rule | Detail |
|:---|:---|
| Focus on people | Always show the humans behind the brand, not just the product |
| Photography style | Raw, human, slightly imperfect. No stock photo perfection. |
| Texture/collage | Images can be treated with collage/texture overlays (Narrative's signature) |
| No stock photos | All imagery must be treated with the agency's style — no raw, generic stock |
| Tone in visuals | Intimate, warm, slightly editorial — like a feature in a premium magazine |
| Animations | Cinematic, scroll-driven reveals, page transitions. More expressive than Logic. |

### 2.5 Tailwind Configuration

```typescript
// apps/narrative/tailwind.config.ts
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

narrative.zeplow.com has **10 routes** (7 static + 3 dynamic):

| Route | Page | Template | Description |
|:---|:---|:---|:---|
| `/` | Home | `home` | Hero, problem statement ("The Invisibility Tax"), featured work, testimonials, CTA |
| `/about` | About | `about` | Brand story, manifesto, values, team |
| `/services` | Services | `services` | Full creative service catalog organized by discipline |
| `/work` | Portfolio Grid | `work` | All published projects in editorial grid |
| `/work/[slug]` | Project Detail | — | Individual project as "Feature Story" (dynamic route) |
| `/process` | Process/Methodology | `process` | The Heartbeat Check, the Narrative Arc, the "how we work" |
| `/insights` | Blog Listing | `insights` | All published blog posts for Narrative |
| `/insights/[slug]` | Blog Post Detail | — | Individual blog post (dynamic route) |
| `/contact` | Contact | `contact` | Contact form + "Book a Heartbeat Review" CTA |

### 3.2 Data Source Per Route

| Route | API Calls at Build Time |
|:---|:---|
| `/` | `getPage('narrative', 'home')` + `getProjects('narrative', { featured: true, limit: 3 })` + `getTestimonials('narrative')` |
| `/about` | `getPage('narrative', 'about')` + `getTeamMembers('narrative')` |
| `/services` | `getPage('narrative', 'services')` |
| `/work` | `getProjects('narrative')` |
| `/work/[slug]` | `getProject('narrative', slug)` — uses `generateStaticParams` |
| `/process` | `getPage('narrative', 'process')` |
| `/insights` | `getBlogPosts('narrative')` |
| `/insights/[slug]` | `getBlogPost('narrative', slug)` — uses `generateStaticParams` |
| `/contact` | `getPage('narrative', 'contact')` |

### 3.3 Navigation Structure

Driven by API config (`getSiteConfig('narrative')`). Expected nav items:

| Label | URL | External |
|:---|:---|:---|
| About | /about | No |
| Services | /services | No |
| Work | /work | No |
| Process | /process | No |
| Insights | /insights | No |
| Contact | /contact | No |

CTA button in nav: "Book a Heartbeat Review" → `/contact`

---

## 4. MONOREPO CONTEXT — WHERE THIS SITE LIVES

### 4.1 Repository Structure

```
zeplow-sites/
├── apps/
│   ├── parent/          ← zeplow.com
│   ├── narrative/       ← THIS SITE (narrative.zeplow.com)
│   └── logic/           ← logic.zeplow.com
├── packages/
│   ├── ui/              ← Shared React components
│   ├── api/             ← Shared API client + TypeScript types
│   └── config/          ← Shared brand colors + font mappings
├── turbo.json
├── package.json
└── pnpm-workspace.yaml
```

### 4.2 Build Commands

```json
{
  "dev:narrative": "turbo run dev --filter=narrative",
  "build:narrative": "turbo run build --filter=narrative"
}
```

**Dev server port:** 3001 (parent: 3000, narrative: 3001, logic: 3002).

---

## 5. SHARED PACKAGES

Same as the other site PRDs. The Narrative site imports from `@zeplow/api`, `@zeplow/ui`, and `@zeplow/config`. The `siteKey` parameter (`'narrative'`) differentiates which content is fetched.

Key functions used by the Narrative site:

| Function | Returns | Used By |
|:---|:---|:---|
| `getSiteConfig('narrative')` | `SiteConfig` | Root layout |
| `getPage('narrative', slug)` | `Page` | Static pages |
| `getProjects('narrative')` | `ProjectListItem[]` | Work grid |
| `getProjects('narrative', { featured: true, limit: 3 })` | `ProjectListItem[]` | Home featured |
| `getProject('narrative', slug)` | `Project` | Feature Story detail |
| `getBlogPosts('narrative')` | `BlogPostListItem[]` | Insights listing |
| `getBlogPost('narrative', slug)` | `BlogPost` | Blog post detail |
| `getTeamMembers('narrative')` | `TeamMember[]` | About page |
| `getTestimonials('narrative')` | `Testimonial[]` | Home page |

---

## 6. NEXT.JS CONFIGURATION

```javascript
// apps/narrative/next.config.js
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: { unoptimized: true },
  transpilePackages: ['@zeplow/ui', '@zeplow/api', '@zeplow/config'],
}
module.exports = nextConfig
```

```json
// apps/narrative/package.json
{
  "name": "narrative",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "next dev -p 3001",
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

---

## 7. ROOT LAYOUT

```typescript
// apps/narrative/app/layout.tsx

import { getSiteConfig } from '@zeplow/api';
import { Navigation, Footer } from '@zeplow/ui';
import localFont from 'next/font/local';
import type { Metadata } from 'next';
import './globals.css';

const SITE_KEY = 'narrative';

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
  metadataBase: new URL('https://narrative.zeplow.com'),
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

---

## 8. PAGE-BY-PAGE SPECIFICATIONS

### 8.1 Home Page (`/`)

**File:** `apps/narrative/app/page.tsx`
**Purpose:** Hook the visitor emotionally. Name the enemy (invisibility). Show proof (featured work). Invite them in (Heartbeat Review).

**Data Sources:**

```typescript
const page = await getPage('narrative', 'home');
const featuredProjects = await getProjects('narrative', { featured: true, limit: 3 });
const testimonials = await getTestimonials('narrative');
```

**Expected Content Blocks (from CMS):**

1. `hero` — Headline: "We don't make ads. We make your business unforgettable." / CTA: "Book a Heartbeat Review" → /contact / Background: Pine Teal `#034c3c`
2. `text` — "The Invisibility Tax" — the core problem statement: "Your product is great. But no one's telling the story. Every day your brand goes unnoticed, you're paying an invisible tax — in lost trust, lost customers, and lost legacy."
3. `projects` — Featured work (count: 3, featured_only: true)
4. `testimonials` — Client testimonials block
5. `cta` — "If this feels like your kind of thinking, we should talk." / Style: primary → /contact

**Additional data rendered outside blocks:**
- Featured projects using `ProjectCard` components
- Testimonials using `TestimonialCard` components
- `OrganizationSchema` JSON-LD

**Implementation:**

```typescript
// apps/narrative/app/page.tsx

import { getPage, getProjects, getTestimonials, getSiteConfig } from '@zeplow/api';
import { ContentRenderer, ProjectCard, TestimonialCard, OrganizationSchema } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'home');
  return {
    title: page.seo.title,
    description: page.seo.description,
    openGraph: {
      title: page.seo.title,
      description: page.seo.description,
      images: page.seo.og_image ? [page.seo.og_image] : [],
      url: 'https://narrative.zeplow.com',
      type: 'website',
    },
  };
}

export default async function HomePage() {
  const [page, featuredProjects, testimonials, config] = await Promise.all([
    getPage(SITE_KEY, 'home'),
    getProjects(SITE_KEY, { featured: true, limit: 3 }),
    getTestimonials(SITE_KEY),
    getSiteConfig(SITE_KEY),
  ]);

  return (
    <main>
      <OrganizationSchema
        name="Zeplow Narrative"
        url="https://narrative.zeplow.com"
        description="Stories that sell. Brand storytelling, identity & content systems."
        logo="https://narrative.zeplow.com/logo.png"
        email={config.contact_email}
        sameAs={Object.values(config.social_links)}
      />
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
    </main>
  );
}
```

---

### 8.2 About Page (`/about`)

**File:** `apps/narrative/app/about/page.tsx`
**Purpose:** The manifesto page. Who we are, what we believe, why we exist.

**Data Sources:**

```typescript
const page = await getPage('narrative', 'about');
const team = await getTeamMembers('narrative');
```

**Expected Content Blocks:**

1. `hero` — "About Zeplow Narrative"
2. `text` — Purpose: "To bridge the gap between businesses and people through the power of raw, human storytelling."
3. `text` — Vision: "To turn businesses into household names by showcasing the people and passion behind the logo."
4. `text` — Mission: "To help brands unlock their full potential by rejecting traditional, transactional advertising in favor of unconventional, intimate narratives."
5. `text` — Values section:
   - **Radical Authenticity** — "No fake stories. We don't polish turds. If the story isn't real, we don't tell it."
   - **Intimacy over Noise** — "Deep connection beats loud reach. We'd rather 1,000 people feel something than 100,000 scroll past."
   - **People First** — "Human elements over metrics. We measure success by engagement and sentiment, not vanity clicks."
6. `stats` — Key metrics: Brands served, campaigns produced, content pieces created
7. `team` — Team display (use_all: true)
8. `cta` — "Ready to tell your story?" → /contact

**Narrative-Specific Component: `AntiClientBlock.tsx`**

Located at `apps/narrative/components/AntiClientBlock.tsx`. A styled content block that showcases Narrative's "Anti-Client" positioning: "Brands We Don't Work With" — a list of filter statements that repel the wrong clients and attract the right ones:

- "We don't work with brands that chase trends."
- "We don't work with founders who hide behind logos."
- "We don't work with businesses that want ads, not truth."
- "If this disqualifies you, good. If it doesn't — apply."

This component can be used on the About page or Home page via a `raw_html` or custom content block.

---

### 8.3 Services Page (`/services`)

**File:** `apps/narrative/app/services/page.tsx`
**Purpose:** Full creative service catalog, organised by discipline. Not a price list — a capability showcase.

**Data Source:**

```typescript
const page = await getPage('narrative', 'services');
```

**Expected Content Blocks:**

1. `hero` — "What We Do" / Sub: "We build brand perception through story, consistency, and execution quality."
2. `text` — Intro: "Every service below is designed to solve a specific business bottleneck. We don't sell isolated deliverables. We build brand perception."
3. `cards` — **Service Disciplines** (7 categories from brand doc):
   - **Strategy & Planning (The Brain)** — Brand Audit ("Heartbeat Review"), Positioning & Legacy Roadmap, Content Ecosystem Design, Founder Personal Brand Architecture, GTM Strategy, ICP Development, Competitor Analysis, CX Journey Mapping
   - **Creative Shoots (The Raw Material)** — Product Shoot, OVC, Campaign Shoot, Corporate Event, Documentary, UGC Production, Lifestyle, Case Study, Aerial/Drone, Architectural, Talking Head/Interview
   - **Video Editing (The Story)** — Short Form, Long Form, Documentary Narrative, Case Study, Product Reels, OVC, Explainer Videos, Motion Graphics, 2D/3D Animation, Podcast Editing
   - **Brand Photography (The Image)** — Executive Portraits, Founder's Story, Culture & Vibes, Process Photography, Food & Recipes, Model/Lifestyle, Retouching
   - **Caption & Copywriting (The Voice)** — Scriptwriting, Blogs & Editorial, Ads Copy, Email Sequences, Website/UI Copy, Verbal Identity Guidelines
   - **Graphics & Visuals (The Face)** — Brand Identity System, Logo Design, Packaging & Print, Social Media Posters, Decks & Docs, Illustrations, OOH/Large Format, Thumbnails
   - **Management & Growth (The Engine)** — Social Media Marketing, Community Engagement, Reputation Management, Influencer Management, Paid Media, Heartbeat Report, Email/Newsletter, Founder Profile Management
4. `text` — The "Heartbeat Check" approval process: Strategy Check → Craft Check → Truth Check. "If it feels like a standard ad, kill it."
5. `cta` — "Start with a Heartbeat Review" → /contact

---

### 8.4 Work — Portfolio Grid (`/work`)

**File:** `apps/narrative/app/work/page.tsx`
**Purpose:** All published Narrative projects in an editorial-style grid. This should feel like a curated gallery, not a standard portfolio page.

**Data Source:**

```typescript
const projects = await getProjects('narrative');
```

**Renders:** Grid of `ProjectCard` components with an editorial feel. Each card shows: title (editorial-style), one_liner, first image (full-bleed or large), tags. Links to `/work/{slug}`.

```typescript
// apps/narrative/app/work/page.tsx

import { getProjects } from '@zeplow/api';
import { ProjectCard } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export const metadata: Metadata = {
  title: 'Our Work — Zeplow Narrative',
  description: 'Stories we\'ve told. Brands we\'ve transformed. Perception we\'ve shifted.',
};

export default async function WorkPage() {
  const projects = await getProjects(SITE_KEY);

  return (
    <main>
      <section>
        <h1>Our Work</h1>
        <p>Every project below started with a brand that felt invisible. Here's what happened next.</p>
        <div>
          {projects.map((project) => (
            <ProjectCard key={project.id} project={project} siteKey={SITE_KEY} />
          ))}
        </div>
      </section>
    </main>
  );
}
```

---

### 8.5 Work — Project Detail / Feature Story (`/work/[slug]`)

**File:** `apps/narrative/app/work/[slug]/page.tsx`
**Purpose:** Individual project presented as an editorial **"Feature Story"** — the signature case study format from the brand document.

This is the most unique component on the Narrative site. Unlike Logic's clinical "Incident Report," Narrative presents projects like a feature in a premium magazine, using the Narrative Arc structure:

**The Narrative Arc (4 steps):**
1. **The Villain** — The enemy (invisibility, soulless marketing, generic positioning)
2. **The Spark** — The shift in perspective ("People buy feelings, not food")
3. **The Weapon** — The strategy and craft used to fix the problem
4. **The Victory** — The transformation (revenue growth + brand love)

**Feature Story Format:**
- **Headline:** Editorial title (e.g., "The Burger Joint That Became a Heartbeat")
- **The Strategy:** The "Plot Twist" — the unconventional idea that was pitched
- **The Legacy:** Qualitative results (DMs, customer love) paired with quantitative (ROI)

**How this maps to the Project API data:**

| Feature Story Section | API Field |
|:---|:---|
| Editorial headline | `project.title` |
| One-liner / tagline | `project.one_liner` |
| The Villain / Problem | `project.challenge` |
| The Weapon / Solution | `project.solution` |
| The Victory / Outcome | `project.outcome` |
| Visual evidence | `project.images` |
| Tags / categories | `project.tags` |
| Client attribution | `project.client_name` + `project.industry` |
| Live URL | `project.url` |

**Narrative-Specific Component: `HeartbeatCTA.tsx`**

Located at `apps/narrative/components/HeartbeatCTA.tsx`. A styled CTA block specific to Narrative's "Heartbeat Review" language. Used at the bottom of every Feature Story:

```
"Every brand has a heartbeat.
Some are strong. Some are fading. Most haven't been checked.
Book a Heartbeat Review — we'll tell you where yours stands."
```

**Implementation:**

```typescript
// apps/narrative/app/work/[slug]/page.tsx

import { getProjects, getProject } from '@zeplow/api';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateStaticParams() {
  const projects = await getProjects(SITE_KEY);
  return projects.map((project) => ({ slug: project.slug }));
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

  return (
    <main>
      <article>
        {/* Hero — editorial headline + client + industry */}
        <header>
          <p>{project.client_name} · {project.industry}</p>
          <h1>{project.title}</h1>
          <p>{project.one_liner}</p>
        </header>

        {/* Hero image — full width */}
        {project.images[0] && (
          <img src={project.images[0]} alt={project.title} />
        )}

        {/* The Villain — what was broken */}
        {project.challenge && (
          <section>
            <h2>The Challenge</h2>
            <p>{project.challenge}</p>
          </section>
        )}

        {/* The Weapon — what we built */}
        {project.solution && (
          <section>
            <h2>The Strategy</h2>
            <p>{project.solution}</p>
          </section>
        )}

        {/* Supporting images */}
        {project.images.length > 1 && (
          <div>
            {project.images.slice(1).map((img, i) => (
              <img key={i} src={img} alt={`${project.title} — ${i + 2}`} loading="lazy" />
            ))}
          </div>
        )}

        {/* The Victory — results */}
        {project.outcome && (
          <section>
            <h2>The Outcome</h2>
            <p>{project.outcome}</p>
          </section>
        )}

        {/* Tags */}
        {project.tags.length > 0 && (
          <div>
            {project.tags.map((tag) => (
              <span key={tag}>{tag}</span>
            ))}
          </div>
        )}

        {/* Live link */}
        {project.url && (
          <a href={project.url} target="_blank" rel="noopener noreferrer">
            View Live →
          </a>
        )}
      </article>

      {/* Heartbeat CTA */}
      {/* <HeartbeatCTA /> */}
    </main>
  );
}
```

---

### 8.6 Process Page (`/process`)

**File:** `apps/narrative/app/process/page.tsx`
**Purpose:** Show how Narrative works. The 6-step workflow + the Heartbeat Check approval process.

**Data Source:**

```typescript
const page = await getPage('narrative', 'process');
```

**Expected Content Blocks (from company profile workflow):**

1. `hero` — "Our Process" / "We don't guess. We diagnose."
2. `text` — Intro: "Every engagement follows the same framework. It's how we consistently turn invisible brands into household names."
3. `cards` or `stats` — The 6 steps:
   - **01 · Discovery** — "We start by listening. What's broken? What's ambitious? What keeps you up at night? We map your brand perception before proposing anything."
   - **02 · Strategy** — "We don't guess. We diagnose the root cause, define the Narrative Arc, and build a roadmap with clear milestones. You see the plan before we touch a camera."
   - **03 · Architecture** — "Content pillars, visual direction, campaign structure. The blueprint for your brand's story system."
   - **04 · Execution** — "We produce. You get updates, not meetings. Focused sprints — shipping real content every week. We push back when something doesn't serve the story."
   - **05 · Delivery** — "Brand-ready files, content systems, and launch assets. Tested, polished, and ready to perform."
   - **06 · Partnership** — "Launch isn't the end. We manage, optimise, and grow your brand monthly. You retain a creative team for the price of one hire. We resell our value every 30 days."
4. `text` — **The Heartbeat Check** (approval process from brand doc): Every piece of work must pass three gates:
   - **Strategy Check:** Does this solve the specific client problem?
   - **Craft Check:** Is the visual distinct and expensive-looking?
   - **Truth Check:** Is it "Human" or does it feel like a "Corporate Ad"? If it feels like a standard ad, kill it.
5. `cta` — "Start with a Heartbeat Review" → /contact

---

### 8.7 Insights — Blog Listing (`/insights`)

**File:** `apps/narrative/app/insights/page.tsx`

**Data Source:**

```typescript
const posts = await getBlogPosts('narrative');
```

**Renders:** Editorial-style grid of `BlogCard` components. Each card: title (serif heading), excerpt, cover image, tags, author, published date. Links to `/insights/{slug}`.

```typescript
export const metadata: Metadata = {
  title: 'Insights — Zeplow Narrative',
  description: 'Thoughts on brand storytelling, founder visibility, and making businesses unforgettable.',
};
```

---

### 8.8 Insights — Blog Post Detail (`/insights/[slug]`)

**File:** `apps/narrative/app/insights/[slug]/page.tsx`

Same pattern as other sites — `generateStaticParams` to enumerate slugs, `getBlogPost('narrative', slug)` for full content, `ArticleSchema` JSON-LD. Body HTML rendered via `dangerouslySetInnerHTML`.

Blog posts on Narrative should feel like magazine articles — large cover images, generous typography, pull quotes.

---

### 8.9 Contact Page (`/contact`)

**File:** `apps/narrative/app/contact/page.tsx`
**Purpose:** Contact form + the "Heartbeat Review" CTA.

**Data Source:**

```typescript
const page = await getPage('narrative', 'contact');
```

**Expected Content Blocks:**

1. `hero` — "Let's Make Your Brand Unforgettable" / "Book a Heartbeat Review — we'll diagnose your brand health, map the gaps, and show you the story your audience is waiting for."
2. `text` — Contact info: hello@zeplow.com, social links
3. The `ContactForm` client component with `siteKey="narrative"` and `siteDomain="narrative.zeplow.com"`

**Narrative-Specific CTA Component: `HeartbeatCTA.tsx`**

Can be placed above the form:

```
"Every brand has a heartbeat. Some are strong. Some are fading. Most haven't been checked."
"A Heartbeat Review is a deep dive into your brand health, sentiment, and gaps —
the starting point for every story we tell."
```

---

## 9. CONTENT BLOCK RENDERING

Same `ContentRenderer` component as all sites. The visual design of block components will differ from Logic/Parent to reflect Narrative's serif typography, warmer palette, and editorial aesthetic.

**Narrative-specific rendering considerations:**

| Block | Narrative Visual Treatment |
|:---|:---|
| `hero` | Pine Teal background, Playfair Display serif heading, coral CTA button, warm/cinematic feel |
| `text` | Manrope body on warm White Smoke background, generous line height, pull-quote styling |
| `cards` | Warm, image-forward cards. Rounded corners, soft shadows. Not bordered/clinical like Logic. |
| `cta` | Coral button on teal background, editorial language ("Book a Heartbeat Review") |
| `stats` | Playfair Display numbers, warm colour treatment, subtle animation |
| `testimonials` | Larger quote text, attributed with name + role. Feels like a magazine endorsement. |
| `projects` | Image-dominant grid. Minimal text on card. Click to reveal the story. |
| `gallery` | Full-bleed image gallery with lightbox. Photographic, not diagrammatic. |
| `image` | Full-width treatment with texture/overlay option |

---

## 10. CONTACT FORM

Same `ContactForm` component from `@zeplow/ui`:

```typescript
<ContactForm siteKey="narrative" siteDomain="narrative.zeplow.com" />
```

POSTs to `api.zeplow.com/sites/v1/narrative/contact`. Email notification includes `site_key: narrative` in the subject. Budget range options same as other sites.

---

## 11. SEO STRATEGY

### 11.1 Meta Tags

Same strategy — every page gets title, description, OG tags, canonical from `page.seo`.

### 11.2 Sitemap

```typescript
// apps/narrative/app/sitemap.ts

import { getPages, getBlogPosts, getProjects } from '@zeplow/api';
import type { MetadataRoute } from 'next';

const BASE_URL = 'https://narrative.zeplow.com';
const SITE_KEY = 'narrative';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const pages = await getPages(SITE_KEY);
  const blogPosts = await getBlogPosts(SITE_KEY);
  const projects = await getProjects(SITE_KEY);

  const pageEntries = pages.map((page) => ({
    url: `${BASE_URL}/${page.slug === 'home' ? '' : page.slug}`,
    lastModified: page.published_at,
    changeFrequency: 'monthly' as const,
    priority: page.slug === 'home' ? 1.0 : 0.8,
  }));

  const projectEntries = projects.map((project) => ({
    url: `${BASE_URL}/work/${project.slug}`,
    lastModified: new Date().toISOString(),
    changeFrequency: 'monthly' as const,
    priority: 0.7,
  }));

  const blogEntries = blogPosts.map((post) => ({
    url: `${BASE_URL}/insights/${post.slug}`,
    lastModified: post.published_at,
    changeFrequency: 'weekly' as const,
    priority: 0.6,
  }));

  return [...pageEntries, ...projectEntries, ...blogEntries];
}
```

### 11.3 robots.txt

```
User-agent: *
Allow: /
Sitemap: https://narrative.zeplow.com/sitemap.xml
```

### 11.4 Structured Data

| Page | Schema | Component |
|:---|:---|:---|
| Home | Organization | `OrganizationSchema` — Zeplow Narrative |
| Blog posts | Article | `ArticleSchema` |

---

## 12. PERFORMANCE REQUIREMENTS

Same targets: Lighthouse 95+, TTFB < 50ms, LCP < 1.2s, page weight < 200 KB (excl. images). Self-hosted fonts, minimal JS, Tailwind purge, no third-party scripts.

**Narrative-specific note:** The site will use more and larger images than Logic (photography vs. schematics). Image optimization is critical:

- All images served from CMS through Cloudflare CDN (cached at edge)
- Hero images: loaded eagerly, optimised for LCP
- Portfolio images: `loading="lazy"` aggressively
- Recommend Spatie MediaLibrary conversions on CMS side (medium: 800x600 for cards, large: 1920x1080 for heroes)

---

## 13. CLOUDFLARE PAGES — BUILD & DEPLOY

### 13.1 Project Configuration

| Setting | Value |
|:---|:---|
| Project name | `zeplow-narrative` |
| Production branch | `main` |
| Build command | `cd apps/narrative && npx next build` |
| Build output directory | `apps/narrative/out` |
| Root directory | `/` |
| Node.js version | `18` |
| Custom domain | `narrative.zeplow.com` |

### 13.2 Environment Variables (Cloudflare Pages)

| Variable | Value |
|:---|:---|
| `NEXT_PUBLIC_API_URL` | `https://api.zeplow.com` |
| `NEXT_PUBLIC_SITE_KEY` | `narrative` |
| `NODE_VERSION` | `18` |

### 13.3 Deploy Triggers

| Trigger | How |
|:---|:---|
| Content change | CMS publish → API sync → API fires `CF_DEPLOY_HOOK_NARRATIVE` → Cloudflare rebuilds |
| Code change | Push to `main` → Cloudflare auto-builds |
| Manual | Cloudflare Pages dashboard |

---

## 14. DNS & DOMAIN CONFIGURATION

| Type | Name | Content | Proxy |
|:---|:---|:---|:---|
| CNAME | `narrative` | `zeplow-narrative.pages.dev` | Yes |

SSL: Automatic via Cloudflare — Universal SSL, free.

---

## 15. SECURITY HEADERS

**File:** `apps/narrative/public/_headers`

```
/*
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  Permissions-Policy: camera=(), microphone=(), geolocation=()
```

---

## 16. CONTENT SEEDING — WHAT TO PUBLISH IN CMS

All content below is created in the CMS as records with `site_key: narrative`.

### 16.1 Pages to Create

| slug | title | template | Content Source |
|:---|:---|:---|:---|
| `home` | Home | `home` | Hero ("We don't make ads...") + Invisibility Tax text + featured projects + testimonials + CTA |
| `about` | About | `about` | Purpose/vision/mission from creative brand doc + values (Radical Authenticity, Intimacy over Noise, People First) + team |
| `services` | Services | `services` | 7 service disciplines from brand doc + Heartbeat Check approval process |
| `work` | Our Work | `work` | Intro text (grid populated by projects API) |
| `process` | Our Process | `process` | 6-step workflow from company profile + Heartbeat Check gates |
| `contact` | Contact | `contact` | Hero + Heartbeat Review CTA text |
| `insights` | Insights | `insights` | Intro text (listing populated by blog API) |

### 16.2 Team Members to Create

| name | role | bio | is_founder | sort_order |
|:---|:---|:---|:---|:---|
| Shadman Sakib | Co-Founder & CEO | Strategy, direction, and brand & venture leadership. Leads strategy, client relationships, and brand direction across the Zeplow group. Asks "why does this exist?" before anything gets built. The person who sets the course. | true | 0 |
| Shakib Bin Kabir | Co-Founder & CTO | Systems, automation, AI & technical architecture. Leads technology, product development, and infrastructure decisions across the Zeplow group. The person who builds the machine. | true | 1 |

**Note:** On Narrative, Shadman is listed first (sort_order 0) because he is the delivery owner for Narrative. On Logic, Shakib is first.

### 16.3 Projects to Create

Narrative projects are creative/branding case studies. These will be different projects from Logic (which shows tech projects). Seed the initial projects that demonstrate Narrative's storytelling capability. The `challenge`, `solution`, and `outcome` fields should follow the Narrative Arc:

| Field | Narrative Arc Mapping |
|:---|:---|
| challenge | "The Villain" — what was broken (invisibility, generic positioning, soulless marketing) |
| solution | "The Weapon" — the strategy and craft used (content pillars, identity redesign, campaign structure) |
| outcome | "The Victory" — the transformation (engagement growth, DMs, brand perception shift, revenue) |

Example projects to seed (adapt from actual Narrative work or create representative ones):

| title | one_liner | industry | featured |
|:---|:---|:---|:---|
| [Brand Project 1] | "Turning a local restaurant into a neighbourhood story" | Food & Beverage | true |
| [Brand Project 2] | "From faceless D2C brand to founder-led movement" | Personal Care | true |
| [Brand Project 3] | "Making a real estate firm feel human in a cold market" | Real Estate | true |

### 16.4 Site Config to Create

| Field | Value |
|:---|:---|
| nav_items | About (/about), Services (/services), Work (/work), Process (/process), Insights (/insights), Contact (/contact) |
| footer_text | © 2026 Zeplow LTD. All rights reserved. |
| cta_text | Book a Heartbeat Review |
| cta_url | /contact |
| social_links | linkedin, instagram, whatsapp URLs |
| contact_email | hello@zeplow.com |
| footer_links | Group: "The Zeplow Group" → Zeplow (https://zeplow.com), Zeplow Narrative (https://narrative.zeplow.com), Zeplow Logic (https://logic.zeplow.com); Group: "Company" → About (/about), Services (/services), Work (/work), Process (/process), Insights (/insights), Contact (/contact) |

### 16.5 Testimonials to Create

Seed at least 2-3 testimonials. The ideal testimonial for Narrative (from brand doc): something that speaks to emotional transformation, not just metrics. Example tone: "They didn't just redesign our brand. They told the story we were too close to see." / "The comments aren't just about the food anymore; they're talking about us."

### 16.6 Content Tone for Narrative Site

The Narrative site speaks as **The Honest Storyteller** — provocative, human, and insightful.

**Do:**
- Focus on the people behind the brand.
- Use conversational and provocative language.
- Showcase the "why" and the struggle.
- Measure success by engagement and sentiment.
- Challenge the client to be braver.

**Don't:**
- Focus solely on product specs or "Buy Now" buttons.
- Use corporate jargon or robotic professionalism.
- Present a plastic, "perfect" stock-photo image.
- Measure success only by vanity metrics (clicks).
- Act like a "yes-man" or simple order taker.

**Key phrases from the brand documents:**
- "Stories that sell."
- "We don't make ads. We make your business unforgettable."
- "The Invisibility Tax" — what your brand is paying every day it goes unnoticed.
- "Book a Heartbeat Review."
- "We turn businesses into stories worth following."
- "If this disqualifies you, good. If it doesn't — apply."
- "We promise to turn your business into a story worth following."
- "Are you looking for a quick sale, or do you want to build a brand people care about?"

**Tone by Context (from brand doc):**
- **Sales:** Challenging — "Are you looking for a quick sale, or do you want to build a brand people care about?"
- **Onboarding:** Reassuring — "We're in this with you now. We've got the strategy, you run the floor."
- **Crisis:** Transparent — "We missed the mark. Here is why it happened, and here is the fix."
- **Celebration:** Shared — "The comments aren't just about the food; they're talking about you."

---

## 17. ENVIRONMENT VARIABLES

### 17.1 Local Development (.env.local)

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_SITE_KEY=narrative
```

### 17.2 Production (Cloudflare Pages)

| Variable | Value |
|:---|:---|
| `NEXT_PUBLIC_API_URL` | `https://api.zeplow.com` |
| `NEXT_PUBLIC_SITE_KEY` | `narrative` |
| `NODE_VERSION` | `18` |

---

## 18. DIRECTORY STRUCTURE

```
apps/narrative/
├── app/
│   ├── globals.css
│   ├── layout.tsx                     # Root layout (Playfair Display + Manrope, nav, footer)
│   ├── page.tsx                       # Home (/)
│   ├── about/
│   │   └── page.tsx                   # About (/about)
│   ├── services/
│   │   └── page.tsx                   # Services (/services)
│   ├── work/
│   │   ├── page.tsx                   # Portfolio grid (/work)
│   │   └── [slug]/
│   │       └── page.tsx               # Feature Story (/work/[slug])
│   ├── process/
│   │   └── page.tsx                   # Process/methodology (/process)
│   ├── insights/
│   │   ├── page.tsx                   # Blog listing (/insights)
│   │   └── [slug]/
│   │       └── page.tsx               # Blog post detail (/insights/[slug])
│   ├── contact/
│   │   └── page.tsx                   # Contact form (/contact)
│   ├── sitemap.ts                     # Dynamic sitemap (pages + projects + blog)
│   └── robots.ts
├── components/                        # Narrative-site-specific components
│   ├── HeartbeatCTA.tsx               # "Book a Heartbeat Review" styled CTA
│   └── AntiClientBlock.tsx            # "Brands We Don't Work With" filter block
├── public/
│   ├── fonts/
│   │   ├── PlayfairDisplay-Bold.woff2
│   │   ├── Manrope-Regular.woff2
│   │   ├── Manrope-Medium.woff2
│   │   ├── Manrope-SemiBold.woff2
│   │   └── Manrope-Bold.woff2
│   ├── robots.txt
│   ├── _headers
│   ├── favicon.ico
│   ├── logo.png
│   └── og-default.jpg
├── next.config.js
├── tailwind.config.ts
├── postcss.config.js
├── tsconfig.json
└── package.json
```

---

## 19. ERROR HANDLING

Same as other sites. Build-time failures keep previous deploy live. Contact form shows fallback messages. Broken images show alt text. Site fully functional without JS except contact form.

---

## 20. IMPLEMENTATION ORDER

### Phase 1: App Scaffolding (Day 1)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 1.1 | Create `apps/narrative` in monorepo | Next.js with App Router, TypeScript, Tailwind | Monorepo infra |
| 1.2 | Configure `next.config.js` | `output: 'export'`, `transpilePackages` | 1.1 |
| 1.3 | Configure `tailwind.config.ts` | Narrative colors (Pine Teal, Coral), serif heading font | 1.1 |
| 1.4 | Download and self-host fonts | Playfair Display Bold + Manrope 400/500/600/700 | 1.1 |
| 1.5 | Create `globals.css` | Tailwind directives, base styles | 1.3 |
| 1.6 | Verify `pnpm dev:narrative` runs on port 3001 | Dev server working | 1.1–1.5 |

### Phase 2: Layout & Core Components (Day 2)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 2.1 | Create root layout | Playfair Display + Manrope fonts, `getSiteConfig('narrative')`, Navigation + Footer | 1.6, shared packages |
| 2.2 | Create `HeartbeatCTA.tsx` component | Narrative-specific "Book a Heartbeat Review" CTA | 1.1 |
| 2.3 | Create `AntiClientBlock.tsx` component | "Brands We Don't Work With" filter block | 1.1 |
| 2.4 | Create `public/_headers` and `public/robots.txt` | Security headers, robots | 1.1 |

### Phase 3: Page Data Layer (Days 3–4)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 3.1 | Wire up Home page | `getPage` + `getProjects` + `getTestimonials` + `OrganizationSchema` | 2.1 |
| 3.2 | Wire up About page | `getPage` + `getTeamMembers` + AntiClientBlock | 2.1, 2.3 |
| 3.3 | Wire up Services page | `getPage` with 7 service disciplines | 2.1 |
| 3.4 | Wire up Work grid page | `getProjects('narrative')` + `ProjectCard` grid | 2.1 |
| 3.5 | Wire up Work/[slug] Feature Story detail | `getProject` + `generateStaticParams` + HeartbeatCTA | 2.1, 2.2 |
| 3.6 | Wire up Process page | `getPage` with 6-step workflow + Heartbeat Check | 2.1 |
| 3.7 | Wire up Insights listing | `getBlogPosts` + `BlogCard` | 2.1 |
| 3.8 | Wire up Insights/[slug] detail | `getBlogPost` + `generateStaticParams` + `ArticleSchema` | 2.1 |
| 3.9 | Wire up Contact page | `getPage` + `ContactForm` + HeartbeatCTA | 2.1, 2.2 |
| 3.10 | Create sitemap.ts | Pages + projects + blog posts | 3.1–3.9 |

### Phase 4: Build Verification (Day 5)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 4.1 | Create `generateStaticParams` for work and blog | Enumerate all slugs | 3.5, 3.8 |
| 4.2 | Run `pnpm build:narrative` locally | Verify static export succeeds | 3.1–3.10 |
| 4.3 | Inspect generated HTML | Check meta tags, JSON-LD, Feature Story rendering | 4.2 |

### Phase 5: Cloudflare Deployment (Day 5–6)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 5.1 | Create Cloudflare Pages project `zeplow-narrative` | Connect to GitHub | Monorepo on GitHub |
| 5.2 | Configure build settings and env vars | Build command, output dir, API URL, site key | 5.1 |
| 5.3 | Add custom domain `narrative.zeplow.com` | CNAME record | DNS configured |
| 5.4 | Push to `main` and verify first deploy | Site loads | 5.1–5.3 |
| 5.5 | Get deploy hook URL and add to API `.env` | `CF_DEPLOY_HOOK_NARRATIVE` | 5.1, API deployed |

### Phase 6: Content Seeding (Days 6–7)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 6.1 | Seed all 7 Narrative pages in CMS | home, about, services, work, process, insights, contact | CMS deployed |
| 6.2 | Seed 2 team members (Shadman first) | Shadman (CEO, sort 0) + Shakib (CTO, sort 1) | CMS deployed |
| 6.3 | Seed Narrative projects (3+ featured) | Creative/branding case studies with Narrative Arc data | CMS deployed |
| 6.4 | Seed Narrative site config | Nav, footer, CTA "Book a Heartbeat Review", socials | CMS deployed |
| 6.5 | Seed 2-3 testimonials | Emotional transformation tone | CMS deployed |
| 6.6 | Trigger resync from CMS | Resync All → narrative | 6.1–6.5 |
| 6.7 | Verify site shows content | Visit narrative.zeplow.com, check all pages | 6.6 |

### Phase 7: Design & Polish (Days 8+)

| # | Task | Details |
|:---|:---|:---|
| 7.1 | Design and implement all block components with Narrative aesthetic | Serif headings, warm palette, coral accents, editorial feel |
| 7.2 | Design and implement Feature Story project layout | Editorial magazine-style case study with Narrative Arc |
| 7.3 | Design and implement HeartbeatCTA visual | Warm, inviting, prominent |
| 7.4 | Design and implement AntiClientBlock visual | Bold, provocative, filter-style |
| 7.5 | Design and implement Navigation (Narrative brand) | Desktop + mobile |
| 7.6 | Design and implement Footer (Narrative brand) | Links, socials, copyright |
| 7.7 | Add Framer Motion animations | Cinematic page transitions, scroll reveals, image parallax |
| 7.8 | Lighthouse audit | Target 95+ (pay attention to image-heavy pages) |
| 7.9 | Cross-browser testing | Chrome, Firefox, Safari, mobile |
| 7.10 | Final QA | All links, forms, images, Feature Stories |

---

## 21. TESTING CHECKLIST

### 21.1 Build Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| `pnpm build:narrative` succeeds | Static files in `apps/narrative/out/` | ☐ |
| All 10 routes generate HTML files | Check `out/` directory | ☐ |
| Project dynamic routes generate for all published projects | One HTML per project slug | ☐ |
| Blog dynamic routes generate for all published posts | One HTML per blog slug | ☐ |

### 21.2 Page Load Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| `narrative.zeplow.com` loads | Home with hero, Invisibility Tax, featured projects, testimonials | ☐ |
| `/about` loads | Vision, mission, values, team (Shadman first) | ☐ |
| `/services` loads | 7 service disciplines with Heartbeat Check | ☐ |
| `/work` loads | Project grid with all published projects | ☐ |
| `/work/{slug}` loads | Feature Story with challenge/strategy/outcome | ☐ |
| `/process` loads | 6-step workflow + Heartbeat Check gates | ☐ |
| `/insights` loads | Blog post grid | ☐ |
| `/insights/{slug}` loads | Full blog post with body HTML | ☐ |
| `/contact` loads | Content + HeartbeatCTA + contact form | ☐ |

### 21.3 Narrative-Specific Visual Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Headings render in Playfair Display | Serif font visible | ☐ |
| Body text renders in Manrope | Geometric sans-serif | ☐ |
| Primary color is Pine Teal `#034c3c` | Not Deep Logic from Logic site | ☐ |
| CTA buttons use Vibrant Coral `#ff6f59` | Not System Teal | ☐ |
| Feature Story layout renders correctly | Editorial headline, challenge, strategy, outcome, images | ☐ |
| HeartbeatCTA renders on contact page and project pages | "Book a Heartbeat Review" text visible | ☐ |
| Portfolio grid has editorial feel | Image-dominant cards, not clinical | ☐ |

### 21.4 SEO Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Meta tags present on all pages | title, description, og tags, canonical | ☐ |
| Home has OrganizationSchema for "Zeplow Narrative" | Valid JSON-LD | ☐ |
| Blog posts have ArticleSchema | Valid JSON-LD | ☐ |
| `/sitemap.xml` accessible | Valid XML with pages + projects + blog | ☐ |
| `/robots.txt` accessible | Correct content | ☐ |

### 21.5 Contact Form Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Submit with valid data | Success message, API stores with site_key "narrative" | ☐ |
| Email notification subject includes "narrative" | "New Lead — narrative" | ☐ |
| Honeypot filled | Fake success, nothing stored | ☐ |
| Missing required fields | Validation error shown | ☐ |

### 21.6 Cross-Site Link Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Footer links to zeplow.com work | External link | ☐ |
| Footer links to logic.zeplow.com work | External link | ☐ |
| All internal links work | No 404s | ☐ |
| All project detail links from /work grid work | Each /work/[slug] loads | ☐ |

### 21.7 Pipeline Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Publish new project in CMS | Appears on /work within 2 minutes | ☐ |
| Edit existing page in CMS | Changes reflected within 2 minutes | ☐ |
| Delete a project | Removed from /work after rebuild | ☐ |
| Change navigation in Site Config | Nav updates after rebuild | ☐ |
| CMS/API down | Live site continues working (static CDN) | ☐ |

---

## 22. POST-LAUNCH CHECKLIST

| # | Task | When |
|:---|:---|:---|
| 1 | Verify narrative.zeplow.com loads on mobile + desktop | Day 1 |
| 2 | Submit sitemap to Google Search Console | Day 1 |
| 3 | Verify Cloudflare SSL active | Day 1 |
| 4 | Run Lighthouse audit on all pages | Day 1 |
| 5 | Test contact form end-to-end (submit → email with "narrative" in subject) | Day 1 |
| 6 | Verify all cross-site links (parent, logic) | Day 1 |
| 7 | Verify deploy hook pipeline: CMS → API → rebuild | Day 1 |
| 8 | Set up Cloudflare Pages email notifications for failed builds | Day 1 |
| 9 | Verify all Feature Story pages render with full Narrative Arc data | Day 1 |
| 10 | Verify Playfair Display serif font loads correctly | Day 1 |
| 11 | Check image performance on portfolio pages (heaviest pages) | Day 1 |
| 12 | Monitor build times for first week | Week 1 |
| 13 | Test project creation end-to-end (CMS → API → build → live Feature Story) | Day 2 |

---

*End of Narrative Site PRD.*
