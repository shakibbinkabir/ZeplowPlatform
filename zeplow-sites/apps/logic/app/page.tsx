import {
  getPage,
  getProjects,
  getTestimonials,
  getSiteConfig,
} from '@zeplow/api';
import {
  ContentRenderer,
  ProjectCard,
  TestimonialCard,
  OrganizationSchema,
  Container,
} from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'home');
  return {
    title: page.seo.title,
    description: page.seo.description,
    openGraph: {
      title: page.seo.title,
      description: page.seo.description,
      images: page.seo.og_image ? [page.seo.og_image] : [],
      url: 'https://logic.zeplow.com',
      type: 'website',
    },
  };
}

export default async function HomePage() {
  const [page, featuredProjects, testimonials, config] = await Promise.all([
    getPage(SITE_KEY, 'home'),
    getProjects(SITE_KEY, { featured: true, limit: 3 }),
    getTestimonials(SITE_KEY),
    getSiteConfig(SITE_KEY),
  ]);

  return (
    <main>
      <OrganizationSchema
        name="Zeplow Logic"
        url="https://logic.zeplow.com"
        description="Build once. Run forever. Technology, automation & AI systems."
        logo="https://logic.zeplow.com/logo.png"
        email={config.contact_email}
        sameAs={Object.values(config.social_links)}
      />

      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />

      {featuredProjects.length > 0 && (
        <section className="py-24 md:py-32">
          <Container>
            <div className="mb-16">
              <p className="font-mono text-[11px] uppercase tracking-[0.18em] text-accent">
                // selected systems
              </p>
              <h2 className="mt-3 font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
                Featured Work
              </h2>
            </div>
            <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
              {featuredProjects.map((project) => (
                <a
                  key={project.id}
                  href={`/work/${project.slug}`}
                  className="block"
                >
                  <ProjectCard project={project} siteKey={SITE_KEY} />
                </a>
              ))}
            </div>
          </Container>
        </section>
      )}

      {testimonials.length > 0 && (
        <section className="border-t border-text/10 py-24 md:py-32">
          <Container>
            <div className="mb-16">
              <p className="font-mono text-[11px] uppercase tracking-[0.18em] text-accent">
                // status reports
              </p>
              <h2 className="mt-3 font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
                What clients say
              </h2>
            </div>
            <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
              {testimonials.map((testimonial) => (
                <TestimonialCard
                  key={testimonial.id}
                  testimonial={testimonial}
                />
              ))}
            </div>
          </Container>
        </section>
      )}
    </main>
  );
}
