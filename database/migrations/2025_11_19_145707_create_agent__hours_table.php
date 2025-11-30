<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_hours', function (Blueprint $table) {
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); 
            $table->time('open_time');
            $table->time('close_time');
            $table->primary(['agent_id','day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_hours');
    }
};
