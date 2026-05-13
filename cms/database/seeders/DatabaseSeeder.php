<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SiteSeeder::class,
            UserSeeder::class,
        ]);

        // Optional content seeders — run them per-site once the API app exists.
        // They wrap operations in Model::withoutEvents() so they don't dispatch
        // failed sync jobs while the API is missing.
        //
        // Run manually with:
        //   php artisan db:seed --class=Database\\Seeders\\LogicContentSeeder
        //   php artisan db:seed --class=Database\\Seeders\\ParentContentSeeder
        //   php artisan db:seed --class=Database\\Seeders\\NarrativeContentSeeder
        //
        // Or uncomment below to include in the default seed run:
        // $this->call(LogicContentSeeder::class);
        // $this->call(ParentContentSeeder::class);
        // $this->call(NarrativeContentSeeder::class);
    }
}
