<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $this->ownsBusiness($user, $order);
    }

    /**
     * Customer-owned action: cancelling a pending order.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->ownsBusiness($user, $order);
    }

    public function verifyPickup(User $user, Order $order): bool
    {
        return $this->ownsBusiness($user, $order);
    }

    private function ownsBusiness(User $user, Order $order): bool
    {
        return $user->partner !== null && $user->partner->id === $order->partner_id;
    }
}
