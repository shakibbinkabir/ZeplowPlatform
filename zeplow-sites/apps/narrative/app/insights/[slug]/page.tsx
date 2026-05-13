import { getBlogPosts, getBlogPost } from '@zeplow/api';
import { ArticleSchema, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'narrative';
const SITE_NAME = 'Zeplow Narrative';
const BASE_URL = 'https://narrative.zeplow.com';

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
  const title = `${post.title} — Zeplow Narrative`;
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
        author={post.author ?? 'Zeplow Narrative'}
        publishedAt={post.published_at}
        siteName={SITE_NAME}
      />

      <article>
        <Container narrow>
          {post.tags.length > 0 && (
            <div className="mb-5 flex flex-wrap gap-x-4 gap-y-1">
              {post.tags.map((tag) => (
                <span
                  key={tag}
                  className="font-heading italic text-[14px] text-accent"
                >
                  #{tag}
                </span>
              ))}
            </div>
          )}

          <h1 className="font-heading text-4xl leading-[1.05] tracking-tight text-primary md:text-5xl lg:text-6xl">
            {post.title}
          </h1>

          <div className="mt-7 flex items-center gap-3 text-[13px] text-text/45">
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
            <p className="mt-10 border-l-2 border-accent pl-6 font-heading italic text-xl leading-snug text-text/65 md:text-2xl">
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
                className="w-full"
              />
            </Container>
          </div>
        )}

        <Container narrow className="mt-12 pb-24 md:pb-32">
          <div
            className="prose-custom text-[17px] leading-[1.9] text-text/80"
            dangerouslySetInnerHTML={{ __html: post.body }}
          />
        </Container>
      </article>
    </main>
  );
}
