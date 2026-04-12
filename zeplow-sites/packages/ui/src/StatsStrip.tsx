interface Stat {
  label: string;
  value: string;
}

interface StatsStripProps {
  stats: Stat[];
  className?: string;
}

export function StatsStrip({ stats, className = '' }: StatsStripProps) {
  return (
    <div className={`grid grid-cols-2 gap-12 md:grid-cols-4 ${className}`}>
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
  );
}
