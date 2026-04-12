import React from 'react';

interface OrganizationSchemaProps {
  name: string;
  url: string;
  description: string;
  logo: string;
  email: string;
  sameAs: string[];
}

export function OrganizationSchema({
  name,
  url,
  description,
  logo,
  email,
  sameAs,
}: OrganizationSchemaProps) {
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name,
    url,
    description,
    logo,
    email,
    sameAs: sameAs.filter(Boolean),
  };

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
    />
  );
}
