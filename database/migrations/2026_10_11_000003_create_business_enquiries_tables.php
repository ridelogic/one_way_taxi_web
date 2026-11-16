<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('company_name', 150);
            $table->string('contact_name', 120);
            $table->string('designation', 120)->nullable();
            $table->string('phone', 20)->index();
            $table->string('email', 150);
            $table->string('city', 100)->nullable();
            $table->string('requirement', 40);        // employee_commute, airport, outstation, events, monthly_rental, other
            $table->string('monthly_trips', 20)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('partner_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('name', 120);
            $table->string('phone', 20)->index();
            $table->string('email', 150)->nullable();
            $table->string('city', 100);
            $table->string('partner_type', 30);       // driver, owner_driver, fleet_owner, travel_agent
            $table->string('vehicle_type', 60)->nullable();
            $table->unsignedSmallInteger('vehicle_count')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->boolean('has_commercial_licence')->default(false);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_enquiries');
        Schema::dropIfExists('corporate_enquiries');
    }
};
