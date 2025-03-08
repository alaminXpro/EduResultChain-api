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
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 20);
            $table->string('phone_number', 15);
            $table->string('verification_code', 6)->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('code_expires_at')->nullable();
            $table->timestamps();
            
            $table->foreign('registration_number')
                  ->references('registration_number')
                  ->on('students')
                  ->onDelete('cascade');
                  
            // Ensure a student can only have one verification record
            $table->unique(['registration_number', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
