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
        Schema::create('result_revalidation_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->string('session', 10);
            $table->string('exam_name', 50);
            $table->string('roll_number', 20);
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->text('reason')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('processed_by')->nullable();
            
            $table->foreign('roll_number')
                  ->references('roll_number')
                  ->on('form_fillups')
                  ->onDelete('cascade');
                  
            $table->foreign('processed_by')
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
        Schema::dropIfExists('result_revalidation_requests');
    }
};
