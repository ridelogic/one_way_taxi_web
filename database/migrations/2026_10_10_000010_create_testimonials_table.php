<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 120);
            $table->string('location', 120)->nullable();
            $table->string('trip', 150)->nullable(); // e.g. "Chennai to Coimbatore"
            $table->unsignedTinyInteger('rating')->nullable(); // 1–5
            $table->text('quote');
            $table->boolean('is_sample')->default(true); // demonstration content until replaced with real reviews
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
