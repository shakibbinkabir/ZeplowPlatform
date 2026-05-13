import React from 'react';
import type { ContentBlock } from '@zeplow/api';
import { Container } from '@zeplow/ui';

function asString(value: unknown): string {
  return typeof value === 'string' ? value : '';
}

// Eyebrow shared by most blocks — coral hairline + uppercase label.
function Eyebrow({ label, tone = 'dark' }: { label: string; tone?: 'dark' | 'light' }) {
  return (
    <p
      className={`flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] ${
        tone === 'light' ? 'text-accent' : 'text-accent'
      }`}
    >
      <span aria-hidden className="inline-block h-px w-8 bg-accent" />
      {label}
    </p>
  );
}

function HeroBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);
  const subheading = asString(data.subheading);
  const buttonText = asString(data.cta_text) || asString(data.button_text);
  const buttonUrl = asString(data.cta_url) || asString(data.button_url);

  return (
    <section className="relative bg-primary pt-36 pb-24 md:pt-44 md:pb-32">
      <Container>
        <Eyebrow label="Zeplow Narrative" />
        <h1 className="mt-7 max-w-4xl font-heading text-4xl leading-[1.05] tracking-tight text-background md:text-6xl lg:text-7xl">
          {heading}
        </h1>
        {subheading && (
          <p className="mt-7 max-w-2xl text-lg leading-[1.7] text-background/75 md:text-xl">
            {subheading}
          </p>
        )}
        {buttonText && buttonUrl && (
          <a
            href={buttonUrl}
            className="mt-10 inline-flex items-center gap-2 bg-accent px-7 py-3.5 text-[15px] font-medium text-primary transition-colors hover:bg-accent/90"
          >
            {buttonText}
            <span aria-hidden>→</span>
          </a>
        )}
      </Container>
    </section>
  );
}

function TextBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);
  const body = asString(data.body);

  return (
    <section className="py-20 md:py-28">
      <Container narrow>
        {heading && (
          <div className="mb-10">
            <Eyebrow label="—" />
            <h2 className="mt-5 font-heading text-3xl leading-[1.15] tracking-tight text-primary md:text-4xl lg:text-5xl">
              {heading}
            </h2>
          </div>
        )}
        <div
          className="prose-custom text-[17px] leading-[1.9] text-text/80"
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
    link_text?: string;
    link_url?: string;
  }>;

  const gridCols =
    cards.length === 3
      ? 'md:grid-cols-3'
      : cards.length === 4 || cards.length === 8
        ? 'md:grid-cols-2 lg:grid-cols-4'
        : 'md:grid-cols-2';

  return (
    <section className="bg-white py-20 md:py-28">
      <Container>
        {heading && (
          <div className="mb-14 max-w-3xl">
            <Eyebrow label="—" />
            <h2 className="mt-5 font-heading text-3xl leading-[1.15] tracking-tight text-primary md:text-4xl">
              {heading}
            </h2>
          </div>
        )}
        <div className={`grid gap-6 ${gridCols}`}>
          {cards.map((card, index) => (
            <article
              key={card.title}
              className="group flex flex-col rounded-sm border border-text/10 bg-background p-7 transition-shadow duration-200 hover:shadow-[0_10px_30px_-12px_rgba(20,0,4,0.18)] md:p-9"
            >
              <span className="font-heading text-2xl font-bold text-accent">
                {String(index + 1).padStart(2, '0')}
              </span>
              <h3 className="mt-3 font-heading text-xl leading-snug text-primary md:text-2xl">
                {card.title}
              </h3>
              <p className="mt-4 text-[15px] leading-[1.8] text-text/75">
                {card.description}
              </p>
              {card.link_url && (
                <a
                  href={card.link_url}
                  className="mt-6 inline-flex items-center gap-1.5 text-[13px] font-medium text-accent transition-colors hover:text-primary"
                >
                  {card.link_text || 'Read more'}
                  <span aria-hidden>→</span>
                </a>
              )}
            </article>
          ))}
        </div>
      </Container>
    </section>
  );
}

function CTABlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);
  const description = asString(data.description);
  const buttonText = asString(data.button_text);
  const buttonUrl = asString(data.button_url);

  return (
    <section className="py-20 md:py-28">
      <Container narrow>
        <aside className="relative overflow-hidden bg-primary px-8 py-14 text-background md:px-14 md:py-20">
          <Eyebrow label="A note to the reader" />
          <h2 className="mt-5 max-w-2xl font-heading text-3xl leading-[1.15] tracking-tight md:text-4xl lg:text-[2.75rem]">
            {heading}
          </h2>
          {description && (
            <p className="mt-6 max-w-xl text-[15px] leading-[1.9] text-background/75">
              {description}
            </p>
          )}
          {buttonText && buttonUrl && (
            <a
              href={buttonUrl}
              className="mt-9 inline-flex items-center gap-2 bg-accent px-7 py-3.5 text-[15px] font-medium text-primary transition-colors hover:bg-accent/90"
            >
              {buttonText}
              <span aria-hidden>→</span>
            </a>
          )}
        </aside>
      </Container>
    </section>
  );
}

function StatsBlock({ data }: { data: Record<string, unknown> }) {
  const stats = (data.stats || []) as Array<{
    label: string;
    number: string;
    suffix?: string;
  }>;

  return (
    <section className="border-y border-text/10 py-16 md:py-20">
      <Container>
        <div className="grid grid-cols-2 gap-10 md:grid-cols-4">
          {stats.map((stat) => (
            <div key={stat.label}>
              <p className="font-heading text-4xl leading-none text-primary md:text-5xl">
                {stat.number}
                {stat.suffix && (
                  <span className="text-accent">{stat.suffix}</span>
                )}
              </p>
              <p className="mt-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-text/55">
                {stat.label}
              </p>
            </div>
          ))}
        </div>
      </Container>
    </section>
  );
}

function ImageBlock({ data }: { data: Record<string, unknown> }) {
  const image = asString(data.image);
  const altText = asString(data.alt_text);
  const caption = asString(data.caption);

  if (!image) return null;

  return (
    <section className="py-14 md:py-20">
      <Container>
        <img
          src={image}
          alt={altText}
          width={1600}
          height={900}
          loading="lazy"
          className="w-full"
        />
        {caption && (
          <p className="mt-4 text-center font-heading italic text-[14px] text-text/60">
            {caption}
          </p>
        )}
      </Container>
    </section>
  );
}

function GalleryBlock({ data }: { data: Record<string, unknown> }) {
  const images = (data.images || []) as Array<{
    image: string;
    alt_text: string;
    caption?: string;
  }>;
  if (images.length === 0) return null;

  return (
    <section className="py-14 md:py-20">
      <Container>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
          {images.map((img, i) => (
            <figure key={i}>
              <img
                src={img.image}
                alt={img.alt_text}
                width={800}
                height={600}
                loading="lazy"
                className="aspect-[4/3] w-full object-cover"
              />
              {img.caption && (
                <figcaption className="mt-2 font-heading italic text-[13px] text-text/60">
                  {img.caption}
                </figcaption>
              )}
            </figure>
          ))}
        </div>
      </Container>
    </section>
  );
}

function DividerBlock({ data }: { data: Record<string, unknown> }) {
  const style = asString(data.style) || 'line';
  return (
    <div className="py-2">
      <Container>
        {style === 'line' && <hr className="border-text/10" />}
        {style === 'space' && <div className="h-12" />}
        {style === 'gradient' && (
          <div className="h-px bg-gradient-to-r from-transparent via-accent/40 to-transparent" />
        )}
      </Container>
    </div>
  );
}

function RawHTMLBlock({ data }: { data: Record<string, unknown> }) {
  return (
    <section className="py-14">
      <Container narrow>
        <div dangerouslySetInnerHTML={{ __html: asString(data.html) }} />
      </Container>
    </section>
  );
}

const blockComponents: Record<
  string,
  React.ComponentType<{ data: Record<string, unknown> }>
> = {
  hero: HeroBlock,
  text: TextBlock,
  cards: CardsBlock,
  cta: CTABlock,
  stats: StatsBlock,
  image: ImageBlock,
  gallery: GalleryBlock,
  divider: DividerBlock,
  raw_html: RawHTMLBlock,
  // team / projects / testimonials are rendered by the page with full data
  // from getTeamMembers/getProjects/getTestimonials, so skip here.
  team: () => null,
  projects: () => null,
  testimonials: () => null,
};

interface NarrativeContentRendererProps {
  blocks: ContentBlock[];
}

export function NarrativeContentRenderer({
  blocks,
}: NarrativeContentRendererProps) {
  return (
    <>
      {blocks.map((block, index) => {
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
