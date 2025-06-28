<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RoomReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // ✅ 1. Import the Log facade

class GenerateWeeklyReservations extends Command
{
    protected $signature = 'reservations:generate-weekly';
    protected $description = 'Reset cancelled weekly reservations for the same weekday of current week';

    public function handle()
    {
        // ✅ 2. Add a log message to indicate the command has started
        Log::info('Running GenerateWeeklyReservations command.');

        $today = Carbon::today();
        $thisWeekday = $today->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

        $reservations = RoomReservation::where('repeat_option', 'weekly')
            ->where('status', 1) // previously cancelled
            ->get();

        $count = 0;

        foreach ($reservations as $res) {
            $originalDate = Carbon::parse($res->date);
            $originalWeekday = $originalDate->dayOfWeek;

            if ($originalWeekday === $thisWeekday) {
                $res->update(['status' => 0]);
                
                // ✅ 3. Log each reactivation instead of writing to the console
                Log::info("Reactivated reservation ID #{$res->id} (Original Date: {$originalDate->toDateString()}) for weekday {$thisWeekday}.");
                
                $count++;
            }
        }

        // ✅ 4. Add a summary log message at the end
        if ($count > 0) {
            Log::info("Finished GenerateWeeklyReservations command. Reactivated {$count} reservations for today (weekday {$thisWeekday}).");
        } else {
            Log::info("Finished GenerateWeeklyReservations command. No weekly reservations found for reactivation today (weekday {$thisWeekday}).");
        }

        return 0;
    }
}