import { getPage } from '@zeplow/api';
import { ContactForm, Container } from '@zeplow/ui';
import { NarrativeContentRenderer } from '../../components/NarrativeContentRenderer';
import { HeartbeatCTA } from '../../components/HeartbeatCTA';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

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
      <NarrativeContentRenderer blocks={page.content} />

      <section className="pb-16">
        <Container narrow>
          <HeartbeatCTA variant="light" />
        </Container>
      </section>

      <section className="pb-24 md:pb-32">
        <Container>
          <div className="mx-auto max-w-2xl">
            <ContactForm siteKey={SITE_KEY} siteDomain="narrative.zeplow.com" />
          </div>
        </Container>
      </section>
    </main>
  );
}
