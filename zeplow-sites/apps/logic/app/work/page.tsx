import { getPage, getProjects } from '@zeplow/api';
import { ContentRenderer, ProjectCard, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'work');
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

export default async function WorkPage() {
  const [page, projects] = await Promise.all([
    getPage(SITE_KEY, 'work'),
    getProjects(SITE_KEY),
  ]);

  return (
    <main>
      <ContentRenderer blocks={page.content} siteKey={SITE_KEY} />

      <section className="pb-24 md:pb-32">
        <Container>
          {projects.length > 0 ? (
            <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
              {projects.map((project) => (
                <a
                  key={project.id}
                  href={`/work/${project.slug}`}
                  className="block"
                >
                  <ProjectCard project={project} siteKey={SITE_KEY} />
                </a>
              ))}
            </div>
          ) : (
            <div className="rounded-sm border border-text/10 bg-white px-8 py-20 text-center">
              <p className="font-mono text-[11px] uppercase tracking-[0.18em] text-accent">
                // status: empty
              </p>
              <h2 className="mt-3 font-heading text-2xl font-bold tracking-tight text-primary">
                No projects published yet.
              </h2>
              <p className="mt-3 text-text/50">Check back soon.</p>
            </div>
          )}
        </Container>
      </section>
    </main>
  );
}
