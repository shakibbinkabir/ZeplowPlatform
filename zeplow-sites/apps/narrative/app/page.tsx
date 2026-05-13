import {
  getPage,
  getProjects,
  getTestimonials,
  getSiteConfig,
} from '@zeplow/api';
import {
  ProjectCard,
  TestimonialCard,
  OrganizationSchema,
  Container,
} from '@zeplow/ui';
import { NarrativeContentRenderer } from '../components/NarrativeContentRenderer';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'home');
  return {
    title: page.seo.title,
    description: page.seo.description,
    openGraph: {
      title: page.seo.title,
      description: page.seo.description,
      images: page.seo.og_image ? [page.seo.og_image] : [],
      url: 'https://narrative.zeplow.com',
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
        name="Zeplow Narrative"
        url="https://narrative.zeplow.com"
        description="Stories that sell. Brand storytelling, identity & content systems."
        logo="https://narrative.zeplow.com/logo.png"
        email={config.contact_email}
        sameAs={Object.values(config.social_links)}
      />

      <NarrativeContentRenderer blocks={page.content} />

      {featuredProjects.length > 0 && (
        <section className="bg-white py-24 md:py-32">
          <Container>
            <div className="mb-14 max-w-3xl">
              <p className="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">
                <span aria-hidden className="inline-block h-px w-8 bg-accent" />
                Selected stories
              </p>
              <h2 className="mt-5 font-heading text-3xl leading-[1.15] tracking-tight text-primary md:text-4xl lg:text-5xl">
                Brands we made unforgettable.
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
        <section className="py-24 md:py-32">
          <Container>
            <div className="mb-14 max-w-3xl">
              <p className="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">
                <span aria-hidden className="inline-block h-px w-8 bg-accent" />
                In their words
              </p>
              <h2 className="mt-5 font-heading text-3xl leading-[1.15] tracking-tight text-primary md:text-4xl lg:text-5xl">
                What clients say after.
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
