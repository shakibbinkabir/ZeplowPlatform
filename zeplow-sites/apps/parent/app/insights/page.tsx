import { getBlogPosts, getPage } from '@zeplow/api';
import type { ContentBlock } from '@zeplow/api';
import { BlogCard, Container, ContentRenderer } from '@zeplow/ui';
import type { Metadata } from 'next';

const SITE_KEY = 'parent';

const INSIGHTS_DESCRIPTION =
  'Thoughts on building ventures, brand storytelling, and systems that scale.';

const fallbackInsightsBlocks: ContentBlock[] = [
  {
    type: 'hero',
    data: {
      heading: 'Insights',
      subheading: INSIGHTS_DESCRIPTION,
    },
  },
];

function isPlaceholderInsightsContent(blocks: ContentBlock[]): boolean {
  if (blocks.length !== 2) {
    return false;
  }

  const [hero, text] = blocks;
  const heroHeading = String(hero.data.heading || '').toLowerCase();
  const textBody = String(text.data.body || '').toLowerCase();

  return (
    hero.type === 'hero' &&
    heroHeading === 'insights' &&
    text.type === 'text' &&
    textBody.includes('mock content for insights')
  );
}

function hasHeroBlock(blocks: ContentBlock[]): boolean {
  return blocks.some((block) => block.type === 'hero');
}

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage(SITE_KEY, 'insights');

  return {
    title: page.seo.title || 'Insights — Zeplow',
    description: page.seo.description || INSIGHTS_DESCRIPTION,
    openGraph: {
      title: page.seo.title || 'Insights — Zeplow',
      description: page.seo.description || INSIGHTS_DESCRIPTION,
      images: page.seo.og_image ? [page.seo.og_image] : [],
    },
  };
}

export default async function InsightsPage() {
  const [page, posts] = await Promise.all([
    getPage(SITE_KEY, 'insights'),
    getBlogPosts(SITE_KEY),
  ]);

  const introBlocks =
    page.content.length === 0 || isPlaceholderInsightsContent(page.content)
      ? fallbackInsightsBlocks
      : hasHeroBlock(page.content)
        ? page.content
        : [...fallbackInsightsBlocks, ...page.content];

  return (
    <main>
      <ContentRenderer blocks={introBlocks} siteKey={SITE_KEY} />
      <section className="pb-24 md:pb-32">
        <Container>
          {posts.length > 0 && (
            <div className="mb-14">
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-text/30">
                Latest
              </p>
              <h2 className="mt-3 font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
                Recent Insights
              </h2>
            </div>
          )}
          {posts.length > 0 ? (
            <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
              {posts.map((post) => (
                <BlogCard key={post.id} post={post} basePath="/insights" />
              ))}
            </div>
          ) : (
            <div className="rounded-2xl border border-text/[0.06] bg-white px-8 py-20 text-center">
              <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-text/30">
                Insights
              </p>
              <h2 className="mt-3 font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
                Coming Soon
              </h2>
              <p className="mt-4 text-text/45">
                No insights published yet. Check back soon.
              </p>
            </div>
          )}
        </Container>
      </section>
    </main>
  );
}
