<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_content', function (Blueprint $table) {
            $table->id();
            $table->string('site_key', 50);
            $table->string('content_type', 50);
            $table->string('slug', 255);
            $table->json('data');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamps();

            $table->unique(['site_key', 'content_type', 'slug'], 'unique_site_type_slug');
            $table->index('site_key', 'idx_site_key');
            $table->index('content_type', 'idx_content_type');
            $table->index('published_at', 'idx_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_content');
    }
};
