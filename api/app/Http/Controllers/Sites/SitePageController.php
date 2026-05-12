<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SitePageController extends Controller
{
    public function __construct(private CacheService $cache) {}

    public function index(string $siteKey): JsonResponse
    {
        $cacheKey = $this->cache->listKey($siteKey, 'pages');

        $data = Cache::remember($cacheKey, CacheService::LIST_TTL, function () use ($siteKey) {
            return SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'page')
                ->whereNotNull('published_at')
                ->orderBy('data->sort_order')
                ->get()
                ->map(function (SiteContent $item) {
                    $data = $item->data;
                    return [
                        'id'           => $item->id,
                        'slug'         => $item->slug,
                        'title'        => $data['title'] ?? null,
                        'template'     => $data['template'] ?? 'default',
                        'seo'          => $data['seo'] ?? null,
                        'sort_order'   => $data['sort_order'] ?? 0,
                        'published_at' => $item->published_at,
                    ];
                });
        });

        return response()->json($data)->header('Cache-Control', 'public, max-age=3600');
    }

    public function show(string $siteKey, string $slug): JsonResponse
    {
        $cacheKey = $this->cache->detailKey($siteKey, 'pages', $slug);

        $data = Cache::remember($cacheKey, CacheService::LIST_TTL, function () use ($siteKey, $slug) {
            $item = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'page')
                ->where('slug', $slug)
                ->whereNotNull('published_at')
                ->firstOrFail();

            return array_merge(
                ['id' => $item->id, 'slug' => $item->slug],
                $item->data,
                ['published_at' => $item->published_at]
            );
        });

        return response()->json($data)->header('Cache-Control', 'public, max-age=3600');
    }
}
