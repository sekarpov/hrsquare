<?php

namespace App\Models;

use App\Enums\AssessmentLevel;
use App\Enums\AssessmentStatus;
use App\Enums\CalibrationSignal;
use App\Enums\MainRisk;
use App\Enums\NineBoxCell;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class CandidateAssessment extends Model
{
    protected $fillable = ['task_scale_score', 'task_scale_evidence', 'result_impact_score', 'result_impact_evidence', 'personal_contribution_score', 'personal_contribution_evidence', 'learning_agility_score', 'learning_agility_evidence', 'adaptability_score', 'adaptability_evidence', 'initiative_score', 'initiative_evidence', 'calibration_signal', 'main_risk', 'final_comment'];

    protected function casts(): array
    {
        return ['status' => AssessmentStatus::class, 'result_level' => AssessmentLevel::class, 'potential_level' => AssessmentLevel::class, 'nine_box_cell' => NineBoxCell::class, 'calibration_signal' => CalibrationSignal::class, 'main_risk' => MainRisk::class, 'completed_at' => 'datetime', 'result_average' => 'float', 'potential_average' => 'float',
            'task_scale_score' => 'integer', 'result_impact_score' => 'integer', 'personal_contribution_score' => 'integer', 'learning_agility_score' => 'integer', 'adaptability_score' => 'integer', 'initiative_score' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $assessment) {
            if ($assessment->getRawOriginal('status') === AssessmentStatus::COMPLETED->value) {
                throw ValidationException::withMessages(['status' => ['Завершённая оценка неизменяема. Создайте новую оценку.']]);
            }
        });
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
