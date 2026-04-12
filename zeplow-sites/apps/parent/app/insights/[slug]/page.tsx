import { getBlogPosts, getBlogPost } from '@zeplow/api';
import { ArticleSchema, Container } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

export async function generateStaticParams() {
  const posts = await getBlogPosts(SITE_KEY);
  return posts.map((post) => ({ slug: post.slug }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const post = await getBlogPost(SITE_KEY, slug);
  return {
    title: post.seo.title,
    description: post.seo.description,
    openGraph: {
      title: post.seo.title,
      description: post.seo.description,
      images: post.cover_image ? [post.cover_image.original] : [],
      type: 'article',
    },
  };
}

export default async function BlogPostPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const post = await getBlogPost(SITE_KEY, slug);

  return (
    <main>
      <ArticleSchema
        title={post.title}
        description={post.seo.description}
        url={`https://zeplow.com/insights/${post.slug}`}
        image={post.cover_image?.original}
        author={post.author || 'Zeplow'}
        publishedAt={post.published_at}
        siteName="Zeplow"
      />
      <article className="pt-32 pb-24 md:pt-40 md:pb-32">
        <Container narrow>
          <div className="flex items-center gap-2 text-[12px] text-text/30">
            {post.author && <span>{post.author}</span>}
            {post.author && post.published_at && (
              <span className="text-text/15">/</span>
            )}
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
          <h1 className="mt-4 font-heading text-4xl font-bold leading-[1.15] tracking-tight text-primary md:text-5xl">
            {post.title}
          </h1>
          {post.tags.length > 0 && (
            <div className="mt-6 flex flex-wrap gap-3">
              {post.tags.map((tag) => (
                <span
                  key={tag}
                  className="text-[11px] font-medium uppercase tracking-[0.1em] text-accent/60"
                >
                  {tag}
                </span>
              ))}
            </div>
          )}
        </Container>
        {post.cover_image && (
          <div className="mx-auto mt-12 max-w-5xl px-6 lg:px-8">
            <img
              src={post.cover_image.large}
              alt={post.title}
              width={1920}
              height={1080}
              loading="eager"
              className="w-full rounded-2xl"
            />
          </div>
        )}
        <Container narrow>
          <div
            className="prose-custom mt-16 text-lg leading-[1.8] text-text/60"
            dangerouslySetInnerHTML={{ __html: post.body }}
          />
        </Container>
      </article>
    </main>
  );
}
