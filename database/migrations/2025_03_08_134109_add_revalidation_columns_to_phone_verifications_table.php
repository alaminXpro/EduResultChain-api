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
        Schema::table('phone_verifications', function (Blueprint $table) {
            $table->boolean('revalidation_verified')->default(false);
            $table->timestamp('revalidation_verified_at')->nullable();
            $table->timestamp('revalidation_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_verifications', function (Blueprint $table) {
            $table->dropColumn('revalidation_verified');
            $table->dropColumn('revalidation_verified_at');
            $table->dropColumn('revalidation_expires_at');
        });
    }
};
