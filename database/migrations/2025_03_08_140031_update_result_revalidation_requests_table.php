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
        // Create the table if it doesn't exist
        if (!Schema::hasTable('result_revalidation_requests')) {
            Schema::create('result_revalidation_requests', function (Blueprint $table) {
                $table->id();
                $table->string('roll_number', 20);
                $table->unsignedBigInteger('subject_id');
                $table->text('reason');
                $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
                $table->unsignedInteger('requested_by');
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('comments')->nullable();
                $table->float('original_marks')->nullable();
                $table->float('updated_marks')->nullable();
                $table->timestamps();
                
                $table->foreign('roll_number')
                      ->references('roll_number')
                      ->on('form_fillups')
                      ->onDelete('cascade');
                      
                $table->foreign('subject_id')
                      ->references('subject_id')
                      ->on('subjects')
                      ->onDelete('cascade');
                      
                $table->foreign('requested_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
                      
                $table->foreign('reviewed_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            });
        } else {
            // Add columns to existing table
            Schema::table('result_revalidation_requests', function (Blueprint $table) {
                // Add missing columns
                if (!Schema::hasColumn('result_revalidation_requests', 'subject_id')) {
                    $table->unsignedBigInteger('subject_id')->after('roll_number');
                }
                
                if (!Schema::hasColumn('result_revalidation_requests', 'requested_by')) {
                    $table->unsignedInteger('requested_by')->after('status');
                }
                
                if (!Schema::hasColumn('result_revalidation_requests', 'reviewed_by')) {
                    $table->unsignedInteger('reviewed_by')->nullable()->after('requested_by');
                }
                
                if (!Schema::hasColumn('result_revalidation_requests', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
                
                if (!Schema::hasColumn('result_revalidation_requests', 'comments')) {
                    $table->text('comments')->nullable()->after('reviewed_at');
                }
                
                if (!Schema::hasColumn('result_revalidation_requests', 'original_marks')) {
                    $table->float('original_marks')->nullable()->after('comments');
                }
                
                if (!Schema::hasColumn('result_revalidation_requests', 'updated_marks')) {
                    $table->float('updated_marks')->nullable()->after('original_marks');
                }
                
                // Add foreign keys if columns exist
                if (Schema::hasColumn('result_revalidation_requests', 'subject_id')) {
                    try {
                        $table->foreign('subject_id')
                              ->references('subject_id')
                              ->on('subjects')
                              ->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Foreign key might already exist
                    }
                }
                
                if (Schema::hasColumn('result_revalidation_requests', 'requested_by')) {
                    try {
                        $table->foreign('requested_by')
                              ->references('id')
                              ->on('users')
                              ->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Foreign key might already exist
                    }
                }
                
                if (Schema::hasColumn('result_revalidation_requests', 'reviewed_by')) {
                    try {
                        $table->foreign('reviewed_by')
                              ->references('id')
                              ->on('users')
                              ->onDelete('set null');
                    } catch (\Exception $e) {
                        // Foreign key might already exist
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('result_revalidation_requests')) {
            Schema::table('result_revalidation_requests', function (Blueprint $table) {
                // Drop foreign keys if they exist
                try {
                    $table->dropForeign(['subject_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                
                try {
                    $table->dropForeign(['requested_by']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                
                try {
                    $table->dropForeign(['reviewed_by']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                
                // Drop columns if they exist
                $columns = [
                    'subject_id',
                    'requested_by',
                    'reviewed_by',
                    'reviewed_at',
                    'comments',
                    'original_marks',
                    'updated_marks'
                ];
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn('result_revalidation_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
