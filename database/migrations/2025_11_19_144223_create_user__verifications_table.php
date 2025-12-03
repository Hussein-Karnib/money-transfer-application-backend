<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('user_verifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('id_type');
    $table->string('id_number');
    $table->string('document_path')->nullable();  
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('user_verifications');
    }
};
