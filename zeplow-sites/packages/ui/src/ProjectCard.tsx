import type { ProjectListItem } from '@zeplow/api';

interface ProjectCardProps {
  project: ProjectListItem;
  siteKey: string;
}

export function ProjectCard({ project }: ProjectCardProps) {
  const image = project.images[0];
  return (
    <div className="group">
      {image ? (
        <div className="overflow-hidden rounded-2xl bg-text/[0.03]">
          <img
            src={image.medium}
            alt={image.alt || project.title}
            width={800}
            height={600}
            loading="lazy"
            className="aspect-[4/3] w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
          />
        </div>
      ) : (
        <div className="flex aspect-[4/3] items-center justify-center rounded-2xl bg-primary/[0.04]">
          <span className="font-heading text-2xl text-primary/20">
            {project.title[0]}
          </span>
        </div>
      )}
      <div className="mt-5">
        <div className="flex items-center gap-3">
          <h3 className="font-heading text-lg font-bold tracking-tight text-primary">
            {project.title}
          </h3>
          {project.industry && (
            <span className="rounded-full bg-primary/[0.06] px-2.5 py-0.5 text-[11px] font-medium uppercase tracking-wider text-primary/60">
              {project.industry}
            </span>
          )}
        </div>
        <p className="mt-2 text-[15px] leading-relaxed text-text/45">
          {project.one_liner}
        </p>
      </div>
    </div>
  );
}
