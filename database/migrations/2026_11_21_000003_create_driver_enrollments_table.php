<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Driver + taxi enrollments from the website ("Drive with us"), forwarded to the fleet admin. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('first_name', 30);
            $table->string('last_name', 30);
            $table->string('gender', 10);
            $table->date('dob');
            $table->string('phone', 15)->index();
            $table->string('email', 150)->nullable();
            $table->text('password')->nullable();            // encrypted; cleared once the fleet account exists
            $table->string('national_id', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('licence_no', 30);
            $table->date('licence_expiry');
            $table->date('badge_expiry');
            $table->unsignedInteger('fleet_model_id');
            $table->string('model_name', 60)->nullable();
            $table->string('state_value', 80);
            $table->string('prefix_value', 40);
            $table->string('plate_number', 20);
            $table->string('taxi_no', 120);
            $table->string('manufacturer_value', 80);
            $table->unsignedSmallInteger('manufacturing_year');
            $table->string('colour', 10)->nullable();
            $table->string('owner_name', 30)->nullable();
            $table->json('files');
            $table->string('status', 10)->default('pending')->index(); // pending | sent | failed
            $table->unsignedBigInteger('fleet_driver_id')->nullable();
            $table->unsignedBigInteger('fleet_taxi_id')->nullable();
            $table->string('sync_error', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_enrollments');
    }
};
