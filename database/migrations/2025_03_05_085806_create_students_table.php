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
        Schema::create('students', function (Blueprint $table) {
            $table->string('registration_number', 20)->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->string('father_name');
            $table->string('mother_name');
            $table->string('phone_number', 15)->unique()->comment('Used for verification');
            $table->string('email')->nullable();
            $table->string('image')->nullable();
            $table->text('permanent_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
