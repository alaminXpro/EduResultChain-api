<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration is designed to fix any issues with the previous migrations.
     * It will run after all other migrations due to its high timestamp.
     */
    public function up(): void
    {
        // Check if the results table exists
        if (!Schema::hasTable('results')) {
            // Create the results table if it doesn't exist
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

        // Check if the result_histories table exists
        if (!Schema::hasTable('result_histories')) {
            // Create the result_histories table if it doesn't exist
            Schema::create('result_histories', function (Blueprint $table) {
                $table->id('audit_id');
                $table->string('result_id');
                $table->unsignedInteger('modified_by')->nullable()->comment('User ID of board employee');
                $table->enum('modification_type', ['marks_update', 'revalidation_update', 'publication'])->comment('Type of modification');
                $table->json('previous_data')->nullable()->comment('Snapshot before modification');
                $table->json('new_data')->nullable()->comment('Snapshot after modification');
                $table->string('previous_ipfs_hash')->nullable();
                $table->string('new_ipfs_hash')->nullable();
                $table->timestamp('timestamp')->useCurrent();
                
                $table->foreign('result_id')
                      ->references('result_id')
                      ->on('results')
                      ->onDelete('cascade');
                      
                $table->foreign('modified_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to drop tables here as they will be dropped by their respective migrations
    }
}; 