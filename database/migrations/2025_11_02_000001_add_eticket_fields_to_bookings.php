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
        Schema::table('bookings', function (Blueprint $table) {
            // Payment method: cash or digital
            $table->enum('payment_method', ['cash', 'digital'])->default('digital')->after('status');
            
            // Entry QR token (single token for both payment verification and entry)
            $table->string('et_qr_token', 80)->unique()->nullable()->after('payment_method');
            
            // Entry check-in tracking
            $table->timestamp('entry_scanned_at')->nullable()->after('et_qr_token');
            $table->string('checked_in_by', 50)->nullable()->after('entry_scanned_at');
            
            // Indices for performance
            $table->index('et_qr_token');
            $table->index('entry_scanned_at');
            $table->index('payment_method');
            
            // Foreign key for checked_in_by
            $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['checked_in_by']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['entry_scanned_at']);
            $table->dropIndex(['et_qr_token']);
            $table->dropColumn([
                'payment_method',
                'et_qr_token',
                'entry_scanned_at',
                'checked_in_by',
            ]);
        });
    }
};
