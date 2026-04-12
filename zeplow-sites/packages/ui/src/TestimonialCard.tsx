import type { Testimonial } from '@zeplow/api';

interface TestimonialCardProps {
  testimonial: Testimonial;
}

export function TestimonialCard({ testimonial }: TestimonialCardProps) {
  return (
    <blockquote className="rounded-2xl border border-text/[0.06] bg-white p-8 md:p-10">
      <p className="text-lg leading-[1.8] text-text/60">
        &ldquo;{testimonial.quote}&rdquo;
      </p>
      <footer className="mt-6 flex items-center gap-4">
        {testimonial.avatar ? (
          <img
            src={testimonial.avatar.thumbnail}
            alt={testimonial.name}
            width={40}
            height={40}
            loading="lazy"
            className="h-10 w-10 rounded-full object-cover"
          />
        ) : (
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/[0.06]">
            <span className="text-sm font-medium text-primary/50">
              {testimonial.name[0]}
            </span>
          </div>
        )}
        <div>
          <p className="text-sm font-medium text-primary">
            {testimonial.name}
          </p>
          {(testimonial.role || testimonial.company) && (
            <p className="text-[12px] text-text/35">
              {[testimonial.role, testimonial.company]
                .filter(Boolean)
                .join(' at ')}
            </p>
          )}
        </div>
      </footer>
    </blockquote>
  );
}
