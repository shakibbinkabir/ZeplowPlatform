<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\SyncService;

class ProjectObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(Project $project): void
    {
        if ($project->is_published) {
            $this->syncService->syncContent(
                siteKey: $project->site->key,
                contentType: 'project',
                slug: $project->slug,
                data: [
                    'title' => $project->title,
                    'one_liner' => $project->one_liner,
                    'client_name' => $project->client_name,
                    'industry' => $project->industry,
                    'url' => $project->url,
                    'challenge' => $project->challenge,
                    'solution' => $project->solution,
                    'outcome' => $project->outcome,
                    'tech_stack' => $project->tech_stack,
                    'images' => $project->getMedia('images')->map(function ($media) {
                        return [
                            'original' => $media->getUrl(),
                            'large' => $media->getUrl('large'),
                            'medium' => $media->getUrl('medium'),
                            'thumbnail' => $media->getUrl('thumbnail'),
                            'large_webp' => $media->hasGeneratedConversion('large-webp')
                                ? $media->getUrl('large-webp')
                                : null,
                            'alt' => $media->getCustomProperty('alt', ''),
                        ];
                    })->toArray(),
                    'tags' => $project->tags,
                    'featured' => $project->featured,
                    'sort_order' => $project->sort_order,
                ],
                publishedAt: now()->toISOString(),
            );
        } elseif ($project->isDirty('is_published') && !$project->is_published) {
            $this->syncService->deleteContent(
                siteKey: $project->site->key,
                contentType: 'project',
                slug: $project->slug,
            );
        }
    }

    public function deleted(Project $project): void
    {
        $this->syncService->deleteContent(
            siteKey: $project->site->key,
            contentType: 'project',
            slug: $project->slug,
        );
    }
}
