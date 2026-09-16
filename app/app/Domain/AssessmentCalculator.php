<?php

namespace App\Domain;

use App\Models\CandidateAssessment;

class AssessmentCalculator
{
    public function __construct(private AssessmentLevelCalculator $levels, private NineBoxCalculator $boxes) {}

    public function apply(CandidateAssessment $assessment): void
    {
        $result = $this->average([$assessment->task_scale_score, $assessment->result_impact_score, $assessment->personal_contribution_score]);
        $potential = $this->average([$assessment->learning_agility_score, $assessment->adaptability_score, $assessment->initiative_score]);
        $assessment->result_average = $result;
        $assessment->potential_average = $potential;
        $assessment->result_level = $result === null ? null : $this->levels->calculate($result);
        $assessment->potential_level = $potential === null ? null : $this->levels->calculate($potential);
        $assessment->nine_box_cell = ($result === null || $potential === null) ? null : $this->boxes->calculate($assessment->result_level, $assessment->potential_level);
    }

    private function average(array $scores): ?float
    {
        return in_array(null, $scores, true) ? null : array_sum($scores) / count($scores);
    }
}
