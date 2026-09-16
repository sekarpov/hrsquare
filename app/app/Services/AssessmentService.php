<?php

namespace App\Services;

use App\Domain\AssessmentCalculator;
use App\Enums\AssessmentStatus;
use App\Models\Candidate;
use App\Models\CandidateAssessment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssessmentService
{
    public function __construct(private AssessmentCalculator $calculator) {}

    public function create(Candidate $candidate, User $user, array $data): CandidateAssessment
    {
        return DB::transaction(function () use ($candidate, $user, $data) {
            Candidate::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('create', [CandidateAssessment::class, $candidate]);
            $assessment = new CandidateAssessment($data);
            $assessment->candidate_id = $candidate->id;
            $assessment->evaluator_id = $user->id;
            $assessment->status = AssessmentStatus::DRAFT;
            $this->calculator->apply($assessment);
            $assessment->save();

            return $assessment->load('evaluator');
        });
    }

    public function save(CandidateAssessment $assessment, User $user, array $data, bool $complete = false): CandidateAssessment
    {
        return DB::transaction(function () use ($assessment, $user, $data, $complete) {
            $assessment = CandidateAssessment::whereKey($assessment->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize($complete ? 'complete' : 'update', $assessment);
            $assessment->fill($data);
            $this->calculator->apply($assessment);
            if ($complete) {
                $assessment->status = AssessmentStatus::COMPLETED;
                $assessment->completed_at = now();
            }
            $assessment->save();

            return $assessment->load('evaluator');
        });
    }
}
