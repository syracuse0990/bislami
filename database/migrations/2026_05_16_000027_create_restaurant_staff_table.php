<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->nullOnDelete()->constrained();
            $table->string('invited_email');
            $table->string('invited_name')->nullable();
            $table->string('role', 30); // manager|kitchen|cashier|waiter
            $table->json('permissions');
            $table->string('status', 20)->default('pending'); // pending|active|suspended
            $table->string('token', 64)->nullable()->unique();
            $table->foreignId('invited_by')->nullable()->nullOnDelete()->constrained('users');
            $table->timestamp('invited_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_staff');
    }
};
