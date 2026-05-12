<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiKeySeeder extends Seeder
{
    public function run(): void
    {
        $internal = $this->generate('CMS Sync Key', 'internal');
        $build    = $this->generate('Cloudflare Build Agent', 'build');

        $bar  = str_repeat('=', 78);

        $this->command->getOutput()->writeln('');
        $this->command->warn($bar);
        $this->command->warn('  API KEYS GENERATED — COPY THESE NOW. THEY WILL NOT BE SHOWN AGAIN.');
        $this->command->warn($bar);
        $this->command->getOutput()->writeln('');

        $this->command->info('  CMS Sync Key (scope: internal)');
        $this->command->getOutput()->writeln('  Paste into CMS .env as ZEPLOW_API_KEY:');
        $this->command->getOutput()->writeln('  ' . $internal);
        $this->command->getOutput()->writeln('');

        $this->command->info('  Cloudflare Build Agent (scope: build)');
        $this->command->getOutput()->writeln('  Paste into API .env as CF_BUILD_TOKEN');
        $this->command->getOutput()->writeln('  AND into Cloudflare Pages env vars as CF_BUILD_TOKEN:');
        $this->command->getOutput()->writeln('  ' . $build);
        $this->command->getOutput()->writeln('');

        $this->command->warn($bar);
        $this->command->getOutput()->writeln('');
    }

    private function generate(string $name, string $scope): string
    {
        $plaintext = Str::random(64);

        ApiKey::create([
            'name'       => $name,
            'key_hash'   => hash('sha256', $plaintext),
            'key_prefix' => substr($plaintext, 0, 8),
            'scope'      => $scope,
            'is_active'  => true,
        ]);

        return $plaintext;
    }
}
