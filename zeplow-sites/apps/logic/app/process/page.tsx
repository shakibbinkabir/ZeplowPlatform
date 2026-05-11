import { getPage } from '@zeplow/api';
import { LogicContentRenderer } from '../../components/LogicContentRenderer';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';

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
      <LogicContentRenderer blocks={page.content} />
    </main>
  );
}
