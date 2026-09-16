<?php

namespace App\Http\Requests;

use App\Enums\AssessmentLevel;
use App\Enums\CandidateStatus;
use App\Enums\NineBoxCell;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];
        foreach (['search', 'position', 'city', 'company', 'division', 'project'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        return $rules + ['managerId' => ['nullable', 'integer', 'min:1'], 'recruiterId' => ['nullable', 'integer', 'min:1'], 'status' => ['nullable', Rule::enum(CandidateStatus::class)], 'resultLevel' => ['nullable', Rule::enum(AssessmentLevel::class)], 'potentialLevel' => ['nullable', Rule::enum(AssessmentLevel::class)], 'nineBoxCell' => ['nullable', Rule::enum(NineBoxCell::class)], 'page' => ['nullable', 'integer', 'min:1'], 'perPage' => ['nullable', 'integer', 'min:1', 'max:100'], 'sort' => ['nullable', Rule::in(['fullName', 'position', 'city', 'company', 'division', 'project', 'status', 'createdAt', 'updatedAt'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])]];
    }
}
