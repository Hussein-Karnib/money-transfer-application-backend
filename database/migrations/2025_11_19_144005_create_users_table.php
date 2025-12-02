<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->default(1) 
                ->constrained('roles');

            $table->string('name');
            $table->string('email')->unique()->nullable();   
            $table->timestamp('email_verified_at')->nullable();

            $table->string('password')->nullable();          
            $table->string('phone')->nullable();

            $table->string('status')->default('pending');   

            $table->string('provider_name')->nullable();     
            $table->string('provider_id')->nullable();       
            $table->string('avatar_url')->nullable();        

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
