<?php

namespace App\Domain;

use App\Enums\AssessmentLevel;
use App\Enums\NineBoxCell;

class NineBoxCalculator
{
    public function calculate(AssessmentLevel $result, AssessmentLevel $potential): NineBoxCell
    {
        $column = match ($result) {
            AssessmentLevel::LOW => 'M', AssessmentLevel::MEDIUM => 'S', AssessmentLevel::HIGH => 'B'
        };
        $row = match ($potential) {
            AssessmentLevel::HIGH => '1', AssessmentLevel::MEDIUM => '2', AssessmentLevel::LOW => '3'
        };

        return NineBoxCell::from($column.$row);
    }
}
