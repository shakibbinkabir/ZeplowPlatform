<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'name' => 'Zeplow',
                'key' => 'parent',
                'domain' => 'zeplow.com',
                'tagline' => 'Story. Systems. Ventures.',
            ],
            [
                'name' => 'Zeplow Narrative',
                'key' => 'narrative',
                'domain' => 'narrative.zeplow.com',
                'tagline' => 'Stories that sell.',
            ],
            [
                'name' => 'Zeplow Logic',
                'key' => 'logic',
                'domain' => 'logic.zeplow.com',
                'tagline' => 'Build once. Run forever.',
            ],
        ];

        foreach ($sites as $site) {
            Site::firstOrCreate(['key' => $site['key']], $site);
        }
    }
}
