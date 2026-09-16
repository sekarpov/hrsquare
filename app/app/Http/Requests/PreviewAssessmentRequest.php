<?php

namespace App\Http\Requests;

class PreviewAssessmentRequest extends AssessmentRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
