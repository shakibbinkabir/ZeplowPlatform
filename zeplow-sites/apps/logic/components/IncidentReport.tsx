'use client';

import React from 'react';
import { motion } from 'framer-motion';
import { getImageUrl } from '@zeplow/api';
import type { Project } from '@zeplow/api';

interface IncidentReportProps {
  project: Project;
}

const fadeUpTransition = { duration: 0.5, ease: [0.22, 0.61, 0.36, 1] as const };

function ReportRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="grid grid-cols-[120px_1fr] gap-4 py-1.5 text-sm md:text-[15px]">
      <span className="font-mono text-[11px] uppercase tracking-[0.14em] text-text/40">
        {label}
      </span>
      <span className="text-text">{value}</span>
    </div>
  );
}

function SectionHeader({ index, title }: { index: string; title: string }) {
  return (
    <div className="flex items-baseline gap-3 border-b border-text/10 pb-3">
      <span className="font-mono text-xs text-accent">{index}</span>
      <h2 className="font-heading text-lg font-bold uppercase tracking-[0.08em] text-primary">
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
    <motion.section
      initial={{ opacity: 0, y: 16 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: '-80px' }}
      transition={fadeUpTransition}
      className="py-10"
    >
      <SectionHeader index={index} title={title} />
      <div className="mt-6">{children}</div>
    </motion.section>
  );
}

export function IncidentReport({ project }: IncidentReportProps) {
  const now = new Date().toISOString().slice(0, 10);
  const incidentId = `ZL-${String(project.id).padStart(4, '0')}`;

  return (
    <article className="mx-auto max-w-3xl px-6 py-24 lg:px-8">
      {/* Header */}
      <motion.header
        initial={{ opacity: 0, y: 16 }}
        animate={{ opacity: 1, y: 0 }}
        transition={fadeUpTransition}
        className="border-b border-text/15 pb-8"
      >
        <div className="flex items-center justify-between font-mono text-[11px] uppercase tracking-[0.14em] text-text/40">
          <span>Incident Report</span>
          <span>
            {incidentId} · {now}
          </span>
        </div>

        <h1 className="mt-6 font-heading text-3xl font-bold leading-tight text-primary md:text-4xl">
          {project.title}
        </h1>
        <p className="mt-3 text-base text-text/70 md:text-lg">
          {project.one_liner}
        </p>

        <div className="mt-8 space-y-1">
          <ReportRow label="Subject" value={project.title} />
          {project.client_name && (
            <ReportRow label="Client" value={project.client_name} />
          )}
          {project.industry && (
            <ReportRow label="Industry" value={project.industry} />
          )}
          {project.tags.length > 0 && (
            <div className="grid grid-cols-[120px_1fr] gap-4 py-1.5">
              <span className="font-mono text-[11px] uppercase tracking-[0.14em] text-text/40">
                Tags
              </span>
              <span className="flex flex-wrap gap-2">
                {project.tags.map((tag) => (
                  <span
                    key={tag}
                    className="font-mono text-[11px] text-text/60"
                  >
                    #{tag}
                  </span>
                ))}
              </span>
            </div>
          )}
        </div>
      </motion.header>

      {/* 01 · BOTTLENECK */}
      {project.challenge && (
        <Section index="01" title="Bottleneck">
          <p className="whitespace-pre-line text-[15px] leading-[1.8] text-text/80">
            {project.challenge}
          </p>
        </Section>
      )}

      {/* 02 · SOLUTION + TECH STACK */}
      {(project.solution || project.tech_stack.length > 0) && (
        <Section index="02" title="Solution">
          {project.solution && (
            <p className="whitespace-pre-line text-[15px] leading-[1.8] text-text/80">
              {project.solution}
            </p>
          )}

          {project.tech_stack.length > 0 && (
            <div className="mt-8">
              <p className="font-mono text-[11px] uppercase tracking-[0.14em] text-text/40">
                Tech stack
              </p>
              <ul className="mt-3 flex flex-wrap gap-2">
                {project.tech_stack.map((tech) => (
                  <li
                    key={tech}
                    className="rounded-full border border-primary/15 bg-primary/5 px-3 py-1 font-mono text-xs text-primary"
                  >
                    {tech}
                  </li>
                ))}
              </ul>
            </div>
          )}
        </Section>
      )}

      {/* 03 · OUTCOME */}
      {project.outcome && (
        <Section index="03" title="Outcome">
          <p className="whitespace-pre-line text-[15px] leading-[1.8] text-text/80">
            {project.outcome}
          </p>
        </Section>
      )}

      {/* 04 · EVIDENCE */}
      {project.images.length > 0 && (
        <Section index="04" title="Evidence">
          <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
            {project.images.map((image, i) => {
              const src = getImageUrl(image.medium ?? image.original, 'medium');
              return (
                <figure
                  key={src}
                  className="overflow-hidden rounded-sm border border-text/10 bg-white"
                >
                  <img
                    src={src}
                    alt={image.alt || `${project.title} — screenshot ${i + 1}`}
                    width={800}
                    height={600}
                    loading={i === 0 ? 'eager' : 'lazy'}
                    className="block h-auto w-full"
                  />
                </figure>
              );
            })}
          </div>
        </Section>
      )}

      {/* Footer / Live link */}
      <motion.footer
        initial={{ opacity: 0 }}
        whileInView={{ opacity: 1 }}
        viewport={{ once: true, margin: '-40px' }}
        transition={{ duration: 0.6, ease: 'easeOut' }}
        className="mt-12 border-t border-text/15 pt-8"
      >
        <div className="flex items-center justify-between font-mono text-[11px] uppercase tracking-[0.14em] text-text/40">
          <span>End of report</span>
          <span>{incidentId}</span>
        </div>

        {project.url && (
          <a
            href={project.url}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-2 rounded-full bg-accent px-6 py-3 font-mono text-sm font-medium text-primary transition-colors hover:bg-accent/90"
          >
            View Live System
            <span aria-hidden>→</span>
          </a>
        )}
      </motion.footer>
    </article>
  );
}
