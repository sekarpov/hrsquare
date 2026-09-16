<?php

namespace App\Http\Requests;

use Illuminate\Support\Str;

class CompleteAssessmentRequest extends AssessmentRequest
{
    protected function prepareForValidation(): void
    {
        $assessment = $this->route('assessment');
        if (! $assessment || ! $this->user()?->can('complete', $assessment)) {
            return;
        }
        $defaults = [];
        foreach (array_keys(config('assessment.criteria')) as $key) {
            $defaults[$key.'Score'] = $assessment->{Str::snake($key).'_score'};
        }
        $this->mergeIfMissing($defaults);
    }

    public function authorize(): bool
    {
        return $this->user()->can('complete', $this->route('assessment'));
    }

    public function rules(): array
    {
        $rules = parent::rules();
        foreach (array_keys(config('assessment.criteria')) as $key) {
            $rules[$key.'Score'] = ['required', 'integer', 'between:'.config('assessment.score_min').','.config('assessment.score_max')];
        }

        return $rules;
    }
}
