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

  const ventureEntries = [
    {
      url: `${BASE_URL}/ventures`,
      changeFrequency: 'monthly' as const,
      priority: 0.8,
    },
    {
      url: `${BASE_URL}/ventures/narrative`,
      changeFrequency: 'monthly' as const,
      priority: 0.7,
    },
    {
      url: `${BASE_URL}/ventures/logic`,
      changeFrequency: 'monthly' as const,
      priority: 0.7,
    },
  ];

  return [...pageEntries, ...ventureEntries, ...blogEntries];
}
