import type { BlogPostListItem } from '@zeplow/api';

interface BlogCardProps {
  post: BlogPostListItem;
  basePath: string;
}

export function BlogCard({ post, basePath }: BlogCardProps) {
  return (
    <a
      href={`${basePath}/${post.slug}`}
      className="group block"
    >
      {post.cover_image ? (
        <div className="overflow-hidden rounded-2xl bg-text/[0.03]">
          <img
            src={post.cover_image.medium}
            alt={post.title}
            width={800}
            height={600}
            loading="lazy"
            className="aspect-[4/3] w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
          />
        </div>
      ) : (
        <div className="flex aspect-[4/3] items-center justify-center rounded-2xl bg-primary/[0.04]">
          <span className="font-heading text-3xl text-primary/15">Z</span>
        </div>
      )}
      <div className="mt-5">
        {post.tags.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {post.tags.map((tag) => (
              <span
                key={tag}
                className="text-[11px] font-medium uppercase tracking-[0.1em] text-accent/70"
              >
                {tag}
              </span>
            ))}
          </div>
        )}
        <h3 className="mt-2 font-heading text-lg font-bold tracking-tight text-primary transition-colors group-hover:text-accent">
          {post.title}
        </h3>
        {post.excerpt && (
          <p className="mt-2 line-clamp-2 text-[15px] leading-relaxed text-text/45">
            {post.excerpt}
          </p>
        )}
        <div className="mt-4 flex items-center gap-2 text-[12px] text-text/30">
          {post.author && <span>{post.author}</span>}
          {post.author && post.published_at && (
            <span className="text-text/15">/</span>
          )}
          {post.published_at && (
            <time dateTime={post.published_at}>
              {new Date(post.published_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </time>
          )}
        </div>
      </div>
    </a>
  );
}
