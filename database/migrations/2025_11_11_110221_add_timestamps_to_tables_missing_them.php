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
        // Add timestamps to tbl_attendance_record if they don't exist
        if (Schema::hasTable('tbl_attendance_record')) {
            Schema::table('tbl_attendance_record', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_attendance_record', 'created_at')) {
                    $table->timestamp('created_at')->nullable()->after('camera_id');
                }
                if (!Schema::hasColumn('tbl_attendance_record', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
        }
        
        // Add timestamps to tbl_recognition_logs if they don't exist
        if (Schema::hasTable('tbl_recognition_logs')) {
            Schema::table('tbl_recognition_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_recognition_logs', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('tbl_recognition_logs', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
        
        // Add timestamps to tbl_activity_logs if they don't exist
        if (Schema::hasTable('tbl_activity_logs')) {
            Schema::table('tbl_activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_activity_logs', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('tbl_activity_logs', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove timestamps if they were added
        if (Schema::hasTable('tbl_attendance_record')) {
            Schema::table('tbl_attendance_record', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_attendance_record', 'created_at')) {
                    $table->dropColumn('created_at');
                }
                if (Schema::hasColumn('tbl_attendance_record', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
        
        if (Schema::hasTable('tbl_recognition_logs')) {
            Schema::table('tbl_recognition_logs', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_recognition_logs', 'created_at')) {
                    $table->dropColumn('created_at');
                }
                if (Schema::hasColumn('tbl_recognition_logs', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
        
        if (Schema::hasTable('tbl_activity_logs')) {
            Schema::table('tbl_activity_logs', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_activity_logs', 'created_at')) {
                    $table->dropColumn('created_at');
                }
                if (Schema::hasColumn('tbl_activity_logs', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }
            });
        }
    }
};
