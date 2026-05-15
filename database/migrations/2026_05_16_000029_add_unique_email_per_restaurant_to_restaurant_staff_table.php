<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate (restaurant_id, invited_email) rows, keeping the latest
        DB::statement('
            DELETE rs1 FROM restaurant_staff rs1
            INNER JOIN restaurant_staff rs2
                ON rs1.restaurant_id = rs2.restaurant_id
                AND rs1.invited_email = rs2.invited_email
                AND rs1.id < rs2.id
        ');

        Schema::table('restaurant_staff', function (Blueprint $table) {
            $table->unique(['restaurant_id', 'invited_email'], 'restaurant_staff_restaurant_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_staff', function (Blueprint $table) {
            $table->dropUnique('restaurant_staff_restaurant_email_unique');
        });
    }
};
