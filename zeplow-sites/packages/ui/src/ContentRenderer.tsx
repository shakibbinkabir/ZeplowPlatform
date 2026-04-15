import type { ContentBlock } from '@zeplow/api';
import { Container } from './Container';
import { Button } from './Button';

interface ContentRendererProps {
  blocks: ContentBlock[];
  siteKey: string;
}

function asString(value: unknown): string {
  return typeof value === 'string' ? value : '';
}

function getHeroBackground(
  data: Record<string, unknown>,
  siteKey: string
): string | null {
  const explicitImageKeys = [
    'background_image',
    'backgroundImage',
    'hero_image',
    'heroImage',
    'image',
  ];

  for (const key of explicitImageKeys) {
    const value = data[key];
    if (typeof value === 'string' && value.trim().length > 0) {
      return value;
    }
  }

  if (siteKey !== 'parent') {
    return null;
  }

  const heading = String(data.heading || '').toLowerCase();

  if (heading.includes('story. systems. ventures')) {
    return 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=2400&q=80';
  }

  if (heading.includes('about')) {
    return 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=2400&q=80';
  }

  if (heading.includes('our ventures')) {
    return 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=2400&q=80';
  }

  if (heading.includes('insights')) {
    return 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=2400&q=80';
  }

  if (heading.includes('narrative')) {
    return 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=2400&q=80';
  }

  if (heading.includes('logic')) {
    return 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=2400&q=80';
  }

  if (heading.includes('careers')) {
    return 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=2400&q=80';
  }

  if (heading.includes('contact') || heading.includes('get in touch')) {
    return 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=2400&q=80';
  }

  return 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=2400&q=80';
}

function HeroBlock({
  data,
  siteKey,
}: {
  data: Record<string, unknown>;
  siteKey: string;
}) {
  const backgroundImage = getHeroBackground(data, siteKey);
  const heading = asString(data.heading);
  const subheading = asString(data.subheading);
  const buttonText = asString(data.button_text);
  const buttonUrl = asString(data.button_url);

  return (
    <section className="relative flex min-h-[85vh] items-center bg-primary pt-20">
      {backgroundImage && (
        <img
          src={backgroundImage}
          alt=""
          aria-hidden="true"
          width={2400}
          height={1600}
          loading="eager"
          fetchPriority="high"
          className="absolute inset-0 h-full w-full object-cover"
        />
      )}
      <div
        className={`absolute inset-0 ${
          backgroundImage
            ? 'bg-gradient-to-br from-primary/90 via-primary/85 to-primary/70'
            : 'bg-gradient-to-br from-primary via-primary to-primary/80'
        }`}
      />
      <Container className="relative z-10 py-24">
        <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/40">
          Zeplow
        </p>
        <h1 className="mt-6 max-w-3xl font-heading text-5xl font-bold leading-[1.1] tracking-tight text-white md:text-6xl lg:text-7xl">
          {heading}
        </h1>
        {subheading && (
          <p className="mt-6 max-w-xl text-lg leading-relaxed text-white/60">
            {subheading}
          </p>
        )}
        {buttonText && buttonUrl && (
          <div className="mt-10 flex items-center gap-4">
            <a
              href={buttonUrl}
              className="inline-flex items-center rounded-full bg-white px-7 py-3 text-[13px] font-medium tracking-wide text-primary transition-all duration-300 hover:shadow-lg hover:shadow-white/20"
            >
              {buttonText}
            </a>
          </div>
        )}
      </Container>
    </section>
  );
}

function TextBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);
  const body = asString(data.body);

  return (
    <section className="py-24 md:py-32">
      <Container narrow>
        {heading && (
          <h2 className="mb-8 font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
            {heading}
          </h2>
        )}
        <div
          className="prose-custom text-lg leading-[1.8] text-text/60"
          dangerouslySetInnerHTML={{ __html: body }}
        />
      </Container>
    </section>
  );
}

function CardsBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);
  const cards = (data.cards || []) as Array<{
    title: string;
    description: string;
    url?: string;
    icon?: string;
  }>;

  const isTwo = cards.length === 2;

  return (
    <section className="py-24 md:py-32">
      <Container>
        {heading && (
          <div className="mb-16">
            <h2 className="font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl">
              {heading}
            </h2>
          </div>
        )}
        <div
          className={`grid gap-6 ${
            isTwo
              ? 'md:grid-cols-2'
              : 'md:grid-cols-2 lg:grid-cols-3'
          }`}
        >
          {cards.map((card) => (
            <div
              key={card.title}
              className="group relative rounded-2xl border border-text/[0.06] bg-white p-8 transition-all duration-300 hover:border-text/[0.1] hover:shadow-xl hover:shadow-text/[0.03] md:p-10"
            >
              <h3 className="font-heading text-xl font-bold tracking-tight text-primary">
                {card.title}
              </h3>
              <p className="mt-3 text-[15px] leading-relaxed text-text/50">
                {card.description}
              </p>
              {card.url && (
                <a
                  href={card.url}
                  className="mt-6 inline-flex items-center gap-2 text-[13px] font-medium text-accent transition-all duration-200 hover:gap-3"
                >
                  Learn more
                  <svg
                    className="h-3.5 w-3.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M17 8l4 4m0 0l-4 4m4-4H3"
                    />
                  </svg>
                </a>
              )}
            </div>
          ))}
        </div>
      </Container>
    </section>
  );
}

function CTABlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);
  const subheading = asString(data.subheading);
  const buttonText = asString(data.button_text);
  const buttonUrl = asString(data.button_url);

  return (
    <section className="py-24 md:py-32">
      <Container>
        <div className="rounded-3xl bg-primary/[0.03] px-8 py-20 text-center md:px-16">
          <h2 className="mx-auto max-w-2xl font-heading text-2xl font-bold tracking-tight text-primary md:text-3xl lg:text-4xl">
            {heading}
          </h2>
          {subheading && (
            <p className="mx-auto mt-5 max-w-lg text-text/50">
              {subheading}
            </p>
          )}
          {buttonText && buttonUrl && (
            <div className="mt-10">
              <Button href={buttonUrl}>{buttonText}</Button>
            </div>
          )}
        </div>
      </Container>
    </section>
  );
}

function ImageBlock({ data }: { data: Record<string, unknown> }) {
  const image = asString(data.image);
  const altText = asString(data.alt_text);
  const caption = asString(data.caption);

  return (
    <section className="py-24">
      <Container>
        {image && (
          <img
            src={image}
            alt={altText}
            width={1200}
            height={600}
            loading="lazy"
            className="w-full rounded-2xl"
          />
        )}
        {caption && (
          <p className="mt-4 text-center text-[13px] text-text/30">
            {caption}
          </p>
        )}
      </Container>
    </section>
  );
}

function GalleryBlock({ data }: { data: Record<string, unknown> }) {
  const images = (data.images || []) as Array<{
    url: string;
    alt: string;
  }>;

  return (
    <section className="py-24">
      <Container>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {images.map((img, i) => (
            <img
              key={i}
              src={img.url}
              alt={img.alt}
              width={800}
              height={600}
              loading="lazy"
              className="aspect-[4/3] w-full rounded-2xl object-cover"
            />
          ))}
        </div>
      </Container>
    </section>
  );
}

function StatsBlock({ data }: { data: Record<string, unknown> }) {
  const stats = (data.stats || []) as Array<{
    label: string;
    value: string;
  }>;

  return (
    <section className="border-y border-text/[0.06] py-20">
      <Container>
        <div className="grid grid-cols-2 gap-12 md:grid-cols-4">
          {stats.map((stat) => (
            <div key={stat.label} className="text-center">
              <p className="font-heading text-4xl font-bold tracking-tight text-primary md:text-5xl">
                {stat.value}
              </p>
              <p className="mt-2 text-[13px] uppercase tracking-[0.1em] text-text/35">
                {stat.label}
              </p>
            </div>
          ))}
        </div>
      </Container>
    </section>
  );
}

function DividerBlock({ data }: { data: Record<string, unknown> }) {
  const style = (data.style as string) || 'line';
  return (
    <div className="py-4">
      <Container>
        {style === 'line' && <hr className="border-text/[0.06]" />}
        {style === 'space' && <div className="h-12" />}
        {style === 'dots' && (
          <div className="flex justify-center gap-1.5">
            <span className="h-1 w-1 rounded-full bg-text/10" />
            <span className="h-1 w-1 rounded-full bg-text/10" />
            <span className="h-1 w-1 rounded-full bg-text/10" />
          </div>
        )}
      </Container>
    </div>
  );
}

function RawHTMLBlock({ data }: { data: Record<string, unknown> }) {
  return (
    <section className="py-24">
      <Container>
        <div dangerouslySetInnerHTML={{ __html: data.html as string }} />
      </Container>
    </section>
  );
}

function TeamBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);

  return (
    <section className="py-24">
      <Container>
        {heading && (
          <h2 className="mb-16 font-heading text-3xl font-bold tracking-tight text-primary">
            {heading}
          </h2>
        )}
      </Container>
    </section>
  );
}

function ProjectsBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);

  return (
    <section className="py-24">
      <Container>
        {heading && (
          <h2 className="mb-16 font-heading text-3xl font-bold tracking-tight text-primary">
            {heading}
          </h2>
        )}
      </Container>
    </section>
  );
}

function TestimonialsBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);

  return (
    <section className="py-24">
      <Container>
        {heading && (
          <h2 className="mb-16 font-heading text-3xl font-bold tracking-tight text-primary">
            {heading}
          </h2>
        )}
      </Container>
    </section>
  );
}

const blockComponents: Record<
  string,
  React.ComponentType<{ data: Record<string, unknown> }>
> = {
  text: TextBlock,
  cards: CardsBlock,
  cta: CTABlock,
  image: ImageBlock,
  gallery: GalleryBlock,
  stats: StatsBlock,
  divider: DividerBlock,
  raw_html: RawHTMLBlock,
  team: TeamBlock,
  projects: ProjectsBlock,
  testimonials: TestimonialsBlock,
};

export function ContentRenderer({ blocks, siteKey }: ContentRendererProps) {
  return (
    <>
      {blocks.map((block, index) => {
        if (block.type === 'hero') {
          return (
            <HeroBlock
              key={`${block.type}-${index}`}
              data={block.data}
              siteKey={siteKey}
            />
          );
        }

        const Component = blockComponents[block.type];
        if (!Component) {
          if (process.env.NODE_ENV === 'development') {
            console.warn(`Unknown block type: ${block.type}`);
          }
          return null;
        }
        return <Component key={`${block.type}-${index}`} data={block.data} />;
      })}
    </>
  );
}
