<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Requests\CandidateRequest;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateService
{
    public function save(CandidateRequest $request, ?Candidate $candidate = null): Candidate
    {
        return DB::transaction(function () use ($request, $candidate) {
            $managerIds = $request->validated('hiringManagerIds');
            $recruiterIds = $request->validated('recruiterIds');
            $users = User::whereIn('id', array_unique([...$managerIds, ...$recruiterIds]))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach (['hiringManagerIds' => [$managerIds, UserRole::MANAGER], 'recruiterIds' => [$recruiterIds, UserRole::RECRUITER]] as $field => [$ids, $role]) {
                $relation = $field === 'hiringManagerIds' ? 'hiringManagers' : 'recruiters';
                $retained = $candidate?->{$relation}()->pluck('users.id')->all() ?? [];
                foreach ($ids as $id) {
                    if (! isset($users[$id]) || ! $users[$id]->is_active || ($users[$id]->role !== $role && ! in_array((int) $id, $retained, true))) {
                        throw ValidationException::withMessages([$field => ['Пользователь изменён или отключён. Обновите список участников.']]);
                    }
                }
            }
            $candidate ??= new Candidate;
            $candidate->fill($request->candidateData());
            if (! $candidate->exists) {
                $candidate->created_by = $request->user()->id;
            }
            $candidate->save();
            $candidate->hiringManagers()->sync($request->validated('hiringManagerIds'));
            $candidate->recruiters()->sync($request->validated('recruiterIds'));

            return $candidate->load(['hiringManagers', 'recruiters', 'currentAssessment.evaluator']);
        });
    }

    public function delete(Candidate $candidate): void
    {
        DB::transaction(function () use ($candidate) {
            $candidate = Candidate::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if ($candidate->assessments()->exists()) {
                throw ValidationException::withMessages(['candidate' => ['Кандидат с историей оценок не удаляется. Используйте статус «Отклонён».']]);
            }
            $candidate->delete();
        });
    }
}
