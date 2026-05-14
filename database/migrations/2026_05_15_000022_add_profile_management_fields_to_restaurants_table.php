<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->string('contact_phone')->nullable()->after('featured_text');
            $table->decimal('delivery_radius_km', 5, 2)->nullable()->after('location_longitude');
            $table->unsignedInteger('minimum_order_value')->default(0)->after('delivery_radius_km');
            $table->unsignedTinyInteger('preparation_time_min')->nullable()->after('minimum_order_value');
            $table->unsignedTinyInteger('preparation_time_max')->nullable()->after('preparation_time_min');
            $table->json('operating_hours')->nullable()->after('order_settings');
            $table->json('closure_dates')->nullable()->after('operating_hours');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->dropColumn([
                'contact_phone',
                'delivery_radius_km',
                'minimum_order_value',
                'preparation_time_min',
                'preparation_time_max',
                'operating_hours',
                'closure_dates',
            ]);
        });
    }
};