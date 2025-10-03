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
        Schema::create('tbl_attendance_record', function (Blueprint $table) {
            $table->id('record_id');
            $table->timestamp('record_date')->useCurrent();
             $table->foreignId('faculty_id')
                  ->constrained('tbl_faculty', 'faculty_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
             $table->foreignId('teaching_load_id')
                  ->constrained('tbl_teaching_load', 'teaching_load_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
                  $table->timestamp('record_time_in')->nullable();
                  $table->timestamp('record_time_out')->nullable();
                  $table->integer('time_duration_seconds')->default(0);
                  $table->string('record_status', 50);
                  $table->string('record_remarks', 50);
            $table->foreignId('camera_id')
                  ->constrained('tbl_camera', 'camera_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_attendance_record');
    }
};
