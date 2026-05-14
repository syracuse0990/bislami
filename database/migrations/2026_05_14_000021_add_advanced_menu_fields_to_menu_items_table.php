<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->unsignedInteger('promo_price')->nullable()->after('price');
            $table->time('availability_starts_at')->nullable()->after('is_available');
            $table->time('availability_ends_at')->nullable()->after('availability_starts_at');
            $table->json('variants')->nullable()->after('availability_ends_at');
            $table->json('add_ons')->nullable()->after('variants');
            $table->json('modifiers')->nullable()->after('add_ons');
            $table->json('bundle_items')->nullable()->after('modifiers');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn([
                'promo_price',
                'availability_starts_at',
                'availability_ends_at',
                'variants',
                'add_ons',
                'modifiers',
                'bundle_items',
            ]);
        });
    }
};