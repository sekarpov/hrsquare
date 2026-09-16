<?php

namespace App\Http\Requests;

use App\Enums\AssessmentStatus;
use App\Enums\CalibrationSignal;
use App\Enums\MainRisk;
use App\Models\CandidateAssessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('assessment') ? $this->user()->can('update', $this->route('assessment')) : $this->user()->can('create', [CandidateAssessment::class, $this->route('candidate')]);
    }

    public function rules(): array
    {
        $rules = ['status' => ['sometimes', Rule::in([AssessmentStatus::DRAFT->value])], 'calibrationSignal' => ['sometimes', Rule::enum(CalibrationSignal::class)], 'mainRisk' => ['sometimes', Rule::enum(MainRisk::class)], 'finalComment' => ['nullable', 'string', 'max:10000']];
        foreach (array_keys(config('assessment.criteria')) as $key) {
            $rules[$key.'Score'] = ['nullable', 'integer', 'between:'.config('assessment.score_min').','.config('assessment.score_max')];
            $rules[$key.'Evidence'] = ['nullable', 'string', 'max:10000'];
        }

        return $rules;
    }

    public function assessmentData(): array
    {
        return collect($this->safe()->except('status'))->mapWithKeys(fn ($v, $k) => [Str::snake($k) => $v])->all();
    }
}
