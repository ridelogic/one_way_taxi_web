<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 20)->index();
            $table->string('customer_email')->nullable();
            $table->foreignId('pickup_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('pickup_address', 255)->nullable();
            $table->string('drop_address', 255)->nullable();
            $table->foreignId('taxi_route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_category_id')->nullable()->constrained()->nullOnDelete();
            $table->date('travel_date')->index();
            $table->time('travel_time');
            $table->unsignedTinyInteger('passengers')->nullable();
            $table->text('customer_notes')->nullable();
            $table->decimal('estimated_fare', 10, 2)->nullable();
            $table->json('fare_breakdown')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('source', 30)->default('website');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
