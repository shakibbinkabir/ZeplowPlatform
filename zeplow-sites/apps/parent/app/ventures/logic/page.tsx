import { getPage } from '@zeplow/api';
import { ContentRenderer } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'ventures-logic');
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

export default async function VenturesLogicPage() {
  const page = await getPage(SITE_KEY, 'ventures-logic');

  return (
    <main>
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
    </main>
  );
}
