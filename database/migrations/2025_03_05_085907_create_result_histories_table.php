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
        // Skip this migration if the results table doesn't exist yet
        // It will be handled by the fix_migration_issues migration
        if (!Schema::hasTable('results')) {
            return;
        }
        
        // This migration depends on the results table
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_histories');
    }
};
