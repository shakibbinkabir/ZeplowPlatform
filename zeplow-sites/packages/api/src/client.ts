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
  getMockTeamMembers,
  getMockBlogPosts,
} from './mock-data';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://api.zeplow.com';

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

// Site Config
export async function getSiteConfig(siteKey: string): Promise<SiteConfig> {
  try {
    return await fetchApi<SiteConfig>(`/sites/v1/${siteKey}/config`);
  } catch {
    return getMockConfig(siteKey);
  }
}

// Pages
export async function getPages(siteKey: string): Promise<PageListItem[]> {
  try {
    return await fetchApi<PageListItem[]>(`/sites/v1/${siteKey}/pages`);
  } catch {
    return getMockPages(siteKey);
  }
}

export async function getPage(siteKey: string, slug: string): Promise<Page> {
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
  try {
    const searchParams = new URLSearchParams();
    if (params?.featured) searchParams.set('featured', '1');
    if (params?.limit) searchParams.set('limit', String(params.limit));
    const qs = searchParams.toString();
    return await fetchApi<ProjectListItem[]>(
      `/sites/v1/${siteKey}/projects${qs ? `?${qs}` : ''}`
    );
  } catch {
    return getMockProjects(siteKey, params);
  }
}

export async function getProject(
  siteKey: string,
  slug: string
): Promise<Project> {
  try {
    return await fetchApi<Project>(`/sites/v1/${siteKey}/projects/${slug}`);
  } catch {
    const list = getMockProjects(siteKey);
    const item = list.find((p) => p.slug === slug) ?? list[0];
    return {
      ...item,
      challenge: null,
      solution: null,
      outcome: null,
      tech_stack: [],
      published_at: '2026-03-27T00:00:00Z',
    };
  }
}

// Blog
export async function getBlogPosts(
  siteKey: string,
  params?: { tag?: string; limit?: number }
): Promise<BlogPostListItem[]> {
  try {
    const searchParams = new URLSearchParams();
    if (params?.tag) searchParams.set('tag', params.tag);
    if (params?.limit) searchParams.set('limit', String(params.limit));
    const qs = searchParams.toString();
    return await fetchApi<BlogPostListItem[]>(
      `/sites/v1/${siteKey}/blog${qs ? `?${qs}` : ''}`
    );
  } catch {
    return getMockBlogPosts();
  }
}

export async function getBlogPost(
  siteKey: string,
  slug: string
): Promise<BlogPost> {
  return fetchApi<BlogPost>(`/sites/v1/${siteKey}/blog/${slug}`);
}

// Testimonials
export async function getTestimonials(
  siteKey: string
): Promise<Testimonial[]> {
  try {
    return await fetchApi<Testimonial[]>(`/sites/v1/${siteKey}/testimonials`);
  } catch {
    return [];
  }
}

// Team
export async function getTeamMembers(
  siteKey: string
): Promise<TeamMember[]> {
  try {
    return await fetchApi<TeamMember[]>(`/sites/v1/${siteKey}/team`);
  } catch {
    return getMockTeamMembers(siteKey);
  }
}
