<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateIndexRequest;
use App\Http\Requests\CandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use App\Queries\CandidateQuery;
use App\Services\CandidateService;
use Illuminate\Support\Facades\Gate;

class CandidateController extends Controller
{
    public function index(CandidateIndexRequest $request, CandidateQuery $query)
    {
        Gate::authorize('viewAny', Candidate::class);

        return CandidateResource::collection($query->build($request->user(), $request->validated())->paginate($request->integer('perPage', 20)));
    }

    public function store(CandidateRequest $request, CandidateService $service): CandidateResource
    {
        return new CandidateResource($service->save($request));
    }

    public function show(Candidate $candidate): CandidateResource
    {
        Gate::authorize('view', $candidate);

        return new CandidateResource($candidate->load(['hiringManagers', 'recruiters', 'currentAssessment.evaluator']));
    }

    public function update(CandidateRequest $request, Candidate $candidate, CandidateService $service): CandidateResource
    {
        return new CandidateResource($service->save($request, $candidate));
    }

    public function destroy(Candidate $candidate, CandidateService $service)
    {
        Gate::authorize('delete', $candidate);
        $service->delete($candidate);

        return response()->noContent();
    }
}
