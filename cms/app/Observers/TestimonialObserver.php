<?php

namespace App\Observers;

use App\Models\Testimonial;
use App\Services\SyncService;
use Illuminate\Support\Str;

class TestimonialObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(Testimonial $testimonial): void
    {
        if ($testimonial->is_published) {
            $this->syncService->syncContent(
                siteKey: $testimonial->site->key,
                contentType: 'testimonial',
                slug: Str::slug($testimonial->name . '-' . $testimonial->id),
                data: [
                    'name' => $testimonial->name,
                    'role' => $testimonial->role,
                    'company' => $testimonial->company,
                    'quote' => $testimonial->quote,
                    'avatar' => $testimonial->getFirstMediaUrl('avatar'),
                    'sort_order' => $testimonial->sort_order,
                ],
                publishedAt: now()->toISOString(),
            );
        } elseif ($testimonial->isDirty('is_published') && !$testimonial->is_published) {
            $this->syncService->deleteContent(
                siteKey: $testimonial->site->key,
                contentType: 'testimonial',
                slug: Str::slug($testimonial->name . '-' . $testimonial->id),
            );
        }
    }

    public function deleted(Testimonial $testimonial): void
    {
        $this->syncService->deleteContent(
            siteKey: $testimonial->site->key,
            contentType: 'testimonial',
            slug: Str::slug($testimonial->name . '-' . $testimonial->id),
        );
    }
}
