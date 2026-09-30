<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Barber = 'barber';
    case Client = 'client';
}