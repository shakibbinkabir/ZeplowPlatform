<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\SyncService;

class PageObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(Page $page): void
    {
        if ($page->is_published) {
            $this->syncService->syncContent(
                siteKey: $page->site->key,
                contentType: 'page',
                slug: $page->slug,
                data: [
                    'title' => $page->title,
                    'template' => $page->template,
                    'content' => $page->content,
                    'seo' => [
                        'title' => $page->seo_title ?? $page->title,
                        'description' => $page->seo_description,
                        'og_image' => $page->getFirstMediaUrl('og_image'),
                    ],
                    'sort_order' => $page->sort_order,
                ],
                publishedAt: $page->published_at?->toISOString(),
            );
        } elseif ($page->isDirty('is_published') && !$page->is_published) {
            $this->syncService->deleteContent(
                siteKey: $page->site->key,
                contentType: 'page',
                slug: $page->slug,
            );
        }
    }

    public function deleted(Page $page): void
    {
        $this->syncService->deleteContent(
            siteKey: $page->site->key,
            contentType: 'page',
            slug: $page->slug,
        );
    }
}
