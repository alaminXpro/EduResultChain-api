<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * IMPORTANT: This migration MUST run before create_result_histories_table.php
     * If you encounter errors, rename this file to have a timestamp earlier than
     * the result_histories migration.
     */
    public function up(): void
    {
        // Skip if the table already exists (it might have been created by another migration)
        if (Schema::hasTable('results')) {
            return;
        }
        
        // This migration should run before result_histories
        Schema::create('results', function (Blueprint $table) {
            $table->string('result_id')->primary()->comment('Concatenation of exam_name, session, and roll_number');
            $table->string('roll_number', 20);
            $table->string('exam_name', 50);
            $table->string('session', 10);
            $table->float('gpa', 4, 2)->nullable()->comment('Calculated from subject marks');
            $table->string('grade', 5)->nullable()->comment('Calculated from overall performance');
            $table->float('total_marks')->nullable()->comment('Sum of marks from Exam Marks Table');
            $table->enum('status', ['Pass', 'Fail'])->nullable()->comment('Based on criteria');
            $table->string('ipfs_hash')->nullable()->comment('Updated whenever marks are saved/updated');
            $table->boolean('published')->default(false)->comment('Initially set to false/unpublished');
            $table->timestamp('published_at')->nullable()->comment('Updated only when admin publishes');
            $table->unsignedInteger('published_by')->nullable();
            $table->timestamps();
            
            $table->foreign('roll_number')
                  ->references('roll_number')
                  ->on('form_fillups')
                  ->onDelete('cascade');
                  
            $table->foreign('published_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
