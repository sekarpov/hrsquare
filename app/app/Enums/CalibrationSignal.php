<?php

namespace App\Enums;

enum CalibrationSignal: string
{
    case NONE = 'NONE';
    case ALIGNED = 'ALIGNED';
    case NEEDS_CALIBRATION = 'NEEDS_CALIBRATION';
}
