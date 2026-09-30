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
}
