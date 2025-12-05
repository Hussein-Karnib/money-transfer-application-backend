<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_verifications', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('id_number');
            $table->text('review_comment')->nullable()->after('verified_at');
        });

        // Add "verified" to the enum without dropping legacy "approved" values.
        if (Schema::getColumnType('user_verifications', 'status') === 'enum') {
            DB::statement("ALTER TABLE user_verifications MODIFY status ENUM('pending','verified','approved','rejected') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // Attempt to revert enum to the prior set while keeping backward compatibility.
        if (Schema::getColumnType('user_verifications', 'status') === 'enum') {
            DB::statement("ALTER TABLE user_verifications MODIFY status ENUM('pending','approved','rejected') DEFAULT 'pending'");
        }

        Schema::table('user_verifications', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'review_comment']);
        });
    }
};
