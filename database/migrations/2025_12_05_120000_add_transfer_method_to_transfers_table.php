<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('transfer_method_id')
                ->nullable()
                ->after('beneficiary_id')
                ->constrained('transfer_methods');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transfer_method_id');
        });
    }
};
