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
        foreach ([[1, 'LOW'], [2.49, 'LOW'], [2.5, 'MEDIUM'], [2.67, 'MEDIUM'], [3, 'MEDIUM'], [3.33, 'MEDIUM'], [3.49, 'MEDIUM'], [3.5, 'HIGH'], [4, 'HIGH']] as [$score,$expected]) {
            $this->assertSame(AssessmentLevel::from($expected), $calculator->calculate($score));
        }
    }

    public function test_rejects_invalid_scores(): void
    {
        foreach ([0, 0.99, 4.1, INF, NAN] as $score) {
            try {
                app(AssessmentLevelCalculator::class)->calculate($score);
                $this->fail('Invalid average accepted');
            } catch (\InvalidArgumentException $e) {
                $this->assertSame('Score must be between 1 and 4', $e->getMessage());
            }
        }
    }

    public function test_thresholds_are_configurable(): void
    {
        config(['assessment.medium_threshold' => 2.8]);
        $this->assertSame(AssessmentLevel::LOW, app(AssessmentLevelCalculator::class)->calculate(2.67));
    }
}
