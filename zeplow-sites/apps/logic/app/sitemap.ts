import { getPages, getBlogPosts, getProjects } from '@zeplow/api';
import type { MetadataRoute } from 'next';

const BASE_URL = 'https://logic.zeplow.com';
const SITE_KEY = 'logic';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [pages, blogPosts, projects] = await Promise.all([
    getPages(SITE_KEY),
    getBlogPosts(SITE_KEY),
    getProjects(SITE_KEY),
  ]);

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
