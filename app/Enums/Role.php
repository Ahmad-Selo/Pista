<?php

namespace App\Enums;

enum Role: string
{
    case OWNER = 'OWNER';

    case ADMIN = 'ADMIN';
    case USER = 'USER';
}
