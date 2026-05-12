<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('site_key', 50);
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('company', 255)->nullable();
            $table->text('message');
            $table->string('budget_range', 100)->nullable();
            $table->string('source', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('site_key', 'idx_site_key');
            $table->index('is_read', 'idx_is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
