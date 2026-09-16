<?php

namespace App\Policies;

use App\Enums\AssessmentStatus;
use App\Models\Candidate;
use App\Models\CandidateAssessment;
use App\Models\User;

class CandidateAssessmentPolicy
{
    public function view(User $user, CandidateAssessment $assessment): bool
    {
        return $user->can('view', $assessment->candidate);
    }

    public function create(User $user, Candidate $candidate): bool
    {
        return $user->can('view', $candidate);
    }

    public function update(User $user, CandidateAssessment $assessment): bool
    {
        return $assessment->status === AssessmentStatus::DRAFT && ($user->isAdmin() || $assessment->evaluator_id === $user->id) && $this->view($user, $assessment);
    }

    public function complete(User $user, CandidateAssessment $assessment): bool
    {
        return $this->update($user, $assessment);
    }
}
