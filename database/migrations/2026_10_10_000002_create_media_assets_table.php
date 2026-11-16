<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('uploads'); // uploads (admin uploads) | demo (bundled images)
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('credit', 500)->nullable();
            // Non-destructive crop / positioning controls applied with CSS
            $table->string('aspect_ratio', 10)->default('original'); // original, 16:9, 4:3, 3:2, 1:1, 3:4
            $table->string('object_fit', 10)->default('cover');      // cover, contain
            $table->unsignedTinyInteger('focal_x')->default(50);     // object-position X (%)
            $table->unsignedTinyInteger('focal_y')->default(50);     // object-position Y (%)
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
