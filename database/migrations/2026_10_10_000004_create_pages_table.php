<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('template', 30)->default('default'); // default, home, about, legal
            $table->string('heading')->nullable();
            $table->string('subheading', 500)->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('hero_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index(); // draft, published
            $table->boolean('show_in_nav')->default(false);
            $table->unsignedInteger('nav_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('type', 50);
            $table->string('title')->nullable();
            $table->string('subtitle', 500)->nullable();
            $table->json('content')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['page_id', 'key']);
            $table->index(['page_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
    }
};
