<?php

namespace App\Http\Requests;

use App\Enums\CandidateStatus;
use App\Enums\UserRole;
use App\Models\Candidate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('candidate') ? $this->user()->can('update', $this->route('candidate')) : $this->user()->can('create', Candidate::class);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->route('candidate') && $this->user()?->role === UserRole::MANAGER) {
            $ids = $this->input('hiringManagerIds', []);
            if (is_array($ids)) {
                $this->merge(['hiringManagerIds' => array_values(array_unique([...$ids, $this->user()->id]))]);
            }
        }
    }

    public function rules(): array
    {
        return ['fullName' => ['required', 'string', 'max:255'], 'position' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255', Rule::exists('cities', 'name')], 'company' => ['nullable', 'string', 'max:255'], 'division' => ['nullable', 'string', 'max:255'], 'project' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(CandidateStatus::class)],
            'hiringManagerIds' => ['required', 'array', 'min:1'], 'hiringManagerIds.*' => ['required', 'integer', 'distinct', $this->participantRule(UserRole::MANAGER, 'hiringManagers')],
            'recruiterIds' => ['present', 'array'], 'recruiterIds.*' => ['required', 'integer', 'distinct', $this->participantRule(UserRole::RECRUITER, 'recruiters')]];
    }

    private function participantRule(UserRole $role, string $relation)
    {
        $retained = $this->route('candidate')?->{$relation}()->pluck('users.id')->all() ?? [];

        return Rule::exists('users', 'id')->where('is_active', true)->where(fn ($query) => $query->where(fn ($query) => $query->where('role', $role->value)->orWhereIn('id', $retained)));
    }

    public function messages(): array
    {
        return ['fullName.required' => 'Поле ФИО обязательно.', 'position.required' => 'Поле должность обязательно.', 'city.exists' => 'Выберите город из справочника или сначала добавьте новый.', 'hiringManagerIds.required' => 'Выберите хотя бы одного менеджера.', 'hiringManagerIds.min' => 'Выберите хотя бы одного менеджера.', 'hiringManagerIds.*.exists' => 'Выбранный пользователь должен быть активным менеджером.', 'recruiterIds.*.exists' => 'Выбранный пользователь должен быть активным рекрутером.'];
    }

    public function candidateData(): array
    {
        return collect($this->safe()->except(['hiringManagerIds', 'recruiterIds']))->mapWithKeys(fn ($v, $k) => [Str::snake($k) => $v])->all();
    }
}
