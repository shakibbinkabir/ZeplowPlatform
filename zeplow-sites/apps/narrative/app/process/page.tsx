import { getPage } from '@zeplow/api';
import { NarrativeContentRenderer } from '../../components/NarrativeContentRenderer';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'process');
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

export default async function ProcessPage() {
  const page = await getPage(SITE_KEY, 'process');

  return (
    <main>
      <NarrativeContentRenderer blocks={page.content} />
    </main>
  );
}
