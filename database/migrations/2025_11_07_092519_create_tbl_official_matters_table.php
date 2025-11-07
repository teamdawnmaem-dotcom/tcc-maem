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
        Schema::create('tbl_official_matters', function (Blueprint $table) {
            $table->id('om_id');
            $table->foreignId('faculty_id')
                ->nullable()
                ->constrained('tbl_faculty', 'faculty_id')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('om_department', 255)->nullable(); // For department selection: All Instructor, or specific colleges
            $table->string('om_purpose', 255);
            $table->string('om_remarks', 255);
            $table->date('om_start_date');
            $table->date('om_end_date');
            $table->string('om_attachment', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_official_matters');
    }
};
