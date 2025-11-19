<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_from', 3);
            $table->string('currency_to', 3);
            $table->decimal('rate', 18, 8);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();

            $table->foreign('currency_from')->references('code')->on('currencies');
            $table->foreign('currency_to')->references('code')->on('currencies');

            $table->unique(['currency_from','currency_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
