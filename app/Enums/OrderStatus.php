<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'PENDING';
    case Confirmed = 'CONFIRMED';
    case Preparing = 'PREPARING';
    case ReadyForPickup = 'READY_FOR_PICKUP';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case Expired = 'EXPIRED';

    /**
     * Statuses that can never transition further.
     *
     * @return list<self>
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }

    /**
     * State machine for the order lifecycle.
     *
     * Pending     -> Confirmed | Cancelled | Expired
     * Confirmed   -> Preparing
     * Preparing   -> ReadyForPickup
     * ReadyForPickup -> Completed
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled, self::Expired],
            self::Confirmed => [self::Preparing],
            self::Preparing => [self::ReadyForPickup],
            self::ReadyForPickup => [self::Completed],
            default => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
