<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->boolean('ignore_empty_reports')->default(false);
            $table->string('format')->default('html');
            $table->boolean('show_coordinates')->default(false);
            $table->boolean('show_addresses')->default(false);
            $table->boolean('markers_addresses')->default(false);
            $table->boolean('zones_addresses')->default(false);
            $table->integer('stop_duration')->default(5);
            $table->string('speed_limit')->nullable();
            $table->text('imei')->nullable();
            $table->text('marker_ids')->nullable();
            $table->text('zone_ids')->nullable();
            $table->text('driver_ids')->nullable();
            $table->text('sensor_names')->nullable();
            $table->text('data_items')->nullable();
            $table->text('other')->nullable(); // JSON data for specific reports
            $table->string('schedule_period')->nullable();
            $table->string('schedule_email_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
