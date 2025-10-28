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
        Schema::create('tbl_attendance_record_archive', function (Blueprint $table) {
            $table->id('archive_id');
            $table->unsignedBigInteger('original_record_id')->nullable(); // Reference to original ID
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('teaching_load_id'); // Reference to archived teaching load
            $table->unsignedBigInteger('camera_id');
            $table->date('record_date');
            $table->time('record_time_in')->nullable();
            $table->time('record_time_out')->nullable();
            $table->integer('time_duration_seconds')->default(0);
            $table->string('record_status', 50);
            $table->text('record_remarks')->nullable();
            $table->string('school_year', 20);
            $table->string('semester', 20);
            $table->timestamp('archived_at')->useCurrent();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->text('archive_notes')->nullable();
            $table->timestamps();

            $table->foreign('faculty_id')->references('faculty_id')->on('tbl_faculty')->onDelete('cascade');
            $table->foreign('teaching_load_id')->references('archive_id')->on('tbl_teaching_load_archive')->onDelete('cascade');
            $table->foreign('camera_id')->references('camera_id')->on('tbl_camera')->onDelete('cascade');
            $table->foreign('archived_by')->references('user_id')->on('tbl_user')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_attendance_record_archive');
    }
};