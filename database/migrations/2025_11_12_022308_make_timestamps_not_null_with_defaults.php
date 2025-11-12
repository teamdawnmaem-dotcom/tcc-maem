<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes all created_at and updated_at columns NOT NULL with proper defaults
     */
    public function up(): void
    {
        // List of all tables that should have timestamps
        $tables = [
            'tbl_user',
            'tbl_room',
            'tbl_faculty',
            'tbl_camera',
            'tbl_teaching_load',
            'tbl_leave_pass',
            'tbl_attendance_record',
            'tbl_subject',
            'tbl_recognition_logs',
            'tbl_stream_recordings',
            'tbl_activity_logs',
            'tbl_official_matters',
            'tbl_teaching_load_archive',
            'tbl_attendance_record_archive',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            // Update NULL values to current timestamp before changing column definition
            if (Schema::hasColumn($tableName, 'created_at')) {
                DB::table($tableName)
                    ->whereNull('created_at')
                    ->update(['created_at' => now()]);
            }

            if (Schema::hasColumn($tableName, 'updated_at')) {
                DB::table($tableName)
                    ->whereNull('updated_at')
                    ->update(['updated_at' => now()]);
            }

            // Modify columns to be NOT NULL with defaults
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_at')) {
                    // Set created_at to NOT NULL with CURRENT_TIMESTAMP default
                    $table->timestamp('created_at')->nullable(false)->useCurrent()->change();
                }

                if (Schema::hasColumn($tableName, 'updated_at')) {
                    // Set updated_at to NOT NULL with CURRENT_TIMESTAMP default and ON UPDATE
                    $table->timestamp('updated_at')->nullable(false)->useCurrent()->change();
                }
            });

            // Use raw SQL to set ON UPDATE CURRENT_TIMESTAMP for updated_at
            // Laravel's change() method doesn't support ON UPDATE directly
            if (Schema::hasColumn($tableName, 'updated_at')) {
                DB::statement("ALTER TABLE `{$tableName}` MODIFY `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // List of all tables that have timestamps
        $tables = [
            'tbl_user',
            'tbl_room',
            'tbl_faculty',
            'tbl_camera',
            'tbl_teaching_load',
            'tbl_leave_pass',
            'tbl_attendance_record',
            'tbl_subject',
            'tbl_recognition_logs',
            'tbl_stream_recordings',
            'tbl_activity_logs',
            'tbl_official_matters',
            'tbl_teaching_load_archive',
            'tbl_attendance_record_archive',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            // Revert to nullable (without defaults)
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $table->timestamp('created_at')->nullable()->change();
                }

                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->change();
                }
            });
        }
    }
};
