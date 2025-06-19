<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RoomReservation;

class GenerateWeeklyReservations extends Command
{
    protected $signature = 'reservations:generate-weekly';
    protected $description = 'No-op: Reservations are handled dynamically based on repeat_option';

    public function handle()
    {
        $count = RoomReservation::where('repeat_option', 'weekly')
            ->where('status', 0)
            ->count();

        $this->info("Currently active weekly recurring reservations: {$count}");
        $this->info("No new reservations created, handled dynamically in availability logic.");

        return 0;
    }
}