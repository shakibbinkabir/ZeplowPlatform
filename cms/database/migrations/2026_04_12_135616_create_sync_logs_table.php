<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('site_key', 50);
            $table->string('content_type', 50);
            $table->string('content_slug');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->integer('attempt_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['site_key', 'content_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
