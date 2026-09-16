<?php

namespace Tests\Unit;

use App\Domain\AssessmentLevelCalculator;
use App\Enums\AssessmentLevel;
use Tests\TestCase;

class AssessmentLevelCalculatorTest extends TestCase
{
    public function test_provisional_boundaries_and_known_examples(): void
    {
        $calculator = app(AssessmentLevelCalculator::class);
        foreach ([[0, 'LOW'], [1.49, 'LOW'], [1.5, 'MEDIUM'], [1.67, 'MEDIUM'], [2, 'MEDIUM'], [2.33, 'MEDIUM'], [2.49, 'MEDIUM'], [2.5, 'HIGH'], [3, 'HIGH']] as [$score,$expected]) {
            $this->assertSame(AssessmentLevel::from($expected), $calculator->calculate($score));
        }
    }

    public function test_rejects_invalid_scores(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(AssessmentLevelCalculator::class)->calculate(3.1);
    }

    public function test_thresholds_are_configurable(): void
    {
        config(['assessment.medium_threshold' => 1.8]);
        $this->assertSame(AssessmentLevel::LOW, app(AssessmentLevelCalculator::class)->calculate(1.67));
    }
}
