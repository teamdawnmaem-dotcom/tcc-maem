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
        Schema::create('tbl_leave_pass', function (Blueprint $table) {
            $table->id('lp_id');
             $table->foreignId('faculty_id')
                  ->constrained('tbl_faculty', 'faculty_id')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
            $table->string('lp_type', 50);
            $table->string('pass_slip_itinerary', 50)->nullable();
            $table->string('lp_purpose', 255);
            $table->date('pass_slip_date')->nullable();
            $table->time('pass_slip_departure_time')->nullable();
            $table->time('pass_slip_arrival_time')->nullable();
            $table->date('leave_start_date')->nullable();
            $table->date('leave_end_date')->nullable();
            $table->string('lp_image',255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_leave_pass');
    }
};
