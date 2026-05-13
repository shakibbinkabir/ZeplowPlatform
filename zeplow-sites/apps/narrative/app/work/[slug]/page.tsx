import { getProjects, getProject } from '@zeplow/api';
import { Container } from '@zeplow/ui';
import { FeatureStory } from '../../../components/FeatureStory';
import { HeartbeatCTA } from '../../../components/HeartbeatCTA';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

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
  const title = `${project.title} — Zeplow Narrative`;
  const url = `https://narrative.zeplow.com/work/${project.slug}`;
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
      <FeatureStory project={project} />

      <section className="pb-24 md:pb-32">
        <Container narrow>
          <HeartbeatCTA variant="dark" />
        </Container>
      </section>
    </main>
  );
}
