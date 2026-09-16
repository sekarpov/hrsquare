<?php

namespace App\Enums;

enum CandidateStatus: string
{
    case ACTIVE = 'ACTIVE';
    case HIRED = 'HIRED';
    case REJECTED = 'REJECTED';
}
