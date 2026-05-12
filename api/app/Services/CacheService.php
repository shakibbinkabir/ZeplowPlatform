<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralises the version-counter cache strategy (PRD §12).
 *
 * Each (site_key, content prefix) pair has a `:version` counter. List queries
 * embed the current counter in their cache key. Invalidation just bumps the
 * counter — every parameterised variant becomes orphaned and expires
 * naturally after its TTL. This works on Laravel's file cache driver,
 * which is what we run on cPanel.
 */
class CacheService
{
    public const LIST_TTL = 3600;

    public function versionKey(string $siteKey, string $prefix): string
    {
        return "site:{$siteKey}:{$prefix}:version";
    }

    public function detailKey(string $siteKey, string $prefix, string $slug): string
    {
        return "site:{$siteKey}:{$prefix}:{$slug}";
    }

    public function listKey(string $siteKey, string $prefix, string $suffix = ''): string
    {
        $version = $this->currentVersion($siteKey, $prefix);
        $base    = "site:{$siteKey}:{$prefix}:v{$version}:list";

        return $suffix === '' ? $base : "{$base}:{$suffix}";
    }

    public function currentVersion(string $siteKey, string $prefix): int
    {
        return (int) Cache::get($this->versionKey($siteKey, $prefix), 1);
    }

    /**
     * Bump the version counter so every parameterised list cache key
     * for this (site, prefix) becomes orphaned. Seeds the counter on
     * first use because Cache::increment on the file driver returns
     * false when the key does not yet exist.
     */
    public function bumpVersion(string $siteKey, string $prefix): int
    {
        $key = $this->versionKey($siteKey, $prefix);

        if (! Cache::has($key)) {
            Cache::forever($key, 2);
            return 2;
        }

        return (int) Cache::increment($key);
    }

    /**
     * Invalidate everything for a single content item: bump the list
     * version, drop the detail cache. Call this from ContentSyncController
     * on both sync and delete.
     */
    public function invalidate(string $siteKey, string $prefix, ?string $slug = null): void
    {
        $this->bumpVersion($siteKey, $prefix);

        if ($slug !== null) {
            Cache::forget($this->detailKey($siteKey, $prefix, $slug));
        }
    }
}
