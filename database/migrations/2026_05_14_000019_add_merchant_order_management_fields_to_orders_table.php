<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_type', 20)->default('delivery')->after('status');
            $table->timestamp('scheduled_for')->nullable()->after('placed_at')->index();
            $table->text('customer_notes')->nullable()->after('driver_notes');
            $table->text('merchant_notes')->nullable()->after('customer_notes');
            $table->string('rejection_reason_code', 80)->nullable()->after('merchant_notes');
            $table->text('rejection_reason_note')->nullable()->after('rejection_reason_code');
            $table->timestamp('accepted_at')->nullable()->after('scheduled_for');
            $table->timestamp('preparing_at')->nullable()->after('accepted_at');
            $table->timestamp('ready_at')->nullable()->after('preparing_at');
            $table->timestamp('picked_up_at')->nullable()->after('ready_at');
            $table->timestamp('delivered_at')->nullable()->after('picked_up_at');
            $table->timestamp('rejected_at')->nullable()->after('delivered_at');
            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'status']);
            $table->dropColumn([
                'fulfillment_type',
                'scheduled_for',
                'customer_notes',
                'merchant_notes',
                'rejection_reason_code',
                'rejection_reason_note',
                'accepted_at',
                'preparing_at',
                'ready_at',
                'picked_up_at',
                'delivered_at',
                'rejected_at',
            ]);
        });
    }
};