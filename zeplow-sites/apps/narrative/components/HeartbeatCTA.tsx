import React from 'react';

interface HeartbeatCTAProps {
  heading?: string;
  body?: string;
  buttonText?: string;
  buttonUrl?: string;
  variant?: 'dark' | 'light';
  className?: string;
}

const DEFAULT_HEADING = 'Every brand has a heartbeat.';
const DEFAULT_BODY =
  "Some are strong. Some are fading. Most haven't been checked. Book a Heartbeat Review — we'll tell you where yours stands.";
const DEFAULT_BUTTON_TEXT = 'Book a Heartbeat Review';
const DEFAULT_BUTTON_URL = '/contact';

export function HeartbeatCTA({
  heading = DEFAULT_HEADING,
  body = DEFAULT_BODY,
  buttonText = DEFAULT_BUTTON_TEXT,
  buttonUrl = DEFAULT_BUTTON_URL,
  variant = 'dark',
  className = '',
}: HeartbeatCTAProps) {
  const isDark = variant === 'dark';

  return (
    <aside
      className={`relative overflow-hidden rounded-sm ${
        isDark
          ? 'bg-primary text-background'
          : 'bg-white text-text border border-text/10'
      } ${className}`}
    >
      <div className="relative px-8 py-14 md:px-14 md:py-20">
        <p
          className={`flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.22em] ${
            isDark ? 'text-accent' : 'text-accent'
          }`}
        >
          <span aria-hidden className="inline-block h-px w-8 bg-accent" />
          A note to the reader
        </p>

        <h3
          className={`mt-5 font-heading text-3xl leading-[1.15] tracking-tight md:text-4xl lg:text-[2.75rem] ${
            isDark ? 'text-background' : 'text-primary'
          }`}
        >
          {heading}
        </h3>

        <p
          className={`mt-6 max-w-xl text-[15px] leading-[1.9] ${
            isDark ? 'text-background/75' : 'text-text/70'
          }`}
        >
          {body}
        </p>

        <a
          href={buttonUrl}
          className="mt-9 inline-flex items-center gap-2 bg-accent px-7 py-3.5 text-[15px] font-medium text-primary transition-colors hover:bg-accent/90"
        >
          {buttonText}
          <span aria-hidden>→</span>
        </a>
      </div>
    </aside>
  );
}
