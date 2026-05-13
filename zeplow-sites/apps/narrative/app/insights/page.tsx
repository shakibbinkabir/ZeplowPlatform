import { getBlogPosts, getPage } from '@zeplow/api';
import { BlogCard, Container } from '@zeplow/ui';
import { NarrativeContentRenderer } from '../../components/NarrativeContentRenderer';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'insights');
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

export default async function InsightsPage() {
  const [page, posts] = await Promise.all([
    getPage(SITE_KEY, 'insights'),
    getBlogPosts(SITE_KEY),
  ]);

  return (
    <main>
      <NarrativeContentRenderer blocks={page.content} />

      <section className="pb-24 md:pb-32">
        <Container>
          {posts.length > 0 ? (
            <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
              {posts.map((post) => (
                <BlogCard key={post.id} post={post} basePath="/insights" />
              ))}
            </div>
          ) : (
            <div className="border border-text/10 bg-white px-8 py-20 text-center">
              <p className="flex items-center justify-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">
                <span aria-hidden className="inline-block h-px w-8 bg-accent" />
                Coming soon
              </p>
              <h2 className="mt-5 font-heading text-2xl leading-tight text-primary md:text-3xl">
                The first essays are being written.
              </h2>
              <p className="mt-4 text-text/60">Come back soon.</p>
            </div>
          )}
        </Container>
      </section>
    </main>
  );
}
