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
            $table->integer('faculty_id')->nullable();
            $table->integer('camera_id')->nullable();
            $table->integer('teaching_load_id')->nullable();
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
