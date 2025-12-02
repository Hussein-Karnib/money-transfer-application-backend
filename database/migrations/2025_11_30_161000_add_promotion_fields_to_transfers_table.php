<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('reference')->constrained('promotions')->nullOnDelete();
            $table->decimal('discount_amount', 18, 2)->default(0)->after('promotion_id');
            $table->string('speed')->nullable()->after('discount_amount');
            $table->timestamp('estimated_delivery_at')->nullable()->after('speed');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['promotion_id']);
            $table->dropColumn(['promotion_id', 'discount_amount', 'speed', 'estimated_delivery_at']);
        });
    }
};


