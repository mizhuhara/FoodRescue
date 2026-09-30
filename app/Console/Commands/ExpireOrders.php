<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpireOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Expire unclaimed orders whose pickup window has passed';

    public function handle(): int
    {
        $expired = Order::query()
            ->where('status', OrderStatus::Pending)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No orders to expire.');

            return self::SUCCESS;
        }

        $service = app(OrderService::class);

        foreach ($expired as $order) {
            try {
                $service->updateStatus($order, OrderStatus::Expired);
            } catch (\InvalidArgumentException $e) {
                $this->warn("Order {$order->order_number} skipped: {$e->getMessage()}");

                continue;
            }
        }

        $this->info("Expired {$expired->count()} orders.");

        return self::SUCCESS;
    }
}
