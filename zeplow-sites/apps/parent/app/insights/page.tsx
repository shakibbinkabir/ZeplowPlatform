import { getBlogPosts } from '@zeplow/api';
import { BlogCard, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export const metadata: Metadata = {
  title: 'Insights — Zeplow',
  description:
    'Thoughts on building ventures, brand storytelling, and systems that scale.',
};

export default async function InsightsPage() {
  const posts = await getBlogPosts(SITE_KEY);

  return (
    <main>
      <section className="pt-32 pb-24 md:pt-40 md:pb-32">
        <Container>
          <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-text/30">
            Blog
          </p>
          <h1 className="mt-3 font-heading text-4xl font-bold tracking-tight text-primary md:text-5xl">
            Insights
          </h1>
          <p className="mt-5 max-w-lg text-lg text-text/45">
            Thoughts on building ventures, brand storytelling, and systems that
            scale.
          </p>
        </Container>
      </section>
      <section className="pb-24 md:pb-32">
        <Container>
          {posts.length > 0 ? (
            <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
              {posts.map((post) => (
                <BlogCard key={post.id} post={post} basePath="/insights" />
              ))}
            </div>
          ) : (
            <div className="rounded-2xl border border-text/[0.06] bg-white px-8 py-20 text-center">
              <p className="text-text/35">
                No insights published yet. Check back soon.
              </p>
            </div>
          )}
        </Container>
      </section>
    </main>
  );
}
