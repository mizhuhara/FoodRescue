<?php

namespace App\Enums;

enum FoodStatus: string
{
    case Draft = 'DRAFT';
    case Available = 'AVAILABLE';
    case SoldOut = 'SOLD_OUT';
    case Expired = 'EXPIRED';
    case Inactive = 'INACTIVE';
}
