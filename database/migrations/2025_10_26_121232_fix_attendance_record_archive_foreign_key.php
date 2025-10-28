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
        Schema::table('tbl_attendance_record_archive', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['archived_by']);
            
            // Add the correct foreign key constraint
            $table->foreign('archived_by')->references('user_id')->on('tbl_user')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_attendance_record_archive', function (Blueprint $table) {
            // Drop the correct foreign key constraint
            $table->dropForeign(['archived_by']);
            
            // Add back the original foreign key constraint
            $table->foreign('archived_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};