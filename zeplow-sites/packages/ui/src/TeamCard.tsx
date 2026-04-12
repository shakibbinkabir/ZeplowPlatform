import type { TeamMember } from '@zeplow/api';

interface TeamCardProps {
  member: TeamMember;
}

export function TeamCard({ member }: TeamCardProps) {
  return (
    <div className="group flex gap-6 md:gap-8">
      {member.photo ? (
        <div className="shrink-0 overflow-hidden rounded-2xl">
          <img
            src={member.photo.medium}
            alt={member.name}
            width={300}
            height={300}
            loading="lazy"
            className="h-28 w-28 object-cover md:h-36 md:w-36"
          />
        </div>
      ) : (
        <div className="flex h-28 w-28 shrink-0 items-center justify-center rounded-2xl bg-primary/[0.04] md:h-36 md:w-36">
          <span className="font-heading text-3xl text-primary/20">
            {member.name[0]}
          </span>
        </div>
      )}
      <div className="flex flex-col justify-center">
        <h3 className="font-heading text-xl font-bold tracking-tight text-primary">
          {member.name}
        </h3>
        <p className="mt-1 text-[13px] font-medium uppercase tracking-[0.08em] text-accent/70">
          {member.role}
        </p>
        {member.bio && (
          <p className="mt-3 text-[15px] leading-relaxed text-text/45">
            {member.bio}
          </p>
        )}
        {member.linkedin && (
          <a
            href={member.linkedin}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-3 inline-flex items-center gap-1.5 text-[13px] font-medium text-text/30 transition-colors hover:text-primary"
          >
            LinkedIn
            <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M7 17L17 7M17 7H7M17 7v10" />
            </svg>
          </a>
        )}
      </div>
    </div>
  );
}
