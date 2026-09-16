<?php

namespace App\Enums;

enum MainRisk: string
{
    case NONE = 'NONE';
    case DELIVERY = 'DELIVERY';
    case LEARNING = 'LEARNING';
    case ADAPTABILITY = 'ADAPTABILITY';
    case OWNERSHIP = 'OWNERSHIP';
    case MOTIVATION = 'MOTIVATION';
}
