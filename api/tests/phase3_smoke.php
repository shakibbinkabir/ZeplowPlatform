<?php

use App\Http\Middleware\ValidateApiKey;
use App\Http\Middleware\ValidateSiteKey;
use App\Models\ApiKey;
use App\Models\DeployLog;
use App\Services\CacheService;
use App\Services\DeployService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;

$next = fn ($r) => response()->json(['ok' => true]);

function out(string $label, $actual, $expected): void
{
    $pass = $actual === $expected ? 'PASS' : 'FAIL';
    echo "  [{$pass}] {$label}: got=" . var_export($actual, true) . " expected=" . var_export($expected, true) . PHP_EOL;
}

echo "=== Phase 3 smoke ===\n";

// 1. ValidateApiKey — missing header → 401
$mw = new ValidateApiKey();
$resp = $mw->handle(Request::create('/internal/v1/x', 'POST'), $next);
out('ValidateApiKey: missing token → 401', $resp->status(), 401);

// 2. ValidateApiKey — bad token → 403
$req = Request::create('/internal/v1/x', 'POST');
$req->headers->set('Authorization', 'Bearer not-a-real-key');
$resp = $mw->handle($req, $next);
out('ValidateApiKey: bad token → 403', $resp->status(), 403);

// 3. ValidateApiKey — good token → next() runs, 200
// Plaintext supplied via env so we don't bake it into a tracked file.
// Set ZEPLOW_TEST_API_KEY in api/.env to the internal-scope key from your local seed.
$plain = env('ZEPLOW_TEST_API_KEY', '');
if ($plain === '') {
    echo "  [SKIP] valid-token assertions: ZEPLOW_TEST_API_KEY env var not set\n";
    echo "=== done (partial) ===\n";
    return;
}
$req = Request::create('/internal/v1/x', 'POST');
$req->headers->set('Authorization', 'Bearer ' . $plain);
$resp = $mw->handle($req, $next);
out('ValidateApiKey: valid internal token → 200', $resp->status(), 200);

// 4. ValidateApiKey — good internal token, but ask for scope=build → 403
$req = Request::create('/internal/v1/x', 'POST');
$req->headers->set('Authorization', 'Bearer ' . $plain);
$resp = $mw->handle($req, $next, 'build');
out('ValidateApiKey: internal token, build scope → 403', $resp->status(), 403);

// 5. last_used_at got updated
$row = ApiKey::where('scope', 'internal')->first();
out('ValidateApiKey: last_used_at updated', $row->last_used_at !== null, true);

// 6. ValidateSiteKey — good key → next()
$siteMw = new ValidateSiteKey();
$r = Request::create('/sites/v1/parent/pages');
$route = new Route(['GET'], '/sites/v1/{siteKey}/pages', []);
$route->parameters = ['siteKey' => 'parent'];
$r->setRouteResolver(fn () => $route);
$resp = $siteMw->handle($r, $next);
out('ValidateSiteKey: parent → 200', $resp->status(), 200);

// 7. ValidateSiteKey — garbage → 404
$r = Request::create('/sites/v1/garbage/pages');
$route2 = new Route(['GET'], '/sites/v1/{siteKey}/pages', []);
$route2->parameters = ['siteKey' => 'garbage'];
$r->setRouteResolver(fn () => $route2);
$resp = $siteMw->handle($r, $next);
out('ValidateSiteKey: garbage → 404', $resp->status(), 404);

// 8. DeployService — no hook URL configured → false, warning logged, no log row
$logsBefore = DeployLog::count();
$deploy = app(DeployService::class);
$ok = $deploy->trigger('parent', 'smoke_test');
$logsAfter = DeployLog::count();
out('DeployService: no hook → returns false', $ok, false);
out('DeployService: no hook → no deploy_logs row added', $logsAfter - $logsBefore, 0);

// 9. CacheService — listKey embeds the version, invalidate bumps it
Cache::flush();
$cache = app(CacheService::class);
$k1 = $cache->listKey('parent', 'pages');
$cache->invalidate('parent', 'pages', 'home');
$k2 = $cache->listKey('parent', 'pages');
out('CacheService: v1 list key', $k1, 'site:parent:pages:v1:list');
out('CacheService: bumped list key', $k2, 'site:parent:pages:v2:list');

// 10. CacheService — detail key + suffix variants
out('CacheService: detail key', $cache->detailKey('logic', 'projects', 'tututor-ai'), 'site:logic:projects:tututor-ai');
out('CacheService: list key with suffix', $cache->listKey('logic', 'projects', '1:3:1:50'), 'site:logic:projects:v1:list:1:3:1:50');

echo "=== done ===\n";
