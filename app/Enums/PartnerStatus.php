<?php

namespace App\Enums;

enum PartnerStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Suspended = 'SUSPENDED';
}
