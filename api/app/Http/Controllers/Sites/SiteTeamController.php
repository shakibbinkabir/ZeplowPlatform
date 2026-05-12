<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SiteTeamController extends Controller
{
    public function __construct(private CacheService $cache) {}

    public function index(string $siteKey): JsonResponse
    {
        $cacheKey = $this->cache->listKey($siteKey, 'team');

        $data = Cache::remember($cacheKey, CacheService::LIST_TTL, function () use ($siteKey) {
            return SiteContent::where('site_key', $siteKey)
                ->where('content_type', 'team_member')
                ->orderBy('data->sort_order')
                ->get()
                ->map(function (SiteContent $item) {
                    $data = $item->data;
                    return [
                        'id'         => $item->id,
                        'name'       => $data['name'] ?? null,
                        'role'       => $data['role'] ?? null,
                        'bio'        => $data['bio'] ?? null,
                        'photo'      => $data['photo'] ?? null,
                        'linkedin'   => $data['linkedin'] ?? null,
                        'email'      => $data['email'] ?? null,
                        'is_founder' => $data['is_founder'] ?? false,
                        'sort_order' => $data['sort_order'] ?? 0,
                    ];
                });
        });

        return response()->json($data)->header('Cache-Control', 'public, max-age=3600');
    }
}
