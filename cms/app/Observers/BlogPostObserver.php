<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\SyncService;

class BlogPostObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(BlogPost $post): void
    {
        if ($post->is_published) {
            $this->syncService->syncContent(
                siteKey: $post->site->key,
                contentType: 'blog_post',
                slug: $post->slug,
                data: [
                    'title' => $post->title,
                    'excerpt' => $post->excerpt,
                    'body' => $post->body,
                    'cover_image' => $post->getFirstMediaUrl('cover_image'),
                    'tags' => $post->tags,
                    'author' => $post->author,
                    'seo' => [
                        'title' => $post->seo_title ?? $post->title,
                        'description' => $post->seo_description ?? $post->excerpt,
                    ],
                ],
                publishedAt: $post->published_at?->toISOString(),
            );
        } elseif ($post->isDirty('is_published') && !$post->is_published) {
            $this->syncService->deleteContent(
                siteKey: $post->site->key,
                contentType: 'blog_post',
                slug: $post->slug,
            );
        }
    }

    public function deleted(BlogPost $post): void
    {
        $this->syncService->deleteContent(
            siteKey: $post->site->key,
            contentType: 'blog_post',
            slug: $post->slug,
        );
    }
}
