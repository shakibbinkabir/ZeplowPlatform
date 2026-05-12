<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deploy_logs', function (Blueprint $table) {
            $table->id();
            $table->string('site_key', 50);
            $table->string('trigger_source', 50);
            $table->enum('status', ['triggered', 'success', 'failed', 'debounced'])->default('triggered');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('site_key', 'idx_site_key');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploy_logs');
    }
};
