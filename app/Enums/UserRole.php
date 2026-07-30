<?php

namespace App\Enums;

enum UserRole: string
{
    case PARENT = 'parent';
    case NANNY = 'nanny';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
}
