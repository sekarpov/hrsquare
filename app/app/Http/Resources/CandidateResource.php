<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray($request): array
    {
        return ['id' => $this->id, 'fullName' => $this->full_name, 'position' => $this->position, 'city' => $this->city, 'company' => $this->company, 'division' => $this->division, 'project' => $this->project, 'status' => $this->status, 'createdBy' => $this->created_by, 'hiringManagers' => UserResource::collection($this->whenLoaded('hiringManagers')), 'recruiters' => UserResource::collection($this->whenLoaded('recruiters')), 'currentAssessment' => new AssessmentResource($this->whenLoaded('currentAssessment')), 'createdAt' => $this->created_at?->toISOString(), 'updatedAt' => $this->updated_at?->toISOString()];
    }
}
