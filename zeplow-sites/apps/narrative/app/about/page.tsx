import { getPage, getTeamMembers } from '@zeplow/api';
import { TeamCard, Container } from '@zeplow/ui';
import { NarrativeContentRenderer } from '../../components/NarrativeContentRenderer';
import { AntiClientBlock } from '../../components/AntiClientBlock';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'about');
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

export default async function AboutPage() {
  const [page, team] = await Promise.all([
    getPage(SITE_KEY, 'about'),
    getTeamMembers(SITE_KEY),
  ]);

  return (
    <main>
      <NarrativeContentRenderer blocks={page.content} />

      <AntiClientBlock />

      {team.length > 0 && (
        <section className="py-24 md:py-32">
          <Container>
            <div className="mb-14 max-w-3xl">
              <p className="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">
                <span aria-hidden className="inline-block h-px w-8 bg-accent" />
                The people behind the story
              </p>
              <h2 className="mt-5 font-heading text-3xl leading-[1.15] tracking-tight text-primary md:text-4xl lg:text-5xl">
                Who we are.
              </h2>
            </div>
            <div className="grid gap-12 md:grid-cols-2">
              {team.map((member) => (
                <TeamCard key={member.id} member={member} />
              ))}
            </div>
          </Container>
        </section>
      )}
    </main>
  );
}
