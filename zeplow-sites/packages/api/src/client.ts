import type {
  SiteConfig,
  PageListItem,
  Page,
  ProjectListItem,
  Project,
  BlogPostListItem,
  BlogPost,
  Testimonial,
  TeamMember,
} from './types';
import {
  getMockConfig,
  getMockPage,
  getMockPages,
  getMockProjects,
  getMockProject,
  getMockTeamMembers,
  getMockTestimonials,
  getMockBlogPosts,
  getMockBlogBody,
} from './mock-data';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://api.zeplow.com';
const USE_MOCK_DATA =
  process.env.NEXT_PUBLIC_ZEPLOW_MOCK_ONLY === '1' ||
  process.env.ZEPLOW_MOCK_ONLY === '1';

function mockBlogPost(siteKey: string, slug: string): BlogPost {
  const posts = getMockBlogPosts(siteKey);
  const existing = posts.find((post) => post.slug === slug);
  const body =
    getMockBlogBody(siteKey, slug) ??
    '<p>This insight is currently unavailable in offline mode. Please check back soon.</p>';

  if (existing) {
    return {
      ...existing,
      body,
      seo: {
        title: `${existing.title} — Zeplow`,
        description:
          existing.excerpt || 'Thoughts on ventures, narrative, and systems.',
        og_image: existing.cover_image?.original || null,
      },
    };
  }

  return {
    id: 0,
    slug,
    title: 'Insight',
    excerpt: 'This insight is not available right now.',
    cover_image: null,
    tags: [],
    author: 'Zeplow',
    published_at: '2026-03-27T00:00:00Z',
    body,
    seo: {
      title: 'Insight — Zeplow',
      description: 'Thoughts on ventures, narrative, and systems.',
      og_image: null,
    },
  };
}

function mockProjectOrStub(siteKey: string, slug: string): Project {
  const full = getMockProject(siteKey, slug);
  if (full) return full;

  const list = getMockProjects(siteKey);
  const item = list.find((p) => p.slug === slug) ?? list[0];

  if (!item) {
    return {
      id: 0,
      slug,
      title: slug,
      one_liner: 'Project details are unavailable in offline mode.',
      client_name: null,
      industry: null,
      url: null,
      images: [],
      tags: [],
      featured: false,
      sort_order: 0,
      challenge: null,
      solution: null,
      outcome: null,
      tech_stack: [],
      published_at: '2026-03-27T00:00:00Z',
    };
  }

  return {
    ...item,
    challenge: null,
    solution: null,
    outcome: null,
    tech_stack: [],
    published_at: '2026-03-27T00:00:00Z',
  };
}

async function fetchApi<T>(path: string): Promise<T> {
  const url = `${API_BASE}${path}`;
  const res = await fetch(url, {
    headers: { Accept: 'application/json' },
  });

  if (!res.ok) {
    throw new Error(`API Error: ${res.status} ${res.statusText} for ${url}`);
  }

  return res.json();
}

// The API returns a bare array when ?limit=N is passed, and a paginated
// { data, meta } envelope otherwise. Unwrap to a bare array either way.
function unwrapList<T>(raw: unknown): T[] {
  if (Array.isArray(raw)) return raw as T[];
  if (raw && typeof raw === 'object' && Array.isArray((raw as { data?: unknown }).data)) {
    return (raw as { data: T[] }).data;
  }
  return [];
}

// Site Config
export async function getSiteConfig(siteKey: string): Promise<SiteConfig> {
  if (USE_MOCK_DATA) {
    return getMockConfig(siteKey);
  }

  try {
    return await fetchApi<SiteConfig>(`/sites/v1/${siteKey}/config`);
  } catch {
    return getMockConfig(siteKey);
  }
}

// Pages
export async function getPages(siteKey: string): Promise<PageListItem[]> {
  if (USE_MOCK_DATA) {
    return getMockPages(siteKey);
  }

  try {
    return await fetchApi<PageListItem[]>(`/sites/v1/${siteKey}/pages`);
  } catch {
    return getMockPages(siteKey);
  }
}

export async function getPage(siteKey: string, slug: string): Promise<Page> {
  if (USE_MOCK_DATA) {
    return getMockPage(siteKey, slug);
  }

  try {
    return await fetchApi<Page>(`/sites/v1/${siteKey}/pages/${slug}`);
  } catch {
    return getMockPage(siteKey, slug);
  }
}

// Projects
export async function getProjects(
  siteKey: string,
  params?: { featured?: boolean; limit?: number }
): Promise<ProjectListItem[]> {
  if (USE_MOCK_DATA) {
    return getMockProjects(siteKey, params);
  }

  try {
    const searchParams = new URLSearchParams();
    if (params?.featured) searchParams.set('featured', '1');
    if (params?.limit) searchParams.set('limit', String(params.limit));
    const qs = searchParams.toString();
    const raw = await fetchApi<unknown>(
      `/sites/v1/${siteKey}/projects${qs ? `?${qs}` : ''}`
    );
    return unwrapList<ProjectListItem>(raw);
  } catch {
    return getMockProjects(siteKey, params);
  }
}

export async function getProject(
  siteKey: string,
  slug: string
): Promise<Project> {
  if (USE_MOCK_DATA) {
    return mockProjectOrStub(siteKey, slug);
  }

  try {
    return await fetchApi<Project>(`/sites/v1/${siteKey}/projects/${slug}`);
  } catch {
    return mockProjectOrStub(siteKey, slug);
  }
}

// Blog
export async function getBlogPosts(
  siteKey: string,
  params?: { tag?: string; limit?: number }
): Promise<BlogPostListItem[]> {
  if (USE_MOCK_DATA) {
    return getMockBlogPosts(siteKey);
  }

  try {
    const searchParams = new URLSearchParams();
    if (params?.tag) searchParams.set('tag', params.tag);
    if (params?.limit) searchParams.set('limit', String(params.limit));
    const qs = searchParams.toString();
    const raw = await fetchApi<unknown>(
      `/sites/v1/${siteKey}/blog${qs ? `?${qs}` : ''}`
    );
    return unwrapList<BlogPostListItem>(raw);
  } catch {
    return getMockBlogPosts(siteKey);
  }
}

export async function getBlogPost(
  siteKey: string,
  slug: string
): Promise<BlogPost> {
  if (USE_MOCK_DATA) {
    return mockBlogPost(siteKey, slug);
  }

  try {
    return await fetchApi<BlogPost>(`/sites/v1/${siteKey}/blog/${slug}`);
  } catch {
    return mockBlogPost(siteKey, slug);
  }
}

// Testimonials
export async function getTestimonials(
  siteKey: string
): Promise<Testimonial[]> {
  if (USE_MOCK_DATA) {
    return getMockTestimonials(siteKey);
  }

  try {
    return await fetchApi<Testimonial[]>(`/sites/v1/${siteKey}/testimonials`);
  } catch {
    return getMockTestimonials(siteKey);
  }
}

// Team
export async function getTeamMembers(
  siteKey: string
): Promise<TeamMember[]> {
  if (USE_MOCK_DATA) {
    return getMockTeamMembers(siteKey);
  }

  try {
    return await fetchApi<TeamMember[]>(`/sites/v1/${siteKey}/team`);
  } catch {
    return getMockTeamMembers(siteKey);
  }
}
