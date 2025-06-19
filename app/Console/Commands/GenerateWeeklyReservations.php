<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RoomReservation;
use Carbon\Carbon;

class GenerateWeeklyReservations extends Command
{
    protected $signature = 'reservations:generate-weekly';
    protected $description = 'Reset cancelled weekly reservations for the same weekday of current week';

    public function handle()
    {
        $today = Carbon::today();
        $thisWeekday = $today->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

        $reservations = RoomReservation::where('repeat_option', 'weekly')
            ->where('status', 1) // previously cancelled
            ->get();

        $count = 0;

        foreach ($reservations as $res) {
            $originalWeekday = Carbon::parse($res->date)->dayOfWeek;

            if ($originalWeekday === $thisWeekday) {
                $res->update(['status' => 0]);
                $this->line("Reactivated reservation ID #{$res->id} for weekday {$thisWeekday}.");
                $count++;
            }
        }

        $this->info("Reactivated {$count} reservations for today (weekday {$thisWeekday}).");
        return 0;
    }
}
