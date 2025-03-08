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
        Schema::create('form_fillups', function (Blueprint $table) {
            $table->string('roll_number', 20)->primary();
            $table->string('registration_number', 20);
            $table->string('exam_name', 50)->comment('e.g., SSC, HSC');
            $table->string('session', 10)->comment('Academic year/session');
            $table->string('group', 30)->comment('e.g., Science, Commerce, Arts');
            $table->unsignedInteger('board_id')->comment('Board user entering the data');
            $table->unsignedInteger('institution_id')->comment('Institution registering the student');
            $table->timestamps();
            
            $table->foreign('registration_number')
                  ->references('registration_number')
                  ->on('students')
                  ->onDelete('cascade');
            
            // Assuming you have users table with board and institution users
            $table->foreign('board_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
                  
            $table->foreign('institution_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fillups');
    }
};
