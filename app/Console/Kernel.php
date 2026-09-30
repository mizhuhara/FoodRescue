<?php

namespace App\Console;

use App\Enums\FoodStatus;
use App\Enums\OrderStatus;
use App\Models\Food;
use App\Models\Order;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Order::where('status', OrderStatus::Pending->value)
                ->where('expires_at', '<', now())
                ->update(['status' => OrderStatus::Expired->value]);
        })->everyMinute();

        $schedule->call(function () {
            Food::where('status', FoodStatus::Available->value)
                ->where('pickup_end', '<', now()->format('H:i'))
                ->update(['status' => FoodStatus::Expired->value]);
        })->everyMinute();
    }
}
