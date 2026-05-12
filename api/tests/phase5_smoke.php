<?php

use App\Models\ContactSubmission;
use App\Models\SiteConfig;
use App\Models\SiteContent;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

$kernel = app(Kernel::class);

function out(string $label, $actual, $expected): void
{
    $pass = $actual === $expected ? 'PASS' : 'FAIL';
    echo "  [{$pass}] {$label}: got=" . var_export($actual, true) . ' expected=' . var_export($expected, true) . PHP_EOL;
}

function httpReq(Kernel $kernel, string $method, string $uri, array $body = [], array $headers = [])
{
    $server = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT'  => 'application/json',
    ];
    foreach ($headers as $k => $v) {
        $server['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
    }
    $payload = ! empty($body) ? json_encode($body) : null;
    $req = Request::create($uri, $method, [], [], [], $server, $payload);
    return $kernel->handle($req);
}

echo "=== Phase 5 smoke ===\n";

// =====================================================
// Seed fixture content for every endpoint
// =====================================================
Cache::flush();

// Config for parent + logic
SiteConfig::updateOrCreate(
    ['site_key' => 'parent'],
    ['config' => [
        'site_name' => 'Zeplow',
        'tagline'   => 'Story. Systems. Ventures.',
        'nav_items' => [
            ['label' => 'About', 'url' => '/about', 'is_external' => false],
        ],
        'cta_text'  => 'Get in touch',
        'cta_url'   => '/contact',
        'contact_email' => 'hello@zeplow.com',
        'social_links'  => ['linkedin' => 'https://linkedin.com/company/zeplow'],
    ], 'synced_at' => now()]
);

// Pages
SiteContent::where('site_key', 'parent')->delete();
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'page', 'slug' => 'home',
    'data' => ['title' => 'Home', 'template' => 'home', 'content' => [['type' => 'hero', 'data' => ['heading' => 'Welcome']]], 'seo' => ['title' => 'Home — Zeplow', 'description' => 'desc', 'og_image' => null], 'sort_order' => 0],
    'published_at' => now(), 'synced_at' => now(),
]);
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'page', 'slug' => 'about',
    'data' => ['title' => 'About', 'template' => 'about', 'content' => [], 'seo' => ['title' => 'About', 'description' => 'desc', 'og_image' => null], 'sort_order' => 1],
    'published_at' => now(), 'synced_at' => now(),
]);
// One draft page (no published_at) — should NOT appear in /pages
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'page', 'slug' => 'draft',
    'data' => ['title' => 'Draft', 'template' => 'default', 'content' => [], 'seo' => [], 'sort_order' => 99],
    'published_at' => null, 'synced_at' => now(),
]);

// Projects
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'project', 'slug' => 'alpha',
    'data' => ['title' => 'Alpha', 'one_liner' => 'first', 'images' => ['x.jpg'], 'tags' => ['web'], 'featured' => true, 'sort_order' => 0],
    'published_at' => now(), 'synced_at' => now(),
]);
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'project', 'slug' => 'beta',
    'data' => ['title' => 'Beta', 'one_liner' => 'second', 'images' => [], 'tags' => [], 'featured' => false, 'sort_order' => 1],
    'published_at' => now(), 'synced_at' => now(),
]);

// Blog
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'blog_post', 'slug' => 'why-x',
    'data' => ['title' => 'Why X', 'excerpt' => 'hook', 'body' => '<p>body</p>', 'tags' => ['branding'], 'author' => 'Shadman'],
    'published_at' => now()->subDay(), 'synced_at' => now(),
]);
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'blog_post', 'slug' => 'why-y',
    'data' => ['title' => 'Why Y', 'excerpt' => 'hook2', 'body' => '<p>body2</p>', 'tags' => ['strategy'], 'author' => 'Shakib'],
    'published_at' => now(), 'synced_at' => now(),
]);

// Testimonials
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'testimonial', 'slug' => 'john',
    'data' => ['name' => 'John', 'role' => 'CEO', 'company' => 'Corp', 'quote' => 'Great!', 'sort_order' => 0],
    'published_at' => now(), 'synced_at' => now(),
]);

// Team
SiteContent::create([
    'site_key' => 'parent', 'content_type' => 'team_member', 'slug' => 'shadman',
    'data' => ['name' => 'Shadman Sakib', 'role' => 'Co-Founder & CEO', 'bio' => 'bio', 'is_founder' => true, 'sort_order' => 0],
    'published_at' => now(), 'synced_at' => now(),
]);

// =====================================================
// Health
// =====================================================
echo "\n[health]\n";
$r = httpReq($kernel, 'GET', '/health');
out('/health → 200', $r->status(), 200);
$body = json_decode($r->getContent(), true);
out('/health body.status', $body['status'], 'ok');
out('/health body.database', $body['database'], 'connected');

// =====================================================
// Site key validation
// =====================================================
echo "\n[site_key middleware]\n";
$r = httpReq($kernel, 'GET', '/sites/v1/garbage/pages');
out('garbage site key → 404', $r->status(), 404);
$r = httpReq($kernel, 'GET', '/sites/v1/parent/pages');
out('valid site key → 200', $r->status(), 200);

// =====================================================
// SiteConfigController
// =====================================================
echo "\n[/config]\n";
$r = httpReq($kernel, 'GET', '/sites/v1/parent/config');
out('config → 200', $r->status(), 200);
$cc = $r->headers->get('Cache-Control');
out('config Cache-Control has public', str_contains($cc, 'public'), true);
out('config Cache-Control has max-age=3600', str_contains($cc, 'max-age=3600'), true);
$body = json_decode($r->getContent(), true);
out('config.site_key merged', $body['site_key'] ?? null, 'parent');
out('config.tagline', $body['tagline'] ?? null, 'Story. Systems. Ventures.');

$r = httpReq($kernel, 'GET', '/sites/v1/logic/config');
out('config for site without record → 404', $r->status(), 404);

// =====================================================
// SitePageController
// =====================================================
echo "\n[/pages]\n";
$r = httpReq($kernel, 'GET', '/sites/v1/parent/pages');
$body = json_decode($r->getContent(), true);
out('pages → 200', $r->status(), 200);
out('pages count (drafts excluded)', count($body), 2);
out('pages[0].slug ordered', $body[0]['slug'], 'home');

$r = httpReq($kernel, 'GET', '/sites/v1/parent/pages/home');
$body = json_decode($r->getContent(), true);
out('page detail → 200', $r->status(), 200);
out('page detail.template', $body['template'], 'home');
out('page detail.content[0].type', $body['content'][0]['type'] ?? null, 'hero');

$r = httpReq($kernel, 'GET', '/sites/v1/parent/pages/draft');
out('draft page → 404', $r->status(), 404);

$r = httpReq($kernel, 'GET', '/sites/v1/parent/pages/nonexistent');
out('missing page → 404', $r->status(), 404);

// =====================================================
// SiteProjectController
// =====================================================
echo "\n[/projects]\n";
$r = httpReq($kernel, 'GET', '/sites/v1/parent/projects?featured=true&limit=3');
$body = json_decode($r->getContent(), true);
out('projects?featured=true&limit=3 → 200', $r->status(), 200);
out('projects limit response is array', is_array($body) && isset($body[0]), true);
out('projects featured only', count($body), 1);

$r = httpReq($kernel, 'GET', '/sites/v1/parent/projects');
$body = json_decode($r->getContent(), true);
out('projects (no limit) paginated shape', isset($body['data']) && isset($body['meta']), true);
out('projects meta.total', $body['meta']['total'], 2);

$r = httpReq($kernel, 'GET', '/sites/v1/parent/projects/alpha');
$body = json_decode($r->getContent(), true);
out('project detail title', $body['title'], 'Alpha');

// =====================================================
// SiteBlogController
// =====================================================
echo "\n[/blog]\n";
$r = httpReq($kernel, 'GET', '/sites/v1/parent/blog');
$body = json_decode($r->getContent(), true);
out('blog → 200', $r->status(), 200);
out('blog paginated', isset($body['data']), true);
out('blog ordered desc by published_at', $body['data'][0]['slug'], 'why-y');

$r = httpReq($kernel, 'GET', '/sites/v1/parent/blog?tag=branding&limit=10');
$body = json_decode($r->getContent(), true);
out('blog?tag=branding count', count($body), 1);
out('blog?tag=branding slug', $body[0]['slug'], 'why-x');

$r = httpReq($kernel, 'GET', '/sites/v1/parent/blog/why-x');
$body = json_decode($r->getContent(), true);
out('blog detail body present', isset($body['body']), true);

// =====================================================
// Testimonials + team
// =====================================================
echo "\n[/testimonials + /team]\n";
$r = httpReq($kernel, 'GET', '/sites/v1/parent/testimonials');
$body = json_decode($r->getContent(), true);
out('testimonials count', count($body), 1);
out('testimonials[0].name', $body[0]['name'], 'John');

$r = httpReq($kernel, 'GET', '/sites/v1/parent/team');
$body = json_decode($r->getContent(), true);
out('team count', count($body), 1);
out('team[0].is_founder', $body[0]['is_founder'], true);

// =====================================================
// Contact form — honeypot, turnstile-missing, validation, success
// =====================================================
echo "\n[/contact]\n";

// honeypot filled — fake 200 silently
$before = ContactSubmission::count();
$r = httpReq($kernel, 'POST', '/sites/v1/parent/contact', [
    'name' => 'Bot', 'email' => 'bot@x.com', 'message' => 'spam', 'website_url' => 'gotcha',
]);
out('honeypot → 200', $r->status(), 200);
out('honeypot → no submission stored', ContactSubmission::count() - $before, 0);

// turnstile missing — fake 200 silently
$r = httpReq($kernel, 'POST', '/sites/v1/parent/contact', [
    'name' => 'John', 'email' => 'john@x.com', 'message' => 'hello',
]);
out('no turnstile → 200', $r->status(), 200);
out('no turnstile → no submission stored', ContactSubmission::count() - $before, 0);

// turnstile present, secret unset → verifyTurnstile returns true, validation runs
Mail::fake();
$r = httpReq($kernel, 'POST', '/sites/v1/parent/contact', [
    'name' => 'John', 'email' => 'john@x.com', 'message' => 'hello',
    'cf_turnstile_response' => 'dev-mode-skip',
]);
out('valid contact → 200', $r->status(), 200);
out('valid contact → submission stored', ContactSubmission::count() - $before, 1);

// validation failure (missing required fields) → 422
$r = httpReq($kernel, 'POST', '/sites/v1/parent/contact', [
    'email' => 'x@x.com',
    'cf_turnstile_response' => 'dev-mode-skip',
]);
out('missing name → 422', $r->status(), 422);
$body = json_decode($r->getContent(), true);
out('422 body has fields.name', isset($body['fields']['name']), true);

// =====================================================
// Cache behaviour: detail cache hits don't re-query DB
// =====================================================
echo "\n[cache]\n";
Cache::flush();

// Warm the cache
$r1 = httpReq($kernel, 'GET', '/sites/v1/parent/pages/home');
$body1 = json_decode($r1->getContent(), true);

// Mutate the DB directly (bypass invalidation) — cached response should still win
SiteContent::where('site_key', 'parent')->where('content_type', 'page')->where('slug', 'home')
    ->update(['data' => array_merge($body1, ['title' => 'STALE_DB_VALUE'])]);

$r2 = httpReq($kernel, 'GET', '/sites/v1/parent/pages/home');
$body2 = json_decode($r2->getContent(), true);
out('detail cache served stale row', $body2['title'], 'Home');

// Internal sync invalidation should bust the cache.
// Plaintext supplied via env so we don't bake it into a tracked file.
$key = env('ZEPLOW_TEST_API_KEY', '');
if ($key === '') {
    echo "  [SKIP] cache invalidation test: ZEPLOW_TEST_API_KEY env var not set\n";
    echo "=== done (partial) ===\n";
    return;
}
$req = Request::create('/internal/v1/content/sync', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT'  => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer ' . $key,
], json_encode([
    'site_key' => 'parent', 'content_type' => 'page', 'slug' => 'home',
    'data' => array_merge($body1, ['title' => 'FRESH_VALUE']),
    'published_at' => now()->toISOString(),
]));
$kernel->handle($req);

$r3 = httpReq($kernel, 'GET', '/sites/v1/parent/pages/home');
$body3 = json_decode($r3->getContent(), true);
out('cache busted after sync', $body3['title'], 'FRESH_VALUE');

// =====================================================
// Cleanup
// =====================================================
SiteContent::where('site_key', 'parent')->delete();
SiteConfig::where('site_key', 'parent')->delete();
ContactSubmission::where('email', 'john@x.com')->delete();

echo "\n=== done ===\n";
