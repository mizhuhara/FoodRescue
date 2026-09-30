<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'CUSTOMER';
    case Partner = 'PARTNER';
    case Admin = 'ADMIN';
}
