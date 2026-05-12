<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteBlogController extends Controller
{
    public function __construct(private CacheService $cache) {}

    public function index(Request $request, string $siteKey): JsonResponse
    {
        $tag     = (string) $request->string('tag', '');
        $limit   = (int) $request->integer('limit', 0);
        $page    = (int) $request->integer('page', 1);
        $perPage = (int) $request->integer('per_page', 20);

        $suffix   = "{$tag}:{$limit}:{$page}:{$perPage}";
        $cacheKey = $this->cache->listKey($siteKey, 'blog', $suffix);

        $data = Cache::remember($cacheKey, CacheService::LIST_TTL, function () use ($siteKey, $tag, $limit, $page, $perPage) {
            $query = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'blog_post')
                ->whereNotNull('published_at')
                ->orderByDesc('published_at');

            if ($tag !== '') {
                $query->whereJsonContains('data->tags', $tag);
            }

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
        $cacheKey = $this->cache->detailKey($siteKey, 'blog', $slug);

        $data = Cache::remember($cacheKey, CacheService::LIST_TTL, function () use ($siteKey, $slug) {
            $item = SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'blog_post')
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
            'id'           => $item->id,
            'slug'         => $item->slug,
            'title'        => $data['title'] ?? null,
            'excerpt'      => $data['excerpt'] ?? null,
            'cover_image'  => $data['cover_image'] ?? null,
            'tags'         => $data['tags'] ?? [],
            'author'       => $data['author'] ?? null,
            'published_at' => $item->published_at,
        ];
    }
}
