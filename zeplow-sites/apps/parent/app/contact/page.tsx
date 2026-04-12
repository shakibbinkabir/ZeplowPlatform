import { getPage } from '@zeplow/api';
import { ContentRenderer, ContactForm, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'contact');
  return {
    title: page.seo.title,
    description: page.seo.description,
  };
}

export default async function ContactPage() {
  const page = await getPage(SITE_KEY, 'contact');

  return (
    <main>
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
      <section className="pb-24 md:pb-32">
        <Container>
          <div className="mx-auto max-w-2xl">
            <ContactForm siteKey={SITE_KEY} siteDomain="zeplow.com" />
          </div>
        </Container>
      </section>
    </main>
  );
}
