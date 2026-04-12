<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('one_liner', 500);
            $table->string('client_name')->nullable();
            $table->string('industry')->nullable();
            $table->string('url', 500)->nullable();
            $table->text('challenge')->nullable();
            $table->text('solution')->nullable();
            $table->text('outcome')->nullable();
            $table->json('tech_stack')->nullable();
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
