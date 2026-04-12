interface SectionHeadingProps {
  title: string;
  subtitle?: string;
  className?: string;
  align?: 'left' | 'center';
}

export function SectionHeading({
  title,
  subtitle,
  className = '',
  align = 'left',
}: SectionHeadingProps) {
  return (
    <div
      className={`mb-16 ${align === 'center' ? 'text-center' : ''} ${className}`}
    >
      <h2 className="font-heading text-3xl font-bold tracking-tight text-primary md:text-4xl lg:text-[2.75rem]">
        {title}
      </h2>
      {subtitle && (
        <p
          className={`mt-5 text-lg leading-relaxed text-text/50 ${
            align === 'center' ? 'mx-auto max-w-xl' : 'max-w-2xl'
          }`}
        >
          {subtitle}
        </p>
      )}
    </div>
  );
}
