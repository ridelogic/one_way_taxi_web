<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow', 120)->nullable();
            $table->string('heading', 160);
            $table->string('highlight', 120)->nullable(); // shown in orange after the heading
            $table->string('subtitle', 400)->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->foreignId('foreground_media_id')->nullable()->constrained('media_assets')->nullOnDelete(); // optional cut-out vehicle
            $table->string('primary_label', 60)->nullable();
            $table->string('primary_url', 255)->nullable();
            $table->string('secondary_label', 60)->nullable();
            $table->string('secondary_url', 255)->nullable();
            $table->unsignedTinyInteger('overlay_strength')->default(45);
            $table->string('text_align', 10)->default('left'); // left, center
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
