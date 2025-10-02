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
        Schema::create('tbl_faculty', function (Blueprint $table) {
            $table->id('faculty_id');
            $table->string('faculty_fname', 50);
            $table->string('faculty_lname', 50);
            $table->string('faculty_department', 50);
            $table->longText('faculty_images');
            $table->text('faculty_face_embedding')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_faculty');
    }
};
