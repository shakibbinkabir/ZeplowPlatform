<?php

namespace App\Observers;

use App\Models\SiteConfig;
use App\Services\SyncService;

class SiteConfigObserver
{
    public function __construct(private SyncService $syncService) {}

    public function saved(SiteConfig $config): void
    {
        $this->syncService->syncConfig(
            siteKey: $config->site->key,
            configData: [
                'site_name' => $config->site->name,
                'domain' => $config->site->domain,
                'tagline' => $config->site->tagline,
                'nav_items' => $config->nav_items,
                'footer_links' => $config->footer_links,
                'footer_text' => $config->footer_text,
                'cta_text' => $config->cta_text,
                'cta_url' => $config->cta_url,
                'social_links' => $config->social_links,
                'contact_email' => $config->contact_email,
                'contact_phone' => $config->contact_phone,
            ],
        );
    }
}
