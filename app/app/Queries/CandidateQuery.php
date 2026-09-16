<?php

namespace App\Queries;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CandidateQuery
{
    public function build(User $user, array $filters): Builder
    {
        $q = Candidate::query()->visibleTo($user)->with(['hiringManagers', 'recruiters', 'currentAssessment.evaluator']);
        if ($search = $filters['search'] ?? null) {
            $q->where(function (Builder $q) use ($search) {
                foreach (['full_name', 'position', 'city', 'company', 'division', 'project'] as $field) {
                    $q->orWhere($field, 'ilike', '%'.$search.'%');
                }
            });
        }
        foreach (['position', 'city', 'company', 'division', 'project', 'status'] as $field) {
            if (! empty($filters[$field])) {
                $q->where($field, $filters[$field]);
            }
        }
        foreach (['managerId' => 'hiringManagers', 'recruiterId' => 'recruiters'] as $field => $relation) {
            if (! empty($filters[$field])) {
                $q->whereHas($relation, fn (Builder $q) => $q->where('users.id', $filters[$field]));
            }
        }
        $assessmentFilters = array_filter(['result_level' => $filters['resultLevel'] ?? null, 'potential_level' => $filters['potentialLevel'] ?? null, 'nine_box_cell' => $filters['nineBoxCell'] ?? null]);
        if ($assessmentFilters) {
            $q->whereHas('currentAssessment', function (Builder $q) use ($assessmentFilters) {
                foreach ($assessmentFilters as $field => $value) {
                    $q->where($field, $value);
                }
            });
        }

        return $q->orderBy(Str::snake($filters['sort'] ?? 'createdAt'), $filters['direction'] ?? 'desc')->orderBy('candidates.id', 'desc');
    }
}
