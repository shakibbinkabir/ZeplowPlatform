import { getPage, getTeamMembers } from '@zeplow/api';
import { ContentRenderer, TeamCard, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

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
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
      {team.length > 0 && (
        <section className="py-24 md:py-32">
          <Container>
            <div className="mb-16">
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-text/30">
                Leadership
              </p>
              <h2 className="mt-3 font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
                Our Team
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
