<?php

namespace Tests\Unit;

use App\Domain\NineBoxCalculator;
use App\Enums\AssessmentLevel;
use App\Enums\NineBoxCell;
use Tests\TestCase;

class NineBoxCalculatorTest extends TestCase
{
    public function test_all_nine_cells(): void
    {
        $calculator = app(NineBoxCalculator::class);
        $mapping = [['HIGH', 'LOW', 'M1'], ['HIGH', 'MEDIUM', 'S1'], ['HIGH', 'HIGH', 'B1'], ['MEDIUM', 'LOW', 'M2'], ['MEDIUM', 'MEDIUM', 'S2'], ['MEDIUM', 'HIGH', 'B2'], ['LOW', 'LOW', 'M3'], ['LOW', 'MEDIUM', 'S3'], ['LOW', 'HIGH', 'B3']];
        foreach ($mapping as [$p,$r,$cell]) {
            $this->assertSame(NineBoxCell::from($cell), $calculator->calculate(AssessmentLevel::from($r), AssessmentLevel::from($p)));
        }
    }
}
