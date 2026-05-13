import React from 'react';
import { getImageUrl } from '@zeplow/api';
import type { Project } from '@zeplow/api';

interface FeatureStoryProps {
  project: Project;
}

// Editorial case study layout — "Feature Story" format per Narrative PRD §8.5.
// Maps the Narrative Arc onto Project fields:
//   challenge → The Villain
//   solution  → The Weapon
//   outcome   → The Victory

function MetaRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="grid grid-cols-[110px_1fr] gap-4 py-1.5 text-[14px] md:text-[15px]">
      <span className="text-[11px] font-semibold uppercase tracking-[0.18em] text-text/40">
        {label}
      </span>
      <span className="text-text">{value}</span>
    </div>
  );
}

function SectionHeader({ index, title }: { index: string; title: string }) {
  return (
    <div className="flex items-baseline gap-4 border-b border-text/15 pb-4">
      <span className="font-heading text-[15px] font-bold text-accent">
        {index}
      </span>
      <h2 className="font-heading text-2xl leading-tight text-primary md:text-3xl">
        {title}
      </h2>
    </div>
  );
}

function Section({
  index,
  title,
  children,
}: {
  index: string;
  title: string;
  children: React.ReactNode;
}) {
  return (
    <section className="py-12 md:py-14">
      <SectionHeader index={index} title={title} />
      <div className="mt-7">{children}</div>
    </section>
  );
}

export function FeatureStory({ project }: FeatureStoryProps) {
  const issueDate = new Date().toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric',
  });

  return (
    <article className="mx-auto max-w-3xl px-6 py-24 lg:px-8">
      {/* Editorial header */}
      <header className="border-b border-text/15 pb-10">
        <div className="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-text/40">
          <span>Feature Story</span>
          <span>Issue · {issueDate}</span>
        </div>

        <h1 className="mt-8 font-heading text-4xl leading-[1.08] tracking-tight text-primary md:text-5xl lg:text-6xl">
          {project.title}
        </h1>

        <p className="mt-6 font-heading text-xl italic leading-snug text-text/65 md:text-2xl">
          {project.one_liner}
        </p>

        <div className="mt-10 space-y-1">
          {project.client_name && (
            <MetaRow label="Client" value={project.client_name} />
          )}
          {project.industry && (
            <MetaRow label="Industry" value={project.industry} />
          )}
          {project.tags.length > 0 && (
            <div className="grid grid-cols-[110px_1fr] gap-4 py-1.5">
              <span className="text-[11px] font-semibold uppercase tracking-[0.18em] text-text/40">
                Tags
              </span>
              <span className="flex flex-wrap gap-x-3 gap-y-1">
                {project.tags.map((tag) => (
                  <span
                    key={tag}
                    className="font-heading italic text-[14px] text-text/60"
                  >
                    #{tag}
                  </span>
                ))}
              </span>
            </div>
          )}
        </div>
      </header>

      {/* Hero image — full bleed within the article column */}
      {project.images[0] && (
        <figure className="mt-12">
          <img
            src={getImageUrl(project.images[0].large ?? project.images[0].original, 'large')}
            alt={project.images[0].alt || project.title}
            width={1200}
            height={675}
            loading="eager"
            className="w-full"
          />
        </figure>
      )}

      {/* 01 · The Villain */}
      {project.challenge && (
        <Section index="01" title="The Villain">
          <p className="whitespace-pre-line text-[16px] leading-[1.9] text-text/80">
            {project.challenge}
          </p>
        </Section>
      )}

      {/* 02 · The Weapon */}
      {project.solution && (
        <Section index="02" title="The Weapon">
          <p className="whitespace-pre-line text-[16px] leading-[1.9] text-text/80">
            {project.solution}
          </p>
        </Section>
      )}

      {/* 03 · The Victory */}
      {project.outcome && (
        <Section index="03" title="The Victory">
          <p className="whitespace-pre-line text-[16px] leading-[1.9] text-text/80">
            {project.outcome}
          </p>
        </Section>
      )}

      {/* Supporting images */}
      {project.images.length > 1 && (
        <Section index="04" title="From the Story">
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
            {project.images.slice(1).map((image, i) => {
              const src = getImageUrl(image.medium ?? image.original, 'medium');
              return (
                <figure key={src}>
                  <img
                    src={src}
                    alt={image.alt || `${project.title} — image ${i + 2}`}
                    width={800}
                    height={600}
                    loading="lazy"
                    className="block h-auto w-full"
                  />
                </figure>
              );
            })}
          </div>
        </Section>
      )}

      {/* Footer */}
      <footer className="mt-14 border-t border-text/15 pt-10">
        <div className="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-text/40">
          <span>End of feature</span>
          <span>Zeplow Narrative</span>
        </div>

        {project.url && (
          <a
            href={project.url}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-8 inline-flex items-center gap-2 bg-accent px-7 py-3.5 text-[15px] font-medium text-primary transition-colors hover:bg-accent/90"
          >
            Visit the brand
            <span aria-hidden>→</span>
          </a>
        )}
      </footer>
    </article>
  );
}
