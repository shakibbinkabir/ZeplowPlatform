import { getProjects, getProject } from '@zeplow/api';
import { IncidentReport } from '../../../components/IncidentReport';
import { AuditCTA } from '../../../components/AuditCTA';
import { Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';

export async function generateStaticParams() {
  const projects = await getProjects(SITE_KEY);
  return projects.map((project) => ({ slug: project.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: { slug: string };
}): Promise<Metadata> {
  const project = await getProject(SITE_KEY, params.slug);
  const title = `${project.title} — Zeplow Logic`;
  const url = `https://logic.zeplow.com/work/${project.slug}`;
  return {
    title,
    description: project.one_liner,
    openGraph: {
      title,
      description: project.one_liner,
      url,
      type: 'article',
    },
  };
}

export default async function ProjectPage({
  params,
}: {
  params: { slug: string };
}) {
  const project = await getProject(SITE_KEY, params.slug);

  return (
    <main className="pt-20">
      <IncidentReport project={project} />

      <section className="pb-24 md:pb-32">
        <Container narrow>
          <AuditCTA variant="dark" />
        </Container>
      </section>
    </main>
  );
}
