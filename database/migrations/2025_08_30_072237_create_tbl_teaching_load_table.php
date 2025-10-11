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
        Schema::create('tbl_teaching_load', function (Blueprint $table) {
            $table->id('teaching_load_id');
             $table->foreignId('faculty_id')
                  ->constrained('tbl_faculty', 'faculty_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
            $table->string('teaching_load_course_code', 50);
            $table->string('teaching_load_subject', 50);
            $table->string('teaching_load_day_of_week', 50);
            $table->string('teaching_load_class_section', 50);
            $table->time('teaching_load_time_in');
            $table->time('teaching_load_time_out');
            $table->foreignId('room_no')
                  ->constrained('tbl_room', 'room_no')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
            $table->timestamps();
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_teaching_load');
    }
};
