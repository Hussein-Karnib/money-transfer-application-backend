<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_from_id')->constrained('countries');
            $table->foreignId('country_to_id')->constrained('countries');
            $table->decimal('min_amount', 18, 2);
            $table->decimal('max_amount', 18, 2);
            $table->decimal('fee_fixed', 18, 2)->default(0);
            $table->decimal('fee_percent', 5, 2)->default(0);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_fees');
    }
};
