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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('cuisine');
            $table->unsignedTinyInteger('min_delivery_time');
            $table->unsignedTinyInteger('max_delivery_time');
            $table->decimal('rating', 3, 2);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->string('featured_text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};