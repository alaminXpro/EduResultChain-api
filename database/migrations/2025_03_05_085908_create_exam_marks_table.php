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
        Schema::create('exam_marks', function (Blueprint $table) {
            $table->id('detail_id');
            $table->string('roll_number', 20);
            $table->unsignedBigInteger('subject_id');
            $table->float('marks_obtained');
            $table->string('grade', 5)->nullable();
            $table->float('grade_point')->nullable();
            $table->unsignedInteger('entered_by')->nullable();
            $table->timestamps();
            
            $table->foreign('roll_number')
                  ->references('roll_number')
                  ->on('form_fillups')
                  ->onDelete('cascade');
                  
            $table->foreign('subject_id')
                  ->references('subject_id')
                  ->on('subjects')
                  ->onDelete('restrict');
                  
            $table->foreign('entered_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            // Ensure a student can't have duplicate marks for the same subject
            $table->unique(['roll_number', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_marks');
    }
};
