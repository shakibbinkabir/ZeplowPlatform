import React from 'react';

interface AuditCTAProps {
  heading?: string;
  body?: string;
  buttonText?: string;
  buttonUrl?: string;
  variant?: 'dark' | 'light';
  className?: string;
}

const DEFAULT_HEADING = 'Your first step is a Systems Audit.';
const DEFAULT_BODY =
  'We diagnose the bottleneck, map the architecture, and show you the ROI — before a single line of code is written. Engagements start at $3,000. If that filters you out, this isn’t the right fit.';
const DEFAULT_BUTTON_TEXT = 'Book a Systems Audit';
const DEFAULT_BUTTON_URL = '/contact';

export function AuditCTA({
  heading = DEFAULT_HEADING,
  body = DEFAULT_BODY,
  buttonText = DEFAULT_BUTTON_TEXT,
  buttonUrl = DEFAULT_BUTTON_URL,
  variant = 'dark',
  className = '',
}: AuditCTAProps) {
  const isDark = variant === 'dark';

  return (
    <aside
      className={`relative overflow-hidden rounded-sm ${
        isDark
          ? 'bg-primary text-background'
          : 'bg-white text-primary border border-text/10'
      } ${className}`}
    >
      {/* Schematic corner brackets — keeps the "blueprint" aesthetic */}
      <span
        aria-hidden
        className={`pointer-events-none absolute left-3 top-3 h-3 w-3 border-l border-t ${
          isDark ? 'border-accent' : 'border-primary/40'
        }`}
      />
      <span
        aria-hidden
        className={`pointer-events-none absolute right-3 top-3 h-3 w-3 border-r border-t ${
          isDark ? 'border-accent' : 'border-primary/40'
        }`}
      />
      <span
        aria-hidden
        className={`pointer-events-none absolute bottom-3 left-3 h-3 w-3 border-b border-l ${
          isDark ? 'border-accent' : 'border-primary/40'
        }`}
      />
      <span
        aria-hidden
        className={`pointer-events-none absolute bottom-3 right-3 h-3 w-3 border-b border-r ${
          isDark ? 'border-accent' : 'border-primary/40'
        }`}
      />

      <div className="px-8 py-12 md:px-12 md:py-16">
        <p
          className={`font-mono text-[11px] uppercase tracking-[0.18em] ${
            isDark ? 'text-accent' : 'text-accent'
          }`}
        >
          // engagement
        </p>

        <h3
          className={`mt-3 font-heading text-2xl font-bold leading-tight md:text-3xl ${
            isDark ? 'text-background' : 'text-primary'
          }`}
        >
          {heading}
        </h3>

        <p
          className={`mt-5 max-w-2xl text-[15px] leading-[1.8] ${
            isDark ? 'text-background/75' : 'text-text/70'
          }`}
        >
          {body}
        </p>

        <a
          href={buttonUrl}
          className={`mt-8 inline-flex items-center gap-2 rounded-full px-7 py-3 font-mono text-sm font-medium transition-colors ${
            isDark
              ? 'bg-accent text-primary hover:bg-accent/90'
              : 'bg-primary text-background hover:bg-primary/90'
          }`}
        >
          {buttonText}
          <span aria-hidden>→</span>
        </a>
      </div>
    </aside>
  );
}
