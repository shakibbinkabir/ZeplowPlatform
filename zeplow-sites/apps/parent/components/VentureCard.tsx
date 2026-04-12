interface VentureCardProps {
  name: string;
  tagline: string;
  description: string;
  detailUrl: string;
  externalUrl: string;
}

export function VentureCard({
  name,
  tagline,
  description,
  detailUrl,
  externalUrl,
}: VentureCardProps) {
  return (
    <div className="group relative rounded-2xl border border-text/[0.06] bg-white p-10 transition-all duration-300 hover:border-text/[0.1] hover:shadow-xl hover:shadow-text/[0.03] md:p-12">
      <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-accent/60">
        Venture
      </p>
      <h3 className="mt-3 font-heading text-2xl font-bold tracking-tight text-primary md:text-3xl">
        {name}
      </h3>
      <p className="mt-1 text-[15px] italic text-text/35">{tagline}</p>
      <p className="mt-5 text-[15px] leading-relaxed text-text/50">
        {description}
      </p>
      <div className="mt-8 flex flex-wrap items-center gap-4">
        <a
          href={detailUrl}
          className="inline-flex items-center rounded-full bg-primary px-6 py-2.5 text-[13px] font-medium tracking-wide text-white transition-all duration-300 hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/20"
        >
          Learn More
        </a>
        <a
          href={externalUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-1.5 text-[13px] font-medium text-text/35 transition-colors hover:text-primary"
        >
          Visit Site
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
              d="M7 17L17 7M17 7H7M17 7v10"
            />
          </svg>
        </a>
      </div>
    </div>
  );
}
