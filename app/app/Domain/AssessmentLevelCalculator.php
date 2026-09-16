<?php

namespace App\Domain;

use App\Enums\AssessmentLevel;
use InvalidArgumentException;

class AssessmentLevelCalculator
{
    public function calculate(float $score): AssessmentLevel
    {
        if (! is_finite($score) || $score < config('assessment.score_min') || $score > config('assessment.score_max')) {
            throw new InvalidArgumentException('Score must be between 1 and 4');
        }

        return match (true) {
            $score < config('assessment.medium_threshold') => AssessmentLevel::LOW,
            $score < config('assessment.high_threshold') => AssessmentLevel::MEDIUM,
            default => AssessmentLevel::HIGH,
        };
    }
}
