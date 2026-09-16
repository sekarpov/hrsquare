<?php

namespace App\Domain;

use App\Enums\AssessmentLevel;
use InvalidArgumentException;

class AssessmentLevelCalculator
{
    public function calculate(float $score): AssessmentLevel
    {
        if (! is_finite($score) || $score < 0 || $score > 3) {
            throw new InvalidArgumentException('Score must be between 0 and 3');
        }

        return match (true) {
            $score < config('assessment.medium_threshold') => AssessmentLevel::LOW,
            $score < config('assessment.high_threshold') => AssessmentLevel::MEDIUM,
            default => AssessmentLevel::HIGH,
        };
    }
}
