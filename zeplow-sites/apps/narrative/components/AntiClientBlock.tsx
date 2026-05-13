import React from 'react';

interface AntiClientBlockProps {
  heading?: string;
  statements?: string[];
  kicker?: string;
  className?: string;
}

const DEFAULT_HEADING = "Brands we don't work with.";
const DEFAULT_STATEMENTS = [
  "We don't work with brands that chase trends.",
  "We don't work with founders who hide behind logos.",
  "We don't work with businesses that want ads, not truth.",
];
const DEFAULT_KICKER =
  "If this disqualifies you, good. If it doesn't — apply.";

export function AntiClientBlock({
  heading = DEFAULT_HEADING,
  statements = DEFAULT_STATEMENTS,
  kicker = DEFAULT_KICKER,
  className = '',
}: AntiClientBlockProps) {
  return (
    <section
      className={`relative border-y border-text/10 bg-background ${className}`}
    >
      <div className="mx-auto max-w-4xl px-6 py-20 md:px-8 md:py-28">
        <p className="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-accent">
          <span aria-hidden className="inline-block h-px w-8 bg-accent" />
          The filter
        </p>

        <h2 className="mt-5 font-heading text-3xl leading-[1.1] tracking-tight text-primary md:text-4xl lg:text-5xl">
          {heading}
        </h2>

        <ul className="mt-12 space-y-6 md:space-y-8">
          {statements.map((statement, index) => (
            <li
              key={index}
              className="flex items-start gap-5 border-l border-accent/60 pl-5 md:pl-7"
            >
              <span
                aria-hidden
                className="mt-[7px] font-body text-[11px] font-semibold tracking-[0.2em] text-accent"
              >
                {String(index + 1).padStart(2, '0')}
              </span>
              <p className="font-heading text-xl leading-snug text-primary md:text-2xl">
                {statement}
              </p>
            </li>
          ))}
        </ul>

        <div className="mt-14 border-t border-text/10 pt-10 md:mt-20 md:pt-12">
          <p className="font-heading text-xl italic leading-snug text-text/80 md:text-2xl">
            {kicker}
          </p>
        </div>
      </div>
    </section>
  );
}
