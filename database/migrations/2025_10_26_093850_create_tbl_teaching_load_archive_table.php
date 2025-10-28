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
        Schema::create('tbl_teaching_load_archive', function (Blueprint $table) {
            $table->id('archive_id');
            $table->unsignedBigInteger('original_teaching_load_id');
            $table->unsignedBigInteger('faculty_id');
            $table->string('teaching_load_course_code', 50);
            $table->string('teaching_load_subject', 50);
            $table->string('teaching_load_class_section', 50);
            $table->string('teaching_load_day_of_week', 50);
            $table->time('teaching_load_time_in');
            $table->time('teaching_load_time_out');
            $table->string('room_no', 50);
            $table->string('school_year', 20); // e.g., "2023-2024"
            $table->string('semester', 20); // e.g., "1st Semester", "2nd Semester"
            $table->timestamp('archived_at');
            $table->unsignedBigInteger('archived_by'); // User who archived it
            $table->text('archive_notes')->nullable(); // Optional notes about the archive
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_teaching_load_archive');
    }
};