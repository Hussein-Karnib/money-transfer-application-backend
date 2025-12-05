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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['bank_account_id']);
            
            // Make the column nullable
            $table->foreignId('bank_account_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullOnDelete
            $table->foreign('bank_account_id')
                  ->references('id')
                  ->on('user_bank_accounts')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['bank_account_id']);
            
            // Make the column not nullable again
            $table->foreignId('bank_account_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint with cascadeOnDelete
            $table->foreign('bank_account_id')
                  ->references('id')
                  ->on('user_bank_accounts')
                  ->cascadeOnDelete();
        });
    }
};
