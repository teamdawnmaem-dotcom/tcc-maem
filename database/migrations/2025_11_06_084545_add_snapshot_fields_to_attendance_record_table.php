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
        Schema::table('tbl_attendance_record', function (Blueprint $table) {
            $table->string('time_in_snapshot', 500)->nullable()->after('record_time_in');
            $table->string('time_out_snapshot', 500)->nullable()->after('record_time_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_attendance_record', function (Blueprint $table) {
            $table->dropColumn(['time_in_snapshot', 'time_out_snapshot']);
        });
    }
};
