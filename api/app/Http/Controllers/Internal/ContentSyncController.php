<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\CacheService;
use App\Services\DeployService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentSyncController extends Controller
{
    /**
     * The CMS sends content types like "blog_post" and "team_member",
     * but the public API endpoints are /blog and /team. Cache keys must
     * align with the public URL prefix, so we translate here.
     */
    private const TYPE_TO_CACHE_PREFIX = [
        'page'        => 'pages',
        'project'     => 'projects',
        'blog_post'   => 'blog',
        'testimonial' => 'testimonials',
        'team_member' => 'team',
    ];

    public function __construct(
        private DeployService $deployService,
        private CacheService $cacheService,
    ) {}

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_key'     => 'required|string|max:50',
            'content_type' => 'required|string|max:50',
            'slug'         => 'required|string|max:255',
            'data'         => 'required|array',
            'published_at' => 'nullable|date',
        ]);

        SiteContent::updateOrCreate(
            [
                'site_key'     => $validated['site_key'],
                'content_type' => $validated['content_type'],
                'slug'         => $validated['slug'],
            ],
            [
                'data'         => $validated['data'],
                'published_at' => $validated['published_at'] ?? null,
                'synced_at'    => now(),
            ]
        );

        $prefix = $this->cachePrefix($validated['content_type']);
        $this->cacheService->invalidate($validated['site_key'], $prefix, $validated['slug']);

        $this->deployService->trigger($validated['site_key'], 'content_sync');

        return response()->json([
            'status'       => 'synced',
            'site_key'     => $validated['site_key'],
            'content_type' => $validated['content_type'],
            'slug'         => $validated['slug'],
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_key'     => 'required|string|max:50',
            'content_type' => 'required|string|max:50',
            'slug'         => 'required|string|max:255',
        ]);

        SiteContent::where('site_key', $validated['site_key'])
            ->where('content_type', $validated['content_type'])
            ->where('slug', $validated['slug'])
            ->delete();

        $prefix = $this->cachePrefix($validated['content_type']);
        $this->cacheService->invalidate($validated['site_key'], $prefix, $validated['slug']);

        $this->deployService->trigger($validated['site_key'], 'content_delete');

        return response()->json(['status' => 'deleted']);
    }

    public function syncAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_key' => 'required|string|max:50',
        ]);

        $siteKey = $validated['site_key'];

        foreach (self::TYPE_TO_CACHE_PREFIX as $prefix) {
            $this->cacheService->bumpVersion($siteKey, $prefix);
        }
        Cache::forget("site:{$siteKey}:config");

        $this->deployService->trigger($siteKey, 'full_resync');

        return response()->json([
            'status'   => 'cache_cleared',
            'site_key' => $siteKey,
        ]);
    }

    private function cachePrefix(string $contentType): string
    {
        return self::TYPE_TO_CACHE_PREFIX[$contentType] ?? $contentType . 's';
    }
}
