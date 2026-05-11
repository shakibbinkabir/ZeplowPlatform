import { getBlogPosts, getBlogPost } from '@zeplow/api';
import { ArticleSchema, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'logic';
const SITE_NAME = 'Zeplow Logic';
const BASE_URL = 'https://logic.zeplow.com';

export async function generateStaticParams() {
  const posts = await getBlogPosts(SITE_KEY);
  return posts.map((post) => ({ slug: post.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: { slug: string };
}): Promise<Metadata> {
  const post = await getBlogPost(SITE_KEY, params.slug);
  const title = `${post.title} — Zeplow Logic`;
  const url = `${BASE_URL}/insights/${post.slug}`;
  return {
    title,
    description: post.seo.description,
    openGraph: {
      title,
      description: post.seo.description,
      images: post.seo.og_image ? [post.seo.og_image] : [],
      url,
      type: 'article',
      publishedTime: post.published_at,
      authors: post.author ? [post.author] : undefined,
    },
  };
}

export default async function InsightDetailPage({
  params,
}: {
  params: { slug: string };
}) {
  const post = await getBlogPost(SITE_KEY, params.slug);

  return (
    <main className="pt-32 md:pt-40">
      <ArticleSchema
        title={post.title}
        description={post.seo.description}
        url={`${BASE_URL}/insights/${post.slug}`}
        image={post.seo.og_image}
        author={post.author ?? 'Zeplow Logic'}
        publishedAt={post.published_at}
        siteName={SITE_NAME}
      />

      <article>
        <Container narrow>
          {post.tags.length > 0 && (
            <div className="mb-4 flex flex-wrap gap-3">
              {post.tags.map((tag) => (
                <span
                  key={tag}
                  className="font-mono text-[11px] uppercase tracking-[0.14em] text-accent"
                >
                  #{tag}
                </span>
              ))}
            </div>
          )}

          <h1 className="font-heading text-3xl font-bold leading-tight tracking-tight text-primary md:text-4xl lg:text-5xl">
            {post.title}
          </h1>

          <div className="mt-6 flex items-center gap-3 font-mono text-[12px] text-text/40">
            {post.author && <span>{post.author}</span>}
            {post.author && post.published_at && <span>·</span>}
            {post.published_at && (
              <time dateTime={post.published_at}>
                {new Date(post.published_at).toLocaleDateString('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
                })}
              </time>
            )}
          </div>

          {post.excerpt && (
            <p className="mt-8 border-l-2 border-accent/40 pl-6 text-lg leading-[1.8] text-text/60">
              {post.excerpt}
            </p>
          )}
        </Container>

        {post.cover_image && (
          <div className="mt-12">
            <Container>
              <img
                src={post.cover_image.large}
                alt={post.title}
                width={1600}
                height={900}
                loading="eager"
                className="w-full rounded-sm"
              />
            </Container>
          </div>
        )}

        <Container narrow className="mt-12 pb-24 md:pb-32">
          <div
            className="prose-custom text-[17px] leading-[1.8] text-text/75"
            dangerouslySetInnerHTML={{ __html: post.body }}
          />
        </Container>
      </article>
    </main>
  );
}
