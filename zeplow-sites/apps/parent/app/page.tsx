import { getPage, getProjects, getSiteConfig } from '@zeplow/api';
import { ContentRenderer, ProjectCard, OrganizationSchema, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'home');
  return {
    title: page.seo.title,
    description: page.seo.description,
    openGraph: {
      title: page.seo.title,
      description: page.seo.description,
      images: page.seo.og_image ? [page.seo.og_image] : [],
      url: 'https://zeplow.com',
      type: 'website',
    },
  };
}

export default async function HomePage() {
  const [page, featuredProjects, config] = await Promise.all([
    getPage(SITE_KEY, 'home'),
    getProjects(SITE_KEY, { featured: true, limit: 3 }),
    getSiteConfig(SITE_KEY),
  ]);

  return (
    <main>
      <OrganizationSchema
        name="Zeplow"
        url="https://zeplow.com"
        description="Story. Systems. Ventures."
        logo="https://zeplow.com/logo.png"
        email={config.contact_email}
        sameAs={Object.values(config.social_links)}
      />
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />
      {featuredProjects.length > 0 && (
        <section className="py-24 md:py-32">
          <Container>
            <div className="mb-16">
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-text/30">
                Selected Work
              </p>
              <h2 className="mt-3 font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
                Featured Projects
              </h2>
            </div>
            <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
              {featuredProjects.map((project) => (
                <ProjectCard
                  key={project.id}
                  project={project}
                  siteKey={SITE_KEY}
                />
              ))}
            </div>
          </Container>
        </section>
      )}
    </main>
  );
}
