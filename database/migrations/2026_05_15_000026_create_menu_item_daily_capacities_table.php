<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_daily_capacities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('capacity')->comment('Max orders for this item today');
            $table->timestamps();

            $table->unique(['menu_item_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_daily_capacities');
    }
};
