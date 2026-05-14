<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('featured_text');
            $table->string('location_address')->nullable()->after('logo_path');
            $table->decimal('location_latitude', 10, 7)->nullable()->after('location_address');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->dropColumn(['logo_path', 'location_address', 'location_latitude', 'location_longitude']);
        });
    }
};