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
        // This migration should run before exam_marks
        Schema::create('subjects', function (Blueprint $table) {
            $table->id('subject_id');
            $table->string('subject_name', 100);
            $table->string('subject_category', 50)->comment('e.g., compulsory, group-specific');
            $table->string('subject_code', 20)->nullable();
            $table->float('full_marks')->default(100);
            $table->float('pass_marks')->default(33);
            $table->timestamps();
            
            // Unique constraint to prevent duplicate subjects
            $table->unique(['subject_name', 'subject_category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
