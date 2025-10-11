<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TeachingLoad;
use Carbon\Carbon;

class TestTimeOverlap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teaching-load:test-overlap {day} {time_in} {time_out} {room}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test time overlap validation for teaching loads';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $day = $this->argument('day');
        $timeIn = $this->argument('time_in');
        $timeOut = $this->argument('time_out');
        $room = $this->argument('room');

        $this->info("Testing time overlap validation:");
        $this->line("Day: {$day}");
        $this->line("Time In: {$timeIn}");
        $this->line("Time Out: {$timeOut}");
        $this->line("Room: {$room}");
        $this->newLine();

        // Get existing teaching loads for the same day and room
        $existingLoads = TeachingLoad::where('teaching_load_day_of_week', $day)
            ->where('room_no', $room)
            ->get();

        $this->info("Found {$existingLoads->count()} existing teaching loads for {$day} in Room {$room}:");

        foreach ($existingLoads as $load) {
            $this->line("  - {$load->teaching_load_course_code}: {$load->teaching_load_time_in} - {$load->teaching_load_time_out}");
        }

        $this->newLine();

        // Test time parsing
        $this->info("Testing time parsing:");
        try {
            $newStart = $this->parseTime($timeIn);
            $newEnd = $this->parseTime($timeOut);
            $this->line("✅ New times parsed successfully: {$newStart->format('H:i:s')} to {$newEnd->format('H:i:s')}");
        } catch (\Exception $e) {
            $this->error("❌ Error parsing new times: " . $e->getMessage());
            return 1;
        }

        // Check for overlaps
        $this->info("Checking for overlaps:");
        $hasOverlap = false;

        foreach ($existingLoads as $load) {
            try {
                $existingStart = $this->parseTime($load->teaching_load_time_in);
                $existingEnd = $this->parseTime($load->teaching_load_time_out);
                
                $this->line("  Comparing with: {$load->teaching_load_course_code} ({$existingStart->format('H:i:s')} - {$existingEnd->format('H:i:s')})");
                
                if ($newStart->lt($existingEnd) && $existingStart->lt($newEnd)) {
                    $this->error("  ❌ OVERLAP DETECTED!");
                    $hasOverlap = true;
                } else {
                    $this->line("  ✅ No overlap");
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error parsing existing time: " . $e->getMessage());
            }
        }

        if ($hasOverlap) {
            $this->error("Result: Time overlap detected - scheduling conflict!");
            return 1;
        } else {
            $this->info("Result: No time overlap - scheduling is clear!");
            return 0;
        }
    }

    /**
     * Parse time string to Carbon instance, handling multiple formats
     */
    private function parseTime($timeString)
    {
        if (empty($timeString)) {
            throw new \InvalidArgumentException('Time string is empty');
        }

        // Try different time formats
        $formats = ['H:i:s', 'H:i', 'g:i A', 'g:i:s A'];
        
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $timeString);
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // If all formats fail, try Carbon's flexible parsing
        try {
            return Carbon::parse($timeString);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Unable to parse time: {$timeString}");
        }
    }
}
