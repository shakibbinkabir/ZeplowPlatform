'use client';

import { motion, useInView, useMotionValue, useSpring } from 'framer-motion';
import { useEffect, useRef } from 'react';
import type { ContentBlock } from '@zeplow/api';
import { Container } from '@zeplow/ui';

function asString(value: unknown): string {
  return typeof value === 'string' ? value : '';
}

// Shared motion variants — restrained, "data transition" feel per PRD §7.5.
const fadeUp = {
  hidden: { opacity: 0, y: 12 },
  visible: { opacity: 1, y: 0 },
};

const fadeUpTransition = { duration: 0.5, ease: [0.22, 0.61, 0.36, 1] as const };

// Stagger container for card grids. 60ms delay between children — slow enough
// to read as "data populating," fast enough not to feel laggy.
const staggerContainer = {
  hidden: {},
  visible: {
    transition: { staggerChildren: 0.06, delayChildren: 0.05 },
  },
};

// Schematic corner brackets — used by hero and CTA blocks to reinforce the
// "blueprint" aesthetic per Logic_Site_PRD.md §2.4.
function CornerBrackets({ tone }: { tone: 'accent' | 'muted' }) {
  const color = tone === 'accent' ? 'border-accent' : 'border-primary/40';
  return (
    <>
      <span
        aria-hidden
        className={`pointer-events-none absolute left-4 top-4 h-3 w-3 border-l border-t ${color}`}
      />
      <span
        aria-hidden
        className={`pointer-events-none absolute right-4 top-4 h-3 w-3 border-r border-t ${color}`}
      />
      <span
        aria-hidden
        className={`pointer-events-none absolute bottom-4 left-4 h-3 w-3 border-b border-l ${color}`}
      />
      <span
        aria-hidden
        className={`pointer-events-none absolute bottom-4 right-4 h-3 w-3 border-b border-r ${color}`}
      />
    </>
  );
}

function HeroBlock({ data }: { data: Record<string, unknown> }) {
  const heading = asString(data.heading);
  const subheading = asString(data.subheading);
  const buttonText = asString(data.button_text);
  const buttonUrl = asString(data.button_url);

  return (
    <section className="relative isolate bg-primary pt-32 pb-24 md:pt-40 md:pb-32">
      <CornerBrackets tone="accent" />

      {/* faint blueprint grid — slow drift in on mount for a "rendering" feel */}
      <motion.div
        aria-hidden
        initial={{ opacity: 0 }}
        animate={{ opacity: 0.07 }}
        transition={{ duration: 1.2, ease: 'easeOut' }}
        className="pointer-events-none absolute inset-0"
        style={{
          backgroundImage:
            'linear-gradient(to right, #00b894 1px, transparent 1px), linear-gradient(to bottom, #00b894 1px, transparent 1px)',
          backgroundSize: '48px 48px',
        }}
      />

      <Container className="relative">
        <motion.div
          initial="hidden"
          animate="visible"
          variants={staggerContainer}
        >
          <motion.p
            variants={fadeUp}
            transition={fadeUpTransition}
            className="font-mono text-[11px] uppercase tracking-[0.18em] text-accent"
          >
            // zeplow.logic
          </motion.p>
          <motion.h1
            variants={fadeUp}
            transition={fadeUpTransition}
            className="mt-6 max-w-4xl font-heading text-4xl font-bold leading-[1.1] tracking-tight text-background md:text-5xl lg:text-6xl"
          >
            {heading}
          </motion.h1>
          {subheading && (
            <motion.p
              variants={fadeUp}
              transition={fadeUpTransition}
              className="mt-6 max-w-2xl text-lg leading-relaxed text-background/60 md:text-xl"
            >
              {subheading}
            </motion.p>
          )}
          {buttonText && buttonUrl && (
            <motion.div
              variants={fadeUp}
              transition={fadeUpTransition}
              className="mt-10"
            >
              <a
                href={buttonUrl}
                className="inline-flex items-center gap-2 rounded-sm bg-accent px-7 py-3.5 font-mono text-sm font-medium text-primary transition-colors hover:bg-accent/90"
              >
                {buttonText}
                <span aria-hidden>→</span>
              </a>
            </motion.div>
          )}
        </motion.div>
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
        <motion.div
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: '-80px' }}
          variants={fadeUp}
          transition={fadeUpTransition}
        >
          {heading && (
            <div className="mb-8 flex items-baseline gap-3 border-b border-text/10 pb-3">
              <span className="font-mono text-[11px] uppercase tracking-[0.18em] text-accent">
                //
              </span>
              <h2 className="font-heading text-2xl font-bold tracking-tight text-primary md:text-3xl">
                {heading}
              </h2>
            </div>
          )}
          <div
            className="prose-custom text-[17px] leading-[1.85] text-text/75"
            dangerouslySetInnerHTML={{ __html: body }}
          />
        </motion.div>
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
  }>;

  // Pick column count: 3 cards → 3 cols; 4 or 8 → 4 cols; default → 2 cols
  const gridCols =
    cards.length === 3
      ? 'md:grid-cols-3'
      : cards.length === 4 || cards.length === 8
        ? 'md:grid-cols-2 lg:grid-cols-4'
        : 'md:grid-cols-2';

  return (
    <section className="py-20 md:py-28">
      <Container>
        {heading && (
          <motion.div
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, margin: '-80px' }}
            variants={fadeUp}
            transition={fadeUpTransition}
            className="mb-12 flex items-baseline gap-3 border-b border-text/10 pb-3"
          >
            <span className="font-mono text-[11px] uppercase tracking-[0.18em] text-accent">
              //
            </span>
            <h2 className="font-heading text-2xl font-bold tracking-tight text-primary md:text-3xl">
              {heading}
            </h2>
          </motion.div>
        )}
        <motion.div
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: '-60px' }}
          variants={staggerContainer}
          className={`grid gap-4 ${gridCols}`}
        >
          {cards.map((card, index) => (
            <motion.article
              key={card.title}
              variants={fadeUp}
              transition={fadeUpTransition}
              className="group relative border border-text/10 bg-white p-6 transition-colors duration-200 hover:border-accent md:p-8"
            >
              <span className="font-mono text-[11px] text-text/30">
                {String(index + 1).padStart(2, '0')}
              </span>
              <h3 className="mt-2 font-heading text-base font-bold uppercase tracking-[0.04em] text-primary md:text-lg">
                {card.title}
              </h3>
              <p className="mt-3 text-[14px] leading-[1.7] text-text/60">
                {card.description}
              </p>
              {card.url && (
                <a
                  href={card.url}
                  className="mt-5 inline-flex items-center gap-1.5 font-mono text-[12px] text-accent transition-colors hover:text-primary"
                >
                  read more
                  <span aria-hidden>→</span>
                </a>
              )}
            </motion.article>
          ))}
        </motion.div>
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
    <section className="py-20 md:py-28">
      <Container narrow>
        <motion.aside
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: '-80px' }}
          transition={fadeUpTransition}
          className="relative overflow-hidden bg-primary px-8 py-12 text-background md:px-12 md:py-16"
        >
          <CornerBrackets tone="accent" />
          <p className="font-mono text-[11px] uppercase tracking-[0.18em] text-accent">
            // next step
          </p>
          <h2 className="mt-3 max-w-2xl font-heading text-2xl font-bold leading-tight text-background md:text-3xl">
            {heading}
          </h2>
          {subheading && (
            <p className="mt-5 max-w-xl text-[15px] leading-[1.8] text-background/70">
              {subheading}
            </p>
          )}
          {buttonText && buttonUrl && (
            <a
              href={buttonUrl}
              className="mt-8 inline-flex items-center gap-2 rounded-sm bg-accent px-7 py-3 font-mono text-sm font-medium text-primary transition-colors hover:bg-accent/90"
            >
              {buttonText}
              <span aria-hidden>→</span>
            </a>
          )}
        </motion.aside>
      </Container>
    </section>
  );
}

// Stat value with count-up — parses a leading integer, animates from 0 to it,
// preserves any non-numeric suffix (k, +, %, etc.) verbatim. If no leading
// integer (e.g. "99.9%"), shows the value as-is without animation.
function AnimatedStatValue({ value }: { value: string }) {
  const ref = useRef<HTMLSpanElement>(null);
  const inView = useInView(ref, { once: true, margin: '-60px' });

  const match = value.match(/^(-?\d+)(.*)$/);
  const target = match ? parseInt(match[1], 10) : null;
  const suffix = match ? match[2] : '';

  const mv = useMotionValue(0);
  const spring = useSpring(mv, { duration: 1200, bounce: 0 });

  useEffect(() => {
    if (!inView || target === null) return;
    mv.set(target);
  }, [inView, target, mv]);

  useEffect(() => {
    if (target === null) return;
    return spring.on('change', (latest) => {
      if (ref.current) {
        ref.current.textContent = `${Math.round(latest)}${suffix}`;
      }
    });
  }, [spring, suffix, target]);

  // Non-numeric values render as-is without animation
  if (target === null) {
    return <span ref={ref}>{value}</span>;
  }

  return <span ref={ref}>0{suffix}</span>;
}

function StatsBlock({ data }: { data: Record<string, unknown> }) {
  const stats = (data.stats || []) as Array<{
    label: string;
    value: string;
  }>;

  return (
    <section className="border-y border-text/10 py-16 md:py-20">
      <Container>
        <motion.div
          initial="hidden"
          whileInView="visible"
          viewport={{ once: true, margin: '-60px' }}
          variants={staggerContainer}
          className="grid grid-cols-2 gap-8 md:grid-cols-4"
        >
          {stats.map((stat) => (
            <motion.div
              key={stat.label}
              variants={fadeUp}
              transition={fadeUpTransition}
            >
              <p className="font-mono text-3xl font-bold tracking-tight text-accent md:text-4xl">
                <AnimatedStatValue value={stat.value} />
              </p>
              <p className="mt-2 font-mono text-[11px] uppercase tracking-[0.14em] text-text/40">
                {stat.label}
              </p>
            </motion.div>
          ))}
        </motion.div>
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
    <section className="py-16">
      <Container>
        <img
          src={image}
          alt={altText}
          width={1200}
          height={600}
          loading="lazy"
          className="w-full"
        />
        {caption && (
          <p className="mt-3 text-center font-mono text-[12px] text-text/40">
            {caption}
          </p>
        )}
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
    <section className="py-16">
      <Container>
        <div dangerouslySetInnerHTML={{ __html: asString(data.html) }} />
      </Container>
    </section>
  );
}

function GalleryBlock({ data }: { data: Record<string, unknown> }) {
  const images = (data.images || []) as Array<{ url: string; alt: string }>;
  if (images.length === 0) return null;

  return (
    <section className="py-16">
      <Container>
        <div className="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
          {images.map((img, i) => (
            <img
              key={i}
              src={img.url}
              alt={img.alt}
              width={800}
              height={600}
              loading="lazy"
              className="aspect-[4/3] w-full object-cover"
            />
          ))}
        </div>
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
  // team / projects / testimonials are rendered by the page outside this
  // component (with full data from getTeamMembers/getProjects/getTestimonials),
  // so we skip block-level rendering for those types.
  team: () => null,
  projects: () => null,
  testimonials: () => null,
};

interface LogicContentRendererProps {
  blocks: ContentBlock[];
}

export function LogicContentRenderer({ blocks }: LogicContentRendererProps) {
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
