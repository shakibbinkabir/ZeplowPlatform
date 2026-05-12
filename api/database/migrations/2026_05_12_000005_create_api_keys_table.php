<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('key_hash', 64)->unique()->comment('SHA-256 hash of the API key');
            $table->string('key_prefix', 8)->comment('First 8 chars of the plaintext key for identification');
            $table->enum('scope', ['internal', 'build'])->default('internal');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['key_hash', 'is_active'], 'idx_key_hash_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
