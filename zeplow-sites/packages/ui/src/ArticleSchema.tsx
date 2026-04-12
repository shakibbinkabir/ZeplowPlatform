import React from 'react';

interface ArticleSchemaProps {
  title: string;
  description: string;
  url: string;
  image?: string | null;
  author: string;
  publishedAt: string;
  siteName: string;
}

export function ArticleSchema({
  title,
  description,
  url,
  image,
  author,
  publishedAt,
  siteName,
}: ArticleSchemaProps) {
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: title,
    description,
    url,
    ...(image ? { image } : {}),
    author: {
      '@type': 'Person',
      name: author,
    },
    datePublished: publishedAt,
    publisher: {
      '@type': 'Organization',
      name: siteName,
    },
  };

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
    />
  );
}
