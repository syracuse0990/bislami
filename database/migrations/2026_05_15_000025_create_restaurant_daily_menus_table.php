<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_daily_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('mode', ['open', 'limited', 'closed'])->default('open');
            $table->unsignedSmallInteger('capacity')->nullable()->comment('Max orders for the day; only used when mode = limited');
            $table->timestamps();

            $table->unique(['restaurant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_daily_menus');
    }
};
