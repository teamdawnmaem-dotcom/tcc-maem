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
        Schema::create('tbl_recognition_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->timestamp('recognition_time')->useCurrent();
            $table->string('camera_name', 100);
            $table->string('room_name', 100);
            $table->string('building_no', 50);
            $table->string('faculty_name', 200);
            $table->string('status', 50); // recognized, unknown_face, etc.
            $table->decimal('distance', 8, 6)->nullable(); // face recognition distance
             $table->foreignId('faculty_id')->nullable()
                  ->constrained('tbl_faculty', 'faculty_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
            $table->foreignId('camera_id')->nullable()
                  ->constrained('tbl_camera', 'camera_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
             $table->foreignId('teaching_load_id')->nullable()
                  ->constrained('tbl_teaching_load', 'teaching_load_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_recognition_logs');
    }
};
