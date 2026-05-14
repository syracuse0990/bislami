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
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('merchant_verified_at')->nullable()->after('email_verified_at');
            $table->boolean('is_suspended')->default(false)->after('role');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            $table->boolean('is_visible')->default(true)->after('featured_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->dropColumn('is_visible');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['merchant_verified_at', 'is_suspended', 'suspended_at']);
        });
    }
};