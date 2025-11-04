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
        Schema::create('tbl_stream_recordings', function (Blueprint $table) {
            $table->bigIncrements('recording_id'); // BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('camera_id');
            $table->string('filename');
            $table->string('filepath');
            $table->dateTime('start_time');
            $table->integer('duration'); // Duration in seconds
            $table->integer('frames'); // Number of frames recorded
            $table->bigInteger('file_size'); // File size in bytes
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('camera_id')
                  ->references('camera_id')
                  ->on('tbl_camera')
                  ->onDelete('cascade');
            
            // Index for faster queries
            $table->index('camera_id');
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_stream_recordings');
    }
};
