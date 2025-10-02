<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AttendanceRemarksService;
use App\Models\AttendanceRecord;

class UpdateAttendanceRemarks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:update-remarks {--faculty-id= : Update remarks for specific faculty ID} {--date= : Update remarks for specific date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update attendance remarks based on leave and pass slip records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $remarksService = new AttendanceRemarksService();
        
        $facultyId = $this->option('faculty-id');
        $date = $this->option('date');

        if ($facultyId) {
            $this->info("Updating attendance remarks for faculty ID: {$facultyId}");
            $remarksService->updateAttendanceRemarksForFaculty($facultyId);
            $this->info("Completed updating remarks for faculty ID: {$facultyId}");
        } elseif ($date) {
            $this->info("Updating attendance remarks for date: {$date}");
            $remarksService->updateAttendanceRemarksForDate($date);
            $this->info("Completed updating remarks for date: {$date}");
        } else {
            $this->info("Updating attendance remarks for all records...");
            
            $totalRecords = AttendanceRecord::count();
            $this->info("Found {$totalRecords} attendance records to process.");
            
            $bar = $this->output->createProgressBar($totalRecords);
            $bar->start();
            
            AttendanceRecord::with(['teachingLoad'])->chunk(100, function ($records) use ($remarksService, $bar) {
                foreach ($records as $record) {
                    $remarksService->updateSingleAttendanceRemarks($record);
                    $bar->advance();
                }
            });
            
            $bar->finish();
            $this->newLine();
            $this->info("Completed updating all attendance remarks.");
        }
    }
}