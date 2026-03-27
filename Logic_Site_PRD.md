# ZEPLOW LOGIC SITE (logic.zeplow.com) — PRODUCT REQUIREMENTS DOCUMENT (PRD)

**Version:** 1.0
**Date:** March 27, 2026
**Derived From:** Zeplow Platform Central PRD v1.1, Tech Business Brand Document, Company Profile
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

logic.zeplow.com is the public website for **Zeplow Logic** — the technology, automation, and AI arm of the Zeplow group. It sells a single idea: "We are your Fractional Tech Co-Founder." It positions Zeplow Logic as the strategic partner that replaces operational chaos with systems that run boringly well.

This is not a dev shop portfolio. It is a consulting company's website that prescribes solutions, not a freelancer's gallery that takes orders. The site should make the right client feel relief ("Finally, someone competent") and scare away the wrong client ("If you want a yes-man to blindly code features, we are not the firm").

### 1.2 Properties

| Property | Value |
|:---|:---|
| Domain | `logic.zeplow.com` |
| Framework | Next.js 14+ (App Router, static export) |
| Styling | Tailwind CSS v3 |
| Animation | Framer Motion |
| Output mode | Static export (`output: 'export'`) |
| Hosting | Cloudflare Pages (free tier) |
| Data source | `api.zeplow.com` (fetched at build time) |
| site_key | `logic` |
| Repository | `zeplow-sites` monorepo → `apps/logic/` |

### 1.3 What Makes This Site Unique vs. Parent & Narrative

| Aspect | Logic (logic.zeplow.com) | Parent / Narrative |
|:---|:---|:---|
| Archetype | The Ruler / Systems Architect | Parent: Empire Builder / Narrative: The Lover / Intimist |
| Tone | Calm, absolute, clinical. Prescribes, doesn't suggest. | Parent: foundational confidence / Narrative: provocative storyteller |
| Typography | Monospace headings (JetBrains Mono) — signals "code" | Serif headings (Playfair Display) — signals "editorial" |
| Color primary | Deep Logic `#081f1a` (near-black teal) | Pine Teal `#034c3c` |
| Color accent | System Teal `#00b894` (electric, "active status") | Vibrant Coral `#ff6f59` |
| Case study format | "Incident Report" (technical debrief) | Narrative: "Feature Story" (editorial) |
| Project detail page | IncidentReport component | Narrative: feature story layout |
| CTA language | "Book a Systems Audit" / "Talk to the Architect" | Parent: "Get in Touch" / Narrative: "Book a Heartbeat Review" |
| Visual imagery | Schematics, flowcharts, architecture diagrams. No stock photos. | Narrative: Human-focused, raw photography |
| Success metric shown | "Zero-Touch Transactions", "Hours Saved", "Systems Uptime" | Narrative: Engagement, sentiment, brand love |

### 1.4 Target Audience

From the brand document — the **Ideal Customer Profile**:

- **Who:** Non-technical founders & COOs of high-volume businesses (Real Estate, Logistics, Legal, Health, EdTech, FinTech, Manufacturing)
- **Stage:** "The Breaking Point" — $10k–$50k/month revenue. Product-market fit achieved, but choking on operational drag.
- **Geography:** Global (US, UK, EU, UAE, Bangladesh)
- **Psychographics:** They look successful on LinkedIn but internally are glued together by interns and broken Excel sheets. They suffer from "Imposter Syndrome" about their back office.
- **The Fear:** Dependency — terrified of hiring a "Black Box" developer who holds their business hostage.
- **The Buy:** They buy **relief and control**, not "Python scripts."

### 1.5 Non-Goals

- Selling creative/branding services (that's narrative.zeplow.com)
- Blog/portfolio for the parent holding company (that's zeplow.com)
- Client portal, dashboards, or login areas
- Job listings system
- E-commerce or payment processing
- Hourly billing calculator or public pricing page (pricing is consultation-based)

---

## 2. SITE IDENTITY & BRAND SYSTEM

### 2.1 Brand Position

| Element | Value |
|:---|:---|
| Name | Zeplow Logic |
| Tagline | "Build once. Run forever." |
| Positioning | "The Fractional Tech Co-Founder that replaces manual chaos with AI-native automation." |
| Archetype | The Ruler / Systems Architect |
| Voice | The Candid Architect — speaks in statements, not suggestions. High-contrast language, short sentences, zero fluff. |
| Promise | "Order out of Chaos." |
| One rule | "If it doesn't create leverage, we delete it." |

### 2.2 Color Palette

| Color | Hex | CSS Variable | Usage | Psychology |
|:---|:---|:---|:---|:---|
| Deep Logic | `#081f1a` | `--color-primary` | Primary / Text / Headers | Near-black green-teal. Feels like code/terminal. |
| White Smoke | `#f4f4f4` | `--color-background` | Backgrounds | Shared DNA with Narrative. Premium paper feel. |
| System Teal | `#00b894` | `--color-accent` | Accents / CTAs / Active states | Electric, sharp. Represents "Active Status" or "Go." |
| Error Coral | `#ff7675` | `--color-error` | Alerts / Destructive actions only | Muted digital version of Narrative's coral. Used sparingly. |

### 2.3 Typography

| Role | Font | Weight | Vibe |
|:---|:---|:---|:---|
| Headings (H1–H3) | JetBrains Mono | 700 (Bold) | Monospace. Signals code, engineering, precision. Looks like a blueprint. |
| Body / UI | Inter | 400, 500, 600 | Clean geometric sans. Maximum readability. "Invisible" design. |

**Self-hosted fonts only.** Download font files into `public/fonts/`. No Google Fonts CDN calls. Use `next/font/local`.

### 2.4 Visual Rules (from Brand Document)

| Rule | Detail |
|:---|:---|
| No stock photos of people | Use schematics, flowcharts, and architecture diagrams |
| Style | Line art, high contrast. "Blueprint on paper." |
| Dashboards | Show the data, not the decoration |
| Imagery feel | Technical, precise, clinical |
| Animations | Subtle, functional. No playful bounces — think loading indicators, data transitions |

### 2.5 Tailwind Configuration

```typescript
// apps/logic/tailwind.config.ts
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
        primary: '#081f1a',
        background: '#f4f4f4',
        text: '#081f1a',
        accent: '#00b894',
        error: '#ff7675',
      },
      fontFamily: {
        heading: ['var(--font-jetbrains)', 'monospace'],
        body: ['var(--font-inter)', 'system-ui', 'sans-serif'],
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

logic.zeplow.com has **9 routes** (7 static + 2 dynamic):

| Route | Page | Template | Description |
|:---|:---|:---|:---|
| `/` | Home | `home` | Hero, problem statement, featured projects, service pillars, CTA |
| `/about` | About | `about` | Company story, values, team, "how we think" |
| `/services` | Services | `services` | Full service catalog organized by engagement type |
| `/work` | Portfolio Grid | `work` | All published projects in grid layout |
| `/work/[slug]` | Project Detail | — | Individual project as "Incident Report" (dynamic route) |
| `/process` | Process/Methodology | `process` | The 6-step workflow: Discovery → Strategy → Architecture → Execution → Delivery → Partnership |
| `/insights` | Blog Listing | `insights` | All published blog posts for Logic |
| `/insights/[slug]` | Blog Post Detail | — | Individual blog post (dynamic route) |
| `/contact` | Contact | `contact` | Contact form + "Book a Systems Audit" CTA |

### 3.2 Data Source Per Route

| Route | API Calls at Build Time |
|:---|:---|
| `/` | `getPage('logic', 'home')` + `getProjects('logic', { featured: true, limit: 3 })` + `getTestimonials('logic')` |
| `/about` | `getPage('logic', 'about')` + `getTeamMembers('logic')` |
| `/services` | `getPage('logic', 'services')` |
| `/work` | `getProjects('logic')` |
| `/work/[slug]` | `getProject('logic', slug)` — uses `generateStaticParams` |
| `/process` | `getPage('logic', 'process')` |
| `/insights` | `getBlogPosts('logic')` |
| `/insights/[slug]` | `getBlogPost('logic', slug)` — uses `generateStaticParams` |
| `/contact` | `getPage('logic', 'contact')` |

### 3.3 Navigation Structure

Driven by API config (`getSiteConfig('logic')`). Expected nav items:

| Label | URL | External |
|:---|:---|:---|
| About | /about | No |
| Services | /services | No |
| Work | /work | No |
| Process | /process | No |
| Insights | /insights | No |
| Contact | /contact | No |

CTA button in nav: "Book a Systems Audit" → `/contact`

---

## 4. MONOREPO CONTEXT — WHERE THIS SITE LIVES

### 4.1 Repository Structure

```
zeplow-sites/
├── apps/
│   ├── parent/          ← zeplow.com
│   ├── narrative/       ← narrative.zeplow.com
│   └── logic/           ← THIS SITE (logic.zeplow.com)
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
  "dev:logic": "turbo run dev --filter=logic",
  "build:logic": "turbo run build --filter=logic"
}
```

**Dev server port:** 3002 (parent: 3000, narrative: 3001, logic: 3002).

---

## 5. SHARED PACKAGES

Same as the Parent Site PRD. The Logic site imports from `@zeplow/api`, `@zeplow/ui`, and `@zeplow/config`. All TypeScript interfaces, the API client, and shared components are identical across sites. The `siteKey` parameter (`'logic'`) differentiates which content is fetched.

Key functions used by the Logic site:

| Function | Returns | Used By |
|:---|:---|:---|
| `getSiteConfig('logic')` | `SiteConfig` | Root layout |
| `getPage('logic', slug)` | `Page` | Static pages |
| `getProjects('logic')` | `ProjectListItem[]` | Work grid |
| `getProjects('logic', { featured: true, limit: 3 })` | `ProjectListItem[]` | Home featured |
| `getProject('logic', slug)` | `Project` | Incident Report detail |
| `getBlogPosts('logic')` | `BlogPostListItem[]` | Insights listing |
| `getBlogPost('logic', slug)` | `BlogPost` | Blog post detail |
| `getTeamMembers('logic')` | `TeamMember[]` | About page |
| `getTestimonials('logic')` | `Testimonial[]` | Home page |

---

## 6. NEXT.JS CONFIGURATION

```javascript
// apps/logic/next.config.js

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

```json
// apps/logic/package.json
{
  "name": "logic",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "next dev -p 3002",
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
// apps/logic/app/layout.tsx

import { getSiteConfig } from '@zeplow/api';
import { Navigation, Footer } from '@zeplow/ui';
import localFont from 'next/font/local';
import type { Metadata } from 'next';
import './globals.css';

const SITE_KEY = 'logic';

const jetbrains = localFont({
  src: '../public/fonts/JetBrainsMono-Bold.woff2',
  variable: '--font-jetbrains',
  display: 'swap',
});

const inter = localFont({
  src: [
    { path: '../public/fonts/Inter-Regular.woff2', weight: '400' },
    { path: '../public/fonts/Inter-Medium.woff2', weight: '500' },
    { path: '../public/fonts/Inter-SemiBold.woff2', weight: '600' },
    { path: '../public/fonts/Inter-Bold.woff2', weight: '700' },
  ],
  variable: '--font-inter',
  display: 'swap',
});

export const metadata: Metadata = {
  metadataBase: new URL('https://logic.zeplow.com'),
};

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const config = await getSiteConfig(SITE_KEY);

  return (
    <html lang="en" className={`${jetbrains.variable} ${inter.variable}`}>
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

**File:** `apps/logic/app/page.tsx`
**Purpose:** Convert the right visitor and repel the wrong one. Establish authority instantly.

**Data Sources:**

```typescript
const page = await getPage('logic', 'home');
const featuredProjects = await getProjects('logic', { featured: true, limit: 3 });
const testimonials = await getTestimonials('logic');
```

**Expected Content Blocks (from CMS):**

1. `hero` — Headline: "Stop running a million-dollar vision on a ten-dollar spreadsheet." / CTA: "Book a Systems Audit" → /contact / Background: Deep Logic `#081f1a`
2. `text` — The problem statement: "Most companies think they need more developers. They actually need a proper architecture." (from brand manifesto)
3. `cards` — The 4-Pillar Promise:
   - "We Speak Business, Not Just Code" — The Consultant Approach
   - "From Idea to MVP in Days, Not Months" — The Speed
   - "Future-Proofed with AI" — The Innovation
   - "Invisible Operations" — The Peace of Mind
4. `projects` — Featured projects (count: 3, featured_only: true)
5. `testimonials` — Client testimonials block
6. `cta` — "If your business runs on duct tape and spreadsheets, let's fix that." → /contact

**Additional data rendered outside blocks:**
- Featured projects using `ProjectCard` components
- Testimonials using `TestimonialCard` components
- `OrganizationSchema` JSON-LD

**Implementation:**

```typescript
// apps/logic/app/page.tsx

import { getPage, getProjects, getTestimonials, getSiteConfig } from '@zeplow/api';
import { ContentRenderer, ProjectCard, TestimonialCard, OrganizationSchema } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'home');
  return {
    title: page.seo.title,
    description: page.seo.description,
    openGraph: {
      title: page.seo.title,
      description: page.seo.description,
      images: page.seo.og_image ? [page.seo.og_image] : [],
      url: 'https://logic.zeplow.com',
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
        name="Zeplow Logic"
        url="https://logic.zeplow.com"
        description="Build once. Run forever. Technology, automation & AI systems."
        logo="https://logic.zeplow.com/logo.png"
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

**File:** `apps/logic/app/about/page.tsx`
**Purpose:** Establish who we are, how we think, and why we push back.

**Data Sources:**

```typescript
const page = await getPage('logic', 'about');
const team = await getTeamMembers('logic');
```

**Expected Content Blocks:**

1. `hero` — "About Zeplow Logic"
2. `text` — Purpose: "To bridge the gap between business vision and technical execution, ensuring no great company fails due to Technical Paralysis."
3. `text` — Vision: "A world of Lean Giants — companies generating enterprise revenue with small teams because they run on Self-Driving operations."
4. `text` — Mission: "To replace manual grunt work with scalable clarity, functioning as the permanent, fractional engineering partner for growth-focused founders."
5. `stats` — Key metrics: Projects delivered, hours automated, systems uptime, countries served
6. `text` — Values section with the 3 core values:
   - **Stewardship Over Revenue** — "We protect your budget like it's ours. If a feature doesn't create leverage, we'll say no."
   - **Systems Over Heroes** — "We don't rely on late-night hacks. We build processes that work even when no one's watching."
   - **Radical Transparency** — "Bad news travels fast here. You'll always know where things stand."
7. `team` — Team display (use_all: true)

---

### 8.3 Services Page (`/services`)

**File:** `apps/logic/app/services/page.tsx`
**Purpose:** Full service catalog organized by engagement type — not a feature list, but a business outcome map.

**Data Source:**

```typescript
const page = await getPage('logic', 'services');
```

**Expected Content Blocks:**

1. `hero` — "What We Build" / Sub: "We don't sell code. We sell operational freedom."
2. `text` — Intro: "Engagements start at $3,000. We do not offer hourly billing." (from brand doc — filters wrong clients instantly)
3. `cards` — **Engagement Types** (from brand doc):
   - **The Discovery Audit** — "We analyze your current manual processes and map out an Automation & Tech Roadmap. You see where you're losing money before we touch a line of code." ($500–$1,000)
   - **The MVP Sprint** — "We build the V1 of your AI-integrated platform in 4 weeks. Fixed scope, fixed price, real users at the end." ($3k–$10k)
   - **The Co-Founder Retainer** — "We manage the app, fix bugs instantly, and release new AI features every month. Your entire tech department for a flat monthly fee." ($1,500–$3,000/mo)
4. `cards` — **Service Categories** (from company profile):
   - Workflow Audits & Process Design
   - Custom Dashboards & Admin Panels
   - ERP / CRM Systems
   - API Integrations & Data Pipelines
   - AI-Native Automation
   - MVP Development
   - Fractional CTO Services
   - Ongoing Monitoring & Optimization
5. `text` — "The Leverage Check" — 3 gates every feature must pass:
   - The ROI Gate: Does this make money or save time?
   - The Scale Gate: Will this break if the client grows 10x?
   - The Complexity Gate: Can we build this simpler?
6. `cta` — "Start with a Systems Audit" → /contact

---

### 8.4 Work — Portfolio Grid (`/work`)

**File:** `apps/logic/app/work/page.tsx`
**Purpose:** All published Logic projects in a grid.

**Data Source:**

```typescript
const projects = await getProjects('logic');
```

**Renders:** Grid of `ProjectCard` components. Each card shows: title, one_liner, industry, featured badge, first image. Links to `/work/{slug}`.

**Implementation:**

```typescript
// apps/logic/app/work/page.tsx

import { getProjects } from '@zeplow/api';
import { ProjectCard } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';

export const metadata: Metadata = {
  title: 'Our Work — Zeplow Logic',
  description: 'Systems, automation, and AI projects that replaced chaos with clarity.',
};

export default async function WorkPage() {
  const projects = await getProjects(SITE_KEY);

  return (
    <main>
      <section>
        <h1>Our Work</h1>
        <p>Every project below solved a real business problem. No vanity features.</p>
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

### 8.5 Work — Project Detail / Incident Report (`/work/[slug]`)

**File:** `apps/logic/app/work/[slug]/page.tsx`
**Purpose:** Individual project presented as a technical debrief — the **"Incident Report"** format.

This is the most unique component on the Logic site. Unlike Narrative's editorial "Feature Story" format, Logic presents projects like a clinical report: Subject → Bottleneck → Solution → Outcome.

**Data Sources:**

```typescript
const projects = await getProjects('logic');       // for generateStaticParams
const project = await getProject('logic', slug);   // full detail
```

**Logic-Specific Component: `IncidentReport.tsx`**

Located at `apps/logic/components/IncidentReport.tsx`. Renders a project in the "Incident Report" format:

```
┌─────────────────────────────────────────┐
│  INCIDENT REPORT                         │
│  Subject: [project.title]                │
│  Client: [project.client_name]           │
│  Industry: [project.industry]            │
├─────────────────────────────────────────┤
│  BOTTLENECK                              │
│  [project.challenge]                     │
├─────────────────────────────────────────┤
│  SOLUTION                                │
│  [project.solution]                      │
│                                          │
│  TECH STACK                              │
│  [project.tech_stack as pills/tags]      │
├─────────────────────────────────────────┤
│  OUTCOME                                 │
│  [project.outcome]                       │
├─────────────────────────────────────────┤
│  EVIDENCE                                │
│  [project.images as screenshot grid]     │
├─────────────────────────────────────────┤
│  [project.url — "View Live System"]      │
└─────────────────────────────────────────┘
```

**Implementation:**

```typescript
// apps/logic/app/work/[slug]/page.tsx

import { getProjects, getProject } from '@zeplow/api';
import { IncidentReport } from '../../../components/IncidentReport';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';

export async function generateStaticParams() {
  const projects = await getProjects(SITE_KEY);
  return projects.map((project) => ({ slug: project.slug }));
}

export async function generateMetadata({ params }: { params: { slug: string } }): Promise<Metadata> {
  const project = await getProject(SITE_KEY, params.slug);
  return {
    title: `${project.title} — Zeplow Logic`,
    description: project.one_liner,
  };
}

export default async function ProjectPage({ params }: { params: { slug: string } }) {
  const project = await getProject(SITE_KEY, params.slug);

  return (
    <main>
      <IncidentReport project={project} />
    </main>
  );
}
```

---

### 8.6 Process Page (`/process`)

**File:** `apps/logic/app/process/page.tsx`
**Purpose:** The 6-step workflow that shows how Logic operates. Builds trust by demonstrating rigor.

**Data Source:**

```typescript
const page = await getPage('logic', 'process');
```

**Expected Content Blocks (from company profile workflow):**

1. `hero` — "Our Process" / "We measure twice so we only cut once."
2. `text` — Intro: "Every engagement follows the same 6-step framework. No shortcuts. No guessing."
3. `cards` or `stats` — The 6 steps:
   - **01 · Discovery** — "We start by listening. What's broken? What keeps you up at night? We map your current state before proposing anything."
   - **02 · Strategy** — "We don't guess. We diagnose the root cause, define the Definition of Done, and build a roadmap with clear milestones."
   - **03 · Architecture** — "The blueprint phase. Data flow, system design, tech stack decisions. We measure twice so we only cut once."
   - **04 · Execution** — "We build. You get updates, not meetings. Focused sprints — shipping real output every week."
   - **05 · Delivery** — "Deployed, documented, and stable. Tested and ready to perform."
   - **06 · Partnership** — "Launch isn't the end. We monitor, optimize, and grow your systems monthly. We resell our value every 30 days."
4. `cta` — "Ready to start with Discovery?" → /contact

---

### 8.7 Insights — Blog Listing (`/insights`)

**File:** `apps/logic/app/insights/page.tsx`

**Data Source:**

```typescript
const posts = await getBlogPosts('logic');
```

**Renders:** Grid of `BlogCard` components. Each card: title, excerpt, cover image, tags, author, published date. Links to `/insights/{slug}`.

```typescript
export const metadata: Metadata = {
  title: 'Insights — Zeplow Logic',
  description: 'Technical thinking on automation, AI systems, and building operations that scale.',
};
```

---

### 8.8 Insights — Blog Post Detail (`/insights/[slug]`)

**File:** `apps/logic/app/insights/[slug]/page.tsx`

Same pattern as Parent Site — `generateStaticParams` to enumerate slugs, `getBlogPost('logic', slug)` for full content, `ArticleSchema` JSON-LD. Body HTML rendered via `dangerouslySetInnerHTML`.

---

### 8.9 Contact Page (`/contact`)

**File:** `apps/logic/app/contact/page.tsx`
**Purpose:** Contact form + positioning CTA.

**Data Source:**

```typescript
const page = await getPage('logic', 'contact');
```

**Expected Content Blocks:**

1. `hero` — "Let's Talk Architecture" / "Book a Systems Audit — we'll map your current state and show you where the leverage is."
2. `text` — Contact info: hello@zeplow.com, social links
3. The `ContactForm` client component with `siteKey="logic"` and `siteDomain="logic.zeplow.com"`

**Logic-Specific CTA Component: `AuditCTA.tsx`**

Located at `apps/logic/components/AuditCTA.tsx`. A styled CTA block specific to Logic's "Systems Audit" language. Can be used on the contact page and elsewhere:

```
"Your first step is a Systems Audit.
We diagnose the bottleneck, map the architecture, and show you the ROI — before a single line of code is written.
Engagements start at $3,000. If that filters you out, this isn't the right fit."
```

---

## 9. CONTENT BLOCK RENDERING

Same `ContentRenderer` component as all sites. Maps block `type` to React components. The visual design of block components will differ from Narrative/Parent to reflect Logic's monospace typography, darker palette, and technical aesthetic — but the data structure is identical.

**Logic-specific rendering considerations:**

| Block | Logic Visual Treatment |
|:---|:---|
| `hero` | Dark background (`#081f1a`), monospace heading, teal accent CTA button |
| `text` | Clean Inter body text on light background, generous whitespace |
| `cards` | Border-based cards (not filled), slight monospace labels |
| `cta` | Teal button on dark background, direct language ("Book a Systems Audit") |
| `stats` | Monospace numbers with teal color, minimal animation (counter roll-up, nothing playful) |
| `testimonials` | Clean, no decorative quotes. Feels like a system status report. |
| `projects` | Grid cards with industry tag, tech stack pills visible |

---

## 10. CONTACT FORM

Same `ContactForm` component from `@zeplow/ui` used by all three sites. The only difference is the props:

```typescript
<ContactForm siteKey="logic" siteDomain="logic.zeplow.com" />
```

This POSTs to `api.zeplow.com/sites/v1/logic/contact`. The email notification sent to hello@zeplow.com will include `site_key: logic` in the subject line so the team knows which arm the lead came from.

Budget range options are the same across all sites: Under $3,000 / $3,000–$5,000 / $5,000–$10,000 / $10,000+.

---

## 11. SEO STRATEGY

### 11.1 Meta Tags

Same strategy as parent site. Every page gets `<title>`, `<meta name="description">`, Open Graph tags, and canonical URL from the `page.seo` data returned by the API.

### 11.2 Sitemap

```typescript
// apps/logic/app/sitemap.ts

import { getPages, getBlogPosts, getProjects } from '@zeplow/api';
import type { MetadataRoute } from 'next';

const BASE_URL = 'https://logic.zeplow.com';
const SITE_KEY = 'logic';

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
Sitemap: https://logic.zeplow.com/sitemap.xml
```

### 11.4 Structured Data

| Page | Schema | Component |
|:---|:---|:---|
| Home | Organization | `OrganizationSchema` — Zeplow Logic |
| Blog posts | Article | `ArticleSchema` |

---

## 12. PERFORMANCE REQUIREMENTS

Same targets as parent site. Lighthouse 95+, TTFB < 50ms, LCP < 1.2s, page weight < 200 KB (excl. images). Self-hosted fonts, minimal JS, Tailwind purge, no third-party scripts.

---

## 13. CLOUDFLARE PAGES — BUILD & DEPLOY

### 13.1 Project Configuration

| Setting | Value |
|:---|:---|
| Project name | `zeplow-logic` |
| Production branch | `main` |
| Build command | `cd apps/logic && npx next build` |
| Build output directory | `apps/logic/out` |
| Root directory | `/` |
| Node.js version | `18` |
| Custom domain | `logic.zeplow.com` |

### 13.2 Environment Variables (Cloudflare Pages)

| Variable | Value |
|:---|:---|
| `NEXT_PUBLIC_API_URL` | `https://api.zeplow.com` |
| `NEXT_PUBLIC_SITE_KEY` | `logic` |
| `NODE_VERSION` | `18` |

### 13.3 Build Process

Same as parent — Cloudflare clones monorepo, installs deps, runs `next build` for the logic app, which fetches data from API at build time, generates static HTML, deploys to CDN. Expected build time: 60–120 seconds.

### 13.4 Deploy Triggers

| Trigger | How |
|:---|:---|
| Content change | CMS publish → API sync → API fires `CF_DEPLOY_HOOK_LOGIC` → Cloudflare rebuilds |
| Code change | Push to `main` → Cloudflare auto-builds |
| Manual | Cloudflare Pages dashboard |

---

## 14. DNS & DOMAIN CONFIGURATION

| Type | Name | Content | Proxy |
|:---|:---|:---|:---|
| CNAME | `logic` | `zeplow-logic.pages.dev` | Yes |

SSL: Automatic via Cloudflare — Universal SSL, free.

---

## 15. SECURITY HEADERS

**File:** `apps/logic/public/_headers`

```
/*
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  Permissions-Policy: camera=(), microphone=(), geolocation=()
```

---

## 16. CONTENT SEEDING — WHAT TO PUBLISH IN CMS

All content below is created in the CMS as records with `site_key: logic`.

### 16.1 Pages to Create

| slug | title | template | Content Source |
|:---|:---|:---|:---|
| `home` | Home | `home` | Hero + problem statement + 4-pillar promise + featured projects + testimonials + CTA |
| `about` | About | `about` | Purpose/vision/mission from tech brand doc + values + team |
| `services` | Services | `services` | 3 engagement types + 8 service categories + leverage check |
| `work` | Our Work | `work` | Intro text (grid populated by projects API) |
| `process` | Our Process | `process` | 6-step workflow from company profile |
| `contact` | Contact | `contact` | Hero + audit CTA text |
| `insights` | Insights | `insights` | Intro text (listing populated by blog API) |

### 16.2 Team Members to Create

| name | role | bio | is_founder | sort_order |
|:---|:---|:---|:---|:---|
| Shakib Bin Kabir | Co-Founder & CTO | Systems, automation, AI & technical architecture. Leads technology, product development, and infrastructure decisions across the Zeplow group. Turns business problems into technical solutions that scale. | true | 0 |
| Shadman Sakib | Co-Founder & CEO | Strategy, direction, and brand & venture leadership. Leads strategy, client relationships, and brand direction across the Zeplow group. | true | 1 |

**Note:** On Logic, Shakib is listed first (sort_order 0) because he is the delivery owner for Logic. On Parent/Narrative, Shadman is first.

### 16.3 Projects to Create (All 17 from company profile)

The Logic site shows all tech projects. The 6 from the company profile, plus additional projects. Here are the 6 primary ones with full detail:

| title | slug | one_liner | industry | client_name | featured | tech_stack |
|:---|:---|:---|:---|:---|:---|:---|
| Tututor.ai | tututor-ai | AI-powered tutoring platform that personalizes learning and accelerates student success | EdTech | Tututor | true | Next.js, Python, PostgreSQL, OpenAI |
| CAPEC AI | capec-ai | Platform connecting emerging market businesses with global non-dilutive funding | FinTech | CAPEC | true | Next.js, Python, AI Matching |
| Aditio ERP | aditio-erp | Custom all-in-one ERP — project management, invoicing, team allocation in a single dashboard | SaaS | Aditio Agency | true | Laravel, Vue.js, MySQL |
| RAT'S Vault | rats-vault | Secure digital platform for managing and protecting critical business data | Enterprise | RAT'S BD | false | Laravel, React, MySQL |
| ATME Cards | atme-cards | Modern digital business card platform — share identity, links, and presence instantly | SaaS | ATME | false | Next.js, Node.js, MongoDB |
| CentrePoint Shop | centrepoint-shop | Full e-commerce store for a Canadian postal service | E-Commerce | CentrePoint Postal | false | Shopify, Custom Theme |

Each project must include `challenge`, `solution`, `outcome`, and `images` for the Incident Report detail page to render fully.

### 16.4 Site Config to Create

| Field | Value |
|:---|:---|
| nav_items | About (/about), Services (/services), Work (/work), Process (/process), Insights (/insights), Contact (/contact) |
| footer_text | © 2026 Zeplow LTD. All rights reserved. |
| cta_text | Book a Systems Audit |
| cta_url | /contact |
| social_links | linkedin, instagram, whatsapp URLs |
| contact_email | hello@zeplow.com |
| footer_links | Group: "The Zeplow Group" → Zeplow (https://zeplow.com), Zeplow Narrative (https://narrative.zeplow.com), Zeplow Logic (https://logic.zeplow.com); Group: "Company" → About (/about), Services (/services), Work (/work), Process (/process), Insights (/insights), Contact (/contact) |

### 16.5 Testimonials to Create

Seed at least 2-3 testimonials. The ideal testimonial for Logic (from brand doc): "Our business finally feels under control." — speaks to the anxiety of the founder and confirms the "Order out of Chaos" promise.

### 16.6 Content Tone for Logic Site

The Logic site speaks as **the Systems Architect** — calm, absolute, clinical.

**Do:**
- Prescribe. "We recommend X because of Y."
- Focus on leverage. "This saves you 10 hours."
- Be absolute. "This is the correct architecture."
- Celebrate boring. "The system is stable."
- Push back. "That feature wastes budget. Delete it."

**Don't:**
- Ask for permission. "Is this okay?"
- Focus on specs. "This uses React v18."
- Be vague. "It depends..."
- Celebrate flash. "Look at this cool animation!"
- Submit. "Yes sir, we will build whatever you want."

**Key phrases from the brand documents:**
- "Stop running a million-dollar vision on a ten-dollar spreadsheet."
- "If it doesn't create leverage, we delete it."
- "Order out of Chaos."
- "Build once. Run forever."
- "We don't build software to satisfy a spec sheet; we build it to solve a business problem."
- "We are not an agency. We are your Tech Co-Founder."
- "Engagements start at $3,000. We do not offer hourly billing."
- "Automation isn't about speed; it's about predictability."

---

## 17. ENVIRONMENT VARIABLES

### 17.1 Local Development (.env.local)

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_SITE_KEY=logic
```

### 17.2 Production (Cloudflare Pages)

| Variable | Value |
|:---|:---|
| `NEXT_PUBLIC_API_URL` | `https://api.zeplow.com` |
| `NEXT_PUBLIC_SITE_KEY` | `logic` |
| `NODE_VERSION` | `18` |

---

## 18. DIRECTORY STRUCTURE

```
apps/logic/
├── app/
│   ├── globals.css
│   ├── layout.tsx                     # Root layout (JetBrains Mono + Inter, nav, footer)
│   ├── page.tsx                       # Home (/)
│   ├── about/
│   │   └── page.tsx                   # About (/about)
│   ├── services/
│   │   └── page.tsx                   # Services (/services)
│   ├── work/
│   │   ├── page.tsx                   # Portfolio grid (/work)
│   │   └── [slug]/
│   │       └── page.tsx               # Incident Report (/work/[slug])
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
├── components/                        # Logic-site-specific components
│   ├── IncidentReport.tsx             # Project detail in technical debrief format
│   └── AuditCTA.tsx                   # "Book a Systems Audit" styled CTA block
├── public/
│   ├── fonts/
│   │   ├── JetBrainsMono-Bold.woff2
│   │   ├── Inter-Regular.woff2
│   │   ├── Inter-Medium.woff2
│   │   ├── Inter-SemiBold.woff2
│   │   └── Inter-Bold.woff2
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

Same as parent site. Build-time failures keep the previous deploy live. Runtime errors in the contact form show friendly fallback messages. Broken image URLs show alt text. Site is fully functional without JavaScript except the contact form.

---

## 20. IMPLEMENTATION ORDER

### Phase 1: App Scaffolding (Day 1)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 1.1 | Create `apps/logic` in monorepo | Next.js with App Router, TypeScript, Tailwind | Monorepo infra |
| 1.2 | Configure `next.config.js` | `output: 'export'`, `transpilePackages` | 1.1 |
| 1.3 | Configure `tailwind.config.ts` | Logic colors (`#081f1a`, `#00b894`), monospace heading font | 1.1 |
| 1.4 | Download and self-host fonts | JetBrains Mono Bold + Inter 400/500/600/700 | 1.1 |
| 1.5 | Create `globals.css` | Tailwind directives, base styles | 1.3 |
| 1.6 | Verify `pnpm dev:logic` runs on port 3002 | Dev server working | 1.1–1.5 |

### Phase 2: Layout & Core Components (Day 2)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 2.1 | Create root layout | JetBrains Mono + Inter fonts, `getSiteConfig('logic')`, Navigation + Footer | 1.6, shared packages |
| 2.2 | Create `IncidentReport.tsx` component | Logic-specific project detail layout (technical debrief format) | 1.1 |
| 2.3 | Create `AuditCTA.tsx` component | "Book a Systems Audit" styled CTA | 1.1 |
| 2.4 | Create `public/_headers` and `public/robots.txt` | Security headers, robots | 1.1 |

### Phase 3: Page Data Layer (Days 3–4)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 3.1 | Wire up Home page | `getPage` + `getProjects` + `getTestimonials` + `OrganizationSchema` | 2.1 |
| 3.2 | Wire up About page | `getPage` + `getTeamMembers` | 2.1 |
| 3.3 | Wire up Services page | `getPage` with engagement types and service categories | 2.1 |
| 3.4 | Wire up Work grid page | `getProjects('logic')` + `ProjectCard` grid | 2.1 |
| 3.5 | Wire up Work/[slug] detail | `getProject` + `generateStaticParams` + `IncidentReport` | 2.1, 2.2 |
| 3.6 | Wire up Process page | `getPage` with 6-step workflow | 2.1 |
| 3.7 | Wire up Insights listing | `getBlogPosts` + `BlogCard` | 2.1 |
| 3.8 | Wire up Insights/[slug] detail | `getBlogPost` + `generateStaticParams` + `ArticleSchema` | 2.1 |
| 3.9 | Wire up Contact page | `getPage` + `ContactForm` + `AuditCTA` | 2.1, 2.3 |
| 3.10 | Create sitemap.ts | Pages + projects + blog posts | 3.1–3.9 |

### Phase 4: Build Verification (Day 5)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 4.1 | Create `generateStaticParams` for work and blog | Enumerate all slugs | 3.5, 3.8 |
| 4.2 | Run `pnpm build:logic` locally | Verify static export succeeds | 3.1–3.10 |
| 4.3 | Inspect generated HTML | Check meta tags, JSON-LD, Incident Report rendering | 4.2 |

### Phase 5: Cloudflare Deployment (Day 5–6)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 5.1 | Create Cloudflare Pages project `zeplow-logic` | Connect to GitHub | Monorepo on GitHub |
| 5.2 | Configure build settings and env vars | Build command, output dir, API URL, site key | 5.1 |
| 5.3 | Add custom domain `logic.zeplow.com` | CNAME record | DNS configured |
| 5.4 | Push to `main` and verify first deploy | Site loads | 5.1–5.3 |
| 5.5 | Get deploy hook URL and add to API `.env` | `CF_DEPLOY_HOOK_LOGIC` | 5.1, API deployed |

### Phase 6: Content Seeding (Days 6–7)

| # | Task | Details | Depends On |
|:---|:---|:---|:---|
| 6.1 | Seed all 7 Logic pages in CMS | home, about, services, work, process, insights, contact | CMS deployed |
| 6.2 | Seed 2 team members (Shakib first) | Shakib (CTO, sort 0) + Shadman (CEO, sort 1) | CMS deployed |
| 6.3 | Seed all Logic projects (17 total, 3 featured) | Full detail with challenge/solution/outcome/images | CMS deployed |
| 6.4 | Seed Logic site config | Nav, footer, CTA "Book a Systems Audit", socials | CMS deployed |
| 6.5 | Seed 2-3 testimonials | From brand doc tone | CMS deployed |
| 6.6 | Trigger resync from CMS | Resync All → logic | 6.1–6.5 |
| 6.7 | Verify site shows content | Visit logic.zeplow.com, check all pages | 6.6 |

### Phase 7: Design & Polish (Days 8+)

| # | Task | Details |
|:---|:---|:---|
| 7.1 | Design and implement all block components with Logic aesthetic | Monospace headings, dark palette, teal accents, no stock photos |
| 7.2 | Design and implement IncidentReport component visual | Technical debrief layout, clean data presentation |
| 7.3 | Design and implement Navigation (Logic brand) | Desktop + mobile |
| 7.4 | Design and implement Footer (Logic brand) | Links, socials, copyright |
| 7.5 | Add Framer Motion animations | Subtle, functional — data transitions, not playful bounces |
| 7.6 | Lighthouse audit | Target 95+ |
| 7.7 | Cross-browser testing | Chrome, Firefox, Safari, mobile |
| 7.8 | Final QA | All links, forms, images, Incident Reports |

---

## 21. TESTING CHECKLIST

### 21.1 Build Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| `pnpm build:logic` succeeds | Static files in `apps/logic/out/` | ☐ |
| All 9 routes generate HTML files | Check `out/` directory | ☐ |
| Project dynamic routes generate for all published projects | One HTML per project slug | ☐ |
| Blog dynamic routes generate for all published posts | One HTML per blog slug | ☐ |

### 21.2 Page Load Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| `logic.zeplow.com` loads | Home with hero, 4 pillars, featured projects | ☐ |
| `/about` loads | Vision, mission, values, team (Shakib first) | ☐ |
| `/services` loads | 3 engagement types + 8 service categories | ☐ |
| `/work` loads | Project grid with all published projects | ☐ |
| `/work/{slug}` loads | Incident Report with challenge/solution/outcome | ☐ |
| `/process` loads | 6-step workflow | ☐ |
| `/insights` loads | Blog post grid | ☐ |
| `/insights/{slug}` loads | Full blog post with body HTML | ☐ |
| `/contact` loads | Content + contact form + AuditCTA | ☐ |

### 21.3 Logic-Specific Visual Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Headings render in JetBrains Mono | Monospace font visible | ☐ |
| Body text renders in Inter | Clean sans-serif | ☐ |
| Primary color is Deep Logic `#081f1a` | Not Pine Teal from Narrative | ☐ |
| CTA buttons use System Teal `#00b894` | Not Vibrant Coral | ☐ |
| Incident Report layout renders correctly | Subject → Bottleneck → Solution → Outcome → Evidence | ☐ |
| Tech stack pills visible on project cards and detail | Tags render as inline pills | ☐ |

### 21.4 SEO Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Meta tags present on all pages | title, description, og tags, canonical | ☐ |
| Home has OrganizationSchema for "Zeplow Logic" | Valid JSON-LD | ☐ |
| Blog posts have ArticleSchema | Valid JSON-LD | ☐ |
| `/sitemap.xml` accessible | Valid XML with pages + projects + blog | ☐ |
| `/robots.txt` accessible | Correct content | ☐ |

### 21.5 Contact Form Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Submit with valid data | Success message, API stores with site_key "logic" | ☐ |
| Email notification subject includes "logic" | "New Lead — logic" | ☐ |
| Honeypot filled | Fake success, nothing stored | ☐ |
| Missing required fields | Validation error shown | ☐ |

### 21.6 Cross-Site Link Tests

| Test | Expected Result | Pass |
|:---|:---|:---|
| Footer links to zeplow.com work | External link | ☐ |
| Footer links to narrative.zeplow.com work | External link | ☐ |
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
| 1 | Verify logic.zeplow.com loads on mobile + desktop | Day 1 |
| 2 | Submit sitemap to Google Search Console | Day 1 |
| 3 | Verify Cloudflare SSL active | Day 1 |
| 4 | Run Lighthouse audit on all pages | Day 1 |
| 5 | Test contact form end-to-end (submit → email with "logic" in subject) | Day 1 |
| 6 | Verify all cross-site links (parent, narrative) | Day 1 |
| 7 | Verify deploy hook pipeline: CMS → API → rebuild | Day 1 |
| 8 | Set up Cloudflare Pages email notifications for failed builds | Day 1 |
| 9 | Verify all Incident Report pages render with full data | Day 1 |
| 10 | Verify monospace font (JetBrains Mono) loads correctly | Day 1 |
| 11 | Monitor build times for first week | Week 1 |
| 12 | Test project creation end-to-end (CMS → API → build → live Incident Report) | Day 2 |

---

*End of Logic Site PRD.*
