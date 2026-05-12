<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteProjectController extends Controller
{
    public function __construct(private CacheService $cache) {}

    public function index(Request $request, string $siteKey): JsonResponse
    {
        $featured = $request->boolean('featured', false);
        $limit    = (int) $request->integer('limit', 0);
        $page     = (int) $request->integer('page', 1);
        $perPage  = (int) $request->integer('per_page', 50);

        $suffix   = "{$featured}:{$limit}:{$page}:{$perPage}";
        $cacheKey = $this->cache->listKey($siteKey, 'projects', $suffix);

        $data = Cache::remember($cacheKey, CacheService::LIST_TTL, function () use ($siteKey, $featured, $limit, $page, $perPage) {
            $query = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'project')
                ->whereNotNull('published_at');

            if ($featured) {
                $query->where('data->featured', true);
            }

            $query->orderBy('data->sort_order');

            if ($limit > 0) {
                return $query->limit($limit)->get()->map(fn ($item) => $this->formatListItem($item));
            }

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => collect($paginated->items())->map(fn ($item) => $this->formatListItem($item)),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ];
        });

        return response()->json($data)->header('Cache-Control', 'public, max-age=3600');
    }

    public function show(string $siteKey, string $slug): JsonResponse
    {
        $cacheKey = $this->cache->detailKey($siteKey, 'projects', $slug);

        $data = Cache::remember($cacheKey, CacheService::LIST_TTL, function () use ($siteKey, $slug) {
            $item = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'project')
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

    private function formatListItem(SiteContent $item): array
    {
        $data = $item->data;
        return [
            'id'          => $item->id,
            'slug'        => $item->slug,
            'title'       => $data['title'] ?? null,
            'one_liner'   => $data['one_liner'] ?? null,
            'client_name' => $data['client_name'] ?? null,
            'industry'    => $data['industry'] ?? null,
            'url'         => $data['url'] ?? null,
            'images'      => $data['images'] ?? [],
            'tags'        => $data['tags'] ?? [],
            'featured'    => $data['featured'] ?? false,
            'sort_order'  => $data['sort_order'] ?? 0,
        ];
    }
}
