<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RoomReservation;
use Carbon\Carbon;

class GenerateWeeklyReservations extends Command
{
    protected $signature = 'reservations:generate-weekly';
    protected $description = 'Auto-generate weekly recurring reservations';

    public function handle()
    {
        $today = Carbon::today();
        $nextWeek = $today->copy()->addWeek(); // jump 1 week ahead

        $recurring = RoomReservation::where('repeat_option', 'weekly')
            ->where('status', 0)
            ->get();

        foreach ($recurring as $res) {
            $originalDate = Carbon::parse($res->date);
            $dayOfWeek = $originalDate->dayOfWeek;

            // Get the exact same weekday for next week
            $targetDate = $nextWeek->copy()->startOfWeek()->addDays($dayOfWeek);

            // Check if it already exists
            $exists = RoomReservation::where('room_id', $res->room_id)
                ->where('user_id', $res->user_id)
                ->where('date', $targetDate->toDateString())
                ->where('start_time', $res->start_time)
                ->exists();

            if ($exists) {
                $this->line("Skipped existing reservation for {$targetDate->toDateString()}");
                continue;
            }

            RoomReservation::create([
                'room_id' => $res->room_id,
                'user_id' => $res->user_id,
                'date' => $targetDate->toDateString(),
                'start_time' => $res->start_time,
                'end_time' => $res->end_time,
                'duration_minutes' => $res->duration_minutes,
                'repeat_option' => 'weekly',
                'all_day' => $res->all_day,
                'status' => 0,
            ]);

            $this->info("Created recurring reservation for Room {$res->room_id} on {$targetDate->toDateString()}");
        }

        return 0;
    }
}