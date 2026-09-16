<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AssessmentResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = ['id' => $this->id, 'candidateId' => $this->candidate_id, 'evaluatorId' => $this->evaluator_id, 'evaluator' => new UserResource($this->whenLoaded('evaluator')), 'status' => $this->status, 'resultAverage' => $this->result_average === null ? null : round($this->result_average, 2), 'resultLevel' => $this->result_level, 'potentialAverage' => $this->potential_average === null ? null : round($this->potential_average, 2), 'potentialLevel' => $this->potential_level, 'nineBoxCell' => $this->nine_box_cell, 'calibrationSignal' => $this->calibration_signal, 'mainRisk' => $this->main_risk, 'finalComment' => $this->final_comment, 'createdAt' => $this->created_at?->toISOString(), 'updatedAt' => $this->updated_at?->toISOString(), 'completedAt' => $this->completed_at?->toISOString()];
        foreach (array_keys(config('assessment.criteria')) as $key) {
            foreach (['Score', 'Evidence'] as $suffix) {
                $data[$key.$suffix] = $this->{Str::snake($key.$suffix)};
            }
        }

        return $data;
    }
}
