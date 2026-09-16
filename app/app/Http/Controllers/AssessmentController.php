<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssessmentRequest;
use App\Http\Requests\CompleteAssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Models\Candidate;
use App\Models\CandidateAssessment;
use App\Services\AssessmentService;
use Illuminate\Support\Facades\Gate;

class AssessmentController extends Controller
{
    public function index(Candidate $candidate)
    {
        Gate::authorize('view', $candidate);

        return AssessmentResource::collection($candidate->assessments()->with('evaluator')->orderByDesc('created_at')->orderByDesc('id')->paginate(20));
    }

    public function store(AssessmentRequest $request, Candidate $candidate, AssessmentService $service): AssessmentResource
    {
        return new AssessmentResource($service->create($candidate, $request->user(), $request->assessmentData()));
    }

    public function show(CandidateAssessment $assessment): AssessmentResource
    {
        Gate::authorize('view', $assessment);

        return new AssessmentResource($assessment->load('evaluator'));
    }

    public function update(AssessmentRequest $request, CandidateAssessment $assessment, AssessmentService $service): AssessmentResource
    {
        return new AssessmentResource($service->save($assessment, $request->user(), $request->assessmentData()));
    }

    public function complete(CompleteAssessmentRequest $request, CandidateAssessment $assessment, AssessmentService $service): AssessmentResource
    {
        return new AssessmentResource($service->save($assessment, $request->user(), $request->assessmentData(), true));
    }
}
