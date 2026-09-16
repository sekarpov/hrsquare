<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case RECRUITER = 'RECRUITER';
    case MANAGER = 'MANAGER';
}
