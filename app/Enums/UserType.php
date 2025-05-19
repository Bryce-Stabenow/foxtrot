<?php

namespace App\Enums;

enum UserType: string
{
    case MEMBER = 'member';
    case ADMIN = 'admin';
}
