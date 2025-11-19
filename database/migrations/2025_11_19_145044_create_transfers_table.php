<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users');
            $table->foreignId('beneficiary_id')->constrained('beneficiaries');
            $table->decimal('amount', 18, 2);
            $table->string('currency_from', 3);
            $table->string('currency_to', 3);
            $table->decimal('exchange_rate', 18, 8);
            $table->decimal('fee', 18, 2);
            $table->decimal('total_amount', 18, 2); // amount + fee
            $table->enum('status', ['queued','paid','in_progress','available_for_pickup','completed','failed','refunded','disputed'])->default('queued');
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('reference')->unique()->nullable();
            $table->timestamps();

            $table->foreign('currency_from')->references('code')->on('currencies');
            $table->foreign('currency_to')->references('code')->on('currencies');

            $table->index(['sender_id', 'initiated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
