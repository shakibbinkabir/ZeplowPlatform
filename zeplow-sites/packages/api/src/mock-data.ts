import type {
  SiteConfig,
  PageListItem,
  Page,
  ProjectListItem,
  BlogPostListItem,
  TeamMember,
  ContentBlock,
} from './types';

// ─── Parent Site Config ─────────────────────────────────────────────────────

const parentConfig: SiteConfig = {
  site_key: 'parent',
  site_name: 'Zeplow',
  domain: 'zeplow.com',
  tagline: 'Story. Systems. Ventures.',
  nav_items: [
    { label: 'About', url: '/about', is_external: false },
    { label: 'Ventures', url: '/ventures', is_external: false },
    { label: 'Insights', url: '/insights', is_external: false },
    { label: 'Careers', url: '/careers', is_external: false },
    { label: 'Contact', url: '/contact', is_external: false },
  ],
  footer_links: [
    {
      group_title: 'Ventures',
      links: [
        { label: 'Zeplow Narrative', url: 'https://narrative.zeplow.com' },
        { label: 'Zeplow Logic', url: 'https://logic.zeplow.com' },
      ],
    },
    {
      group_title: 'Company',
      links: [
        { label: 'About', url: '/about' },
        { label: 'Insights', url: '/insights' },
        { label: 'Careers', url: '/careers' },
        { label: 'Contact', url: '/contact' },
      ],
    },
  ],
  footer_text: '\u00a9 2026 Zeplow LLC. All rights reserved.',
  cta_text: 'Get in Touch',
  cta_url: '/contact',
  social_links: {
    linkedin: 'https://linkedin.com/company/zeplow',
    instagram: 'https://instagram.com/zeplow',
  },
  contact_email: 'hello@zeplow.com',
};

// ─── Parent Pages ───────────────────────────────────────────────────────────

function seo(title: string, description: string) {
  return { title, description, og_image: null };
}

const parentPages: Record<string, Page> = {
  home: {
    id: 1,
    slug: 'home',
    title: 'Home',
    template: 'home',
    seo: seo(
      'Zeplow — Story. Systems. Ventures.',
      'The company behind companies. Zeplow builds and operates ventures in brand storytelling and technology.'
    ),
    published_at: '2026-03-27T00:00:00Z',
    content: [
      {
        type: 'hero',
        data: {
          heading: 'Story. Systems. Ventures.',
          subheading:
            'We build and operate ventures that help businesses become household names.',
          button_text: 'Get in Touch',
          button_url: '/contact',
        },
      },
      {
        type: 'text',
        data: {
          heading: 'The company behind companies.',
          body: '<p>At Zeplow, we believe that lasting impact comes from two forces working together — the art of narrative and the discipline of logic. Through our two venture arms, we help businesses build brands that resonate and systems that scale.</p>',
        },
      },
      {
        type: 'cards',
        data: {
          heading: 'Our Ventures',
          cards: [
            {
              title: 'Zeplow Narrative',
              description:
                'Brand storytelling, identity systems, and content that turns businesses into stories worth following.',
              url: '/ventures/narrative',
            },
            {
              title: 'Zeplow Logic',
              description:
                'Technology, automation, and AI systems that replace chaos with systems that run boringly well.',
              url: '/ventures/logic',
            },
          ],
        },
      },
      {
        type: 'cta',
        data: {
          heading: 'If this feels like your kind of thinking, we should talk.',
          button_text: 'Get in Touch',
          button_url: '/contact',
        },
      },
    ] as ContentBlock[],
  },
  about: {
    id: 2,
    slug: 'about',
    title: 'About',
    template: 'about',
    seo: seo(
      'About — Zeplow',
      'Learn about Zeplow, our vision, mission, values, and the team behind the ventures.'
    ),
    published_at: '2026-03-27T00:00:00Z',
    content: [
      {
        type: 'hero',
        data: { heading: 'About Zeplow' },
      },
      {
        type: 'text',
        data: {
          heading: 'Our Vision',
          body: '<p>To build an ecosystem where businesses don\'t just survive — they become household names through the combined power of compelling narrative and resilient systems.</p>',
        },
      },
      {
        type: 'text',
        data: {
          heading: 'Our Mission',
          body: '<p>To help businesses unlock their full potential through two disciplines: Narrative — the art of brand storytelling, and Logic — the science of scalable systems.</p>',
        },
      },
      {
        type: 'stats',
        data: {
          stats: [
            { label: 'Projects Delivered', value: '50+' },
            { label: 'Countries Served', value: '12' },
            { label: 'Venture Arms', value: '2' },
            { label: 'Founded', value: '2024' },
          ],
        },
      },
    ] as ContentBlock[],
  },
  ventures: {
    id: 3,
    slug: 'ventures',
    title: 'Our Ventures',
    template: 'ventures',
    seo: seo(
      'Our Ventures — Zeplow',
      'Zeplow operates through two specialized arms — Narrative for brand storytelling and Logic for technology systems.'
    ),
    published_at: '2026-03-27T00:00:00Z',
    content: [
      {
        type: 'hero',
        data: { heading: 'Our Ventures' },
      },
      {
        type: 'text',
        data: {
          body: '<p>Zeplow operates through two specialized arms — each with its own expertise, but united by a single standard of quality.</p>',
        },
      },
      {
        type: 'cards',
        data: {
          cards: [
            {
              title: 'Zeplow Narrative',
              description:
                'Brand Storytelling, Identity & Content Systems. We help brands stop being invisible.',
              url: '/ventures/narrative',
            },
            {
              title: 'Zeplow Logic',
              description:
                'Technology, Automation & AI Systems. We replace spreadsheets and manual processes with systems that run boringly well.',
              url: '/ventures/logic',
            },
          ],
        },
      },
      {
        type: 'cta',
        data: {
          heading: 'Ready to work with us?',
          button_text: 'Get in Touch',
          button_url: '/contact',
        },
      },
    ] as ContentBlock[],
  },
  'ventures-narrative': {
    id: 4,
    slug: 'ventures-narrative',
    title: 'Zeplow Narrative',
    template: 'ventures',
    seo: seo(
      'Zeplow Narrative — Stories that sell.',
      'Through Zeplow Narrative, we help brands stop being invisible with strategy, visual identity, content production, and ongoing management.'
    ),
    published_at: '2026-03-27T00:00:00Z',
    content: [
      {
        type: 'hero',
        data: {
          heading: 'Zeplow Narrative',
          subheading: 'Stories that sell.',
        },
      },
      {
        type: 'text',
        data: {
          body: '<p>We help brands stop being invisible. Through strategy, visual identity, content production, and ongoing management — we turn businesses into stories worth following.</p>',
        },
      },
      {
        type: 'cards',
        data: {
          heading: 'What Narrative Does',
          cards: [
            { title: 'Brand Strategy & Positioning', description: 'Define who you are and why it matters.' },
            { title: 'Visual Identity Systems', description: 'Logos, design systems, and brand guidelines that hold.' },
            { title: 'Video & Photo Production', description: 'Content that captures attention and tells your story.' },
            { title: 'Content Direction & Calendars', description: 'Strategic content planning that drives results.' },
            { title: 'Social Media Management', description: 'Consistent, on-brand presence across platforms.' },
            { title: 'Campaign Creative', description: 'Creative campaigns that move people to action.' },
          ],
        },
      },
      {
        type: 'cta',
        data: {
          heading: 'Visit Zeplow Narrative',
          button_text: 'Get in Touch',
          button_url: '/contact',
        },
      },
    ] as ContentBlock[],
  },
  'ventures-logic': {
    id: 5,
    slug: 'ventures-logic',
    title: 'Zeplow Logic',
    template: 'ventures',
    seo: seo(
      'Zeplow Logic — Build once. Run forever.',
      'Through Zeplow Logic, we replace spreadsheets, manual processes, and operational chaos with systems that run boringly well.'
    ),
    published_at: '2026-03-27T00:00:00Z',
    content: [
      {
        type: 'hero',
        data: {
          heading: 'Zeplow Logic',
          subheading: 'Build once. Run forever.',
        },
      },
      {
        type: 'text',
        data: {
          body: '<p>We replace spreadsheets, manual processes, and operational chaos with systems that run boringly well.</p>',
        },
      },
      {
        type: 'cards',
        data: {
          heading: 'What Logic Does',
          cards: [
            { title: 'Workflow Audits & Process Design', description: 'Find the bottlenecks, design the fix.' },
            { title: 'Custom Dashboards', description: 'See your business in real time.' },
            { title: 'ERP/CRM Systems', description: 'All-in-one systems tailored to how you work.' },
            { title: 'API Integrations', description: 'Connect your tools so data flows automatically.' },
            { title: 'AI-Native Automation', description: 'Intelligent systems that learn and improve.' },
            { title: 'MVP Development', description: 'Launch fast, validate faster.' },
            { title: 'Fractional CTO Services', description: 'Senior technical leadership without the full-time cost.' },
          ],
        },
      },
      {
        type: 'cta',
        data: {
          heading: 'Visit Zeplow Logic',
          button_text: 'Get in Touch',
          button_url: '/contact',
        },
      },
    ] as ContentBlock[],
  },
  careers: {
    id: 6,
    slug: 'careers',
    title: 'Careers',
    template: 'careers',
    seo: seo(
      'Careers at Zeplow',
      'Join the Zeplow team. We\'re building something ambitious.'
    ),
    published_at: '2026-03-27T00:00:00Z',
    content: [
      {
        type: 'hero',
        data: { heading: 'Careers at Zeplow' },
      },
      {
        type: 'text',
        data: {
          body: '<p>We\'re a small, focused team building something ambitious. If you\'re interested in joining us, reach out directly.</p>',
        },
      },
      {
        type: 'cta',
        data: {
          heading: 'Interested?',
          button_text: 'Send us a message',
          button_url: '/contact',
        },
      },
    ] as ContentBlock[],
  },
  contact: {
    id: 7,
    slug: 'contact',
    title: 'Contact',
    template: 'contact',
    seo: seo(
      'Contact — Zeplow',
      'Get in touch with Zeplow. We\'d love to hear from you.'
    ),
    published_at: '2026-03-27T00:00:00Z',
    content: [
      {
        type: 'hero',
        data: {
          heading: 'Get in Touch',
          subheading:
            'Have a project in mind? We\'d love to hear from you.',
        },
      },
    ] as ContentBlock[],
  },
};

// ─── Parent Projects ────────────────────────────────────────────────────────

const parentProjects: ProjectListItem[] = [
  {
    id: 1,
    slug: 'tututor-ai',
    title: 'Tututor.ai',
    one_liner:
      'AI-powered tutoring platform that personalizes learning and accelerates student success',
    client_name: null,
    industry: 'EdTech',
    url: null,
    images: [],
    tags: ['AI', 'EdTech', 'Platform'],
    featured: true,
    sort_order: 0,
  },
  {
    id: 2,
    slug: 'capec-ai',
    title: 'CAPEC AI',
    one_liner:
      'Platform connecting emerging market businesses with global non-dilutive funding',
    client_name: null,
    industry: 'FinTech',
    url: null,
    images: [],
    tags: ['AI', 'FinTech', 'Marketplace'],
    featured: true,
    sort_order: 1,
  },
  {
    id: 3,
    slug: 'aditio-erp',
    title: 'Aditio ERP',
    one_liner:
      'Custom all-in-one ERP — project management, invoicing, team allocation in a single dashboard',
    client_name: null,
    industry: 'SaaS',
    url: null,
    images: [],
    tags: ['ERP', 'SaaS', 'Dashboard'],
    featured: true,
    sort_order: 2,
  },
];

// ─── Parent Team ────────────────────────────────────────────────────────────

const parentTeam: TeamMember[] = [
  {
    id: 1,
    name: 'Shadman Sakib',
    role: 'Co-Founder & CEO',
    bio: 'Strategy, direction, and brand & venture leadership. Leads strategy, client relationships, and brand direction across the Zeplow group.',
    photo: null,
    linkedin: null,
    email: null,
    is_founder: true,
    sort_order: 0,
  },
  {
    id: 2,
    name: 'Shakib Bin Kabir',
    role: 'Co-Founder & CTO',
    bio: 'Systems, automation, AI & technical architecture. Leads technology, product development, and infrastructure decisions across the Zeplow group.',
    photo: null,
    linkedin: null,
    email: null,
    is_founder: true,
    sort_order: 1,
  },
];

// ─── Mock Data Store ────────────────────────────────────────────────────────

const mockConfigs: Record<string, SiteConfig> = {
  parent: parentConfig,
};

const mockPages: Record<string, Record<string, Page>> = {
  parent: parentPages,
};

const mockProjects: Record<string, ProjectListItem[]> = {
  parent: parentProjects,
};

const mockTeam: Record<string, TeamMember[]> = {
  parent: parentTeam,
};

// ─── Accessors ──────────────────────────────────────────────────────────────

export function getMockConfig(siteKey: string): SiteConfig {
  return mockConfigs[siteKey] ?? mockConfigs.parent;
}

export function getMockPage(siteKey: string, slug: string): Page {
  const pages = mockPages[siteKey];
  if (pages?.[slug]) return pages[slug];
  return {
    id: 0,
    slug,
    title: slug,
    template: slug,
    content: [
      { type: 'hero', data: { heading: slug } } as ContentBlock,
      {
        type: 'text',
        data: { body: `<p>Mock content for ${slug}.</p>` },
      } as ContentBlock,
    ],
    seo: seo(`${slug} — Zeplow`, `Mock page for ${slug}`),
    published_at: '2026-03-27T00:00:00Z',
  };
}

export function getMockPages(siteKey: string): PageListItem[] {
  const pages = mockPages[siteKey] ?? {};
  return Object.values(pages).map((p) => ({
    id: p.id,
    slug: p.slug,
    title: p.title,
    template: p.template,
    seo: p.seo,
    sort_order: p.id,
    published_at: p.published_at,
  }));
}

export function getMockProjects(
  siteKey: string,
  params?: { featured?: boolean; limit?: number }
): ProjectListItem[] {
  let projects = mockProjects[siteKey] ?? [];
  if (params?.featured) projects = projects.filter((p) => p.featured);
  if (params?.limit) projects = projects.slice(0, params.limit);
  return projects;
}

export function getMockTeamMembers(siteKey: string): TeamMember[] {
  return mockTeam[siteKey] ?? [];
}

export function getMockBlogPosts(): BlogPostListItem[] {
  return [];
}
