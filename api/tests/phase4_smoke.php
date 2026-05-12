<?php

use App\Models\SiteConfig;
use App\Models\SiteContent;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

$kernel = app(Kernel::class);

function out(string $label, $actual, $expected): void
{
    $pass = $actual === $expected ? 'PASS' : 'FAIL';
    echo "  [{$pass}] {$label}: got=" . var_export($actual, true) . " expected=" . var_export($expected, true) . PHP_EOL;
}

function postJson(Kernel $kernel, string $uri, array $body, ?string $bearer = null, string $method = 'POST')
{
    $req = Request::create($uri, $method, [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT'  => 'application/json',
    ], json_encode($body));

    if ($bearer !== null) {
        $req->headers->set('Authorization', 'Bearer ' . $bearer);
    }

    return $kernel->handle($req);
}

$validKey   = 'g2JLXsfnef9VpilJjOSM0GvJndcjBRVKTNLuY8ZQtfJrSic6kACOmWMgg8fHQKpH';
$invalidKey = 'this-is-not-a-valid-api-key-at-all-just-garbage-padding-to-64ch';

$validPayload = [
    'site_key'     => 'parent',
    'content_type' => 'page',
    'slug'         => 'phase4-smoke-test',
    'data'         => [
        'title'    => 'Phase 4 Smoke',
        'template' => 'default',
        'content'  => [],
        'seo'      => ['title' => 'Smoke', 'description' => 'Smoke', 'og_image' => null],
    ],
    'published_at' => '2026-05-12T00:00:00Z',
];

echo "=== Phase 4 smoke ===\n";

// Clean slate for this test slug
SiteContent::where('slug', 'phase4-smoke-test')->delete();
Cache::flush();

// 4.5 — no bearer
$r = postJson($kernel, '/internal/v1/content/sync', $validPayload);
out('4.5 sync without bearer → 401', $r->status(), 401);

// 4.6 — wrong bearer
$r = postJson($kernel, '/internal/v1/content/sync', $validPayload, $invalidKey);
out('4.6 sync with invalid bearer → 403', $r->status(), 403);

// 4.7 — valid bearer + valid payload → 200, row stored, cache bumped
$versionBefore = Cache::get('site:parent:pages:version', 1);
$r = postJson($kernel, '/internal/v1/content/sync', $validPayload, $validKey);
$versionAfter = Cache::get('site:parent:pages:version', 1);

out('4.7 sync with valid bearer → 200', $r->status(), 200);
out('4.7 response body status', json_decode($r->getContent(), true)['status'] ?? null, 'synced');

$row = SiteContent::where('slug', 'phase4-smoke-test')->first();
out('4.7 row created in site_content', $row !== null, true);
out('4.7 row content_type', $row?->content_type, 'page');
out('4.7 row data.title', $row?->data['title'] ?? null, 'Phase 4 Smoke');
out('4.7 cache version bumped', $versionAfter > $versionBefore, true);

// Validation failure path
$badPayload = ['site_key' => 'parent']; // missing content_type, slug, data
$r = postJson($kernel, '/internal/v1/content/sync', $badPayload, $validKey);
out('validation: missing fields → 422', $r->status(), 422);

// Idempotent re-sync (updateOrCreate, not duplicate)
$beforeCount = SiteContent::count();
$r = postJson($kernel, '/internal/v1/content/sync', $validPayload, $validKey);
$afterCount = SiteContent::count();
out('re-sync same slug → 200', $r->status(), 200);
out('re-sync: no new row created', $afterCount - $beforeCount, 0);

// DELETE removes the row
$r = postJson($kernel, '/internal/v1/content/sync', [
    'site_key'     => 'parent',
    'content_type' => 'page',
    'slug'         => 'phase4-smoke-test',
], $validKey, 'DELETE');
$deletedRow = SiteContent::where('slug', 'phase4-smoke-test')->first();
out('DELETE → 200', $r->status(), 200);
out('DELETE removed the row', $deletedRow === null, true);

// Config sync end-to-end
SiteConfig::where('site_key', 'parent')->delete();
$r = postJson($kernel, '/internal/v1/config/sync', [
    'site_key' => 'parent',
    'config'   => [
        'site_name' => 'Zeplow',
        'tagline'   => 'Smoke',
        'nav_items' => [],
    ],
], $validKey);
out('config/sync valid → 200', $r->status(), 200);
out('site_configs row created', SiteConfig::where('site_key', 'parent')->exists(), true);

// sync-all bumps every prefix version
Cache::flush();
$r = postJson($kernel, '/internal/v1/content/sync-all', ['site_key' => 'logic'], $validKey);
out('sync-all → 200', $r->status(), 200);
out('sync-all: pages version bumped', Cache::get('site:logic:pages:version'), 2);
out('sync-all: blog version bumped', Cache::get('site:logic:blog:version'), 2);

// Deploy trigger with no hook URL configured → 500
$r = postJson($kernel, '/internal/v1/deploy/trigger/parent', [], $validKey);
out('deploy/trigger no hook → 500', $r->status(), 500);

// Clean up smoke test artifacts
SiteContent::where('slug', 'phase4-smoke-test')->delete();

echo "=== done ===\n";
