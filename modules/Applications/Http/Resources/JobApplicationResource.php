<?php

namespace Modules\Applications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['candidate.profile', 'job.recruiter']);

        return [
            'type' => 'applications',
            'id' => (string) $this->id,
            'attributes' => [
                'status' => $this->status?->value ?? $this->status,
                'status_label' => $this->status?->label(),
                'resume_url' => $this->resume_url,
                'cover_letter' => $this->cover_letter,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'candidate' => [
                    'data' => ApplicationCandidateResource::make($this->candidate)->identifierOnly(),
                ],
                'job' => [
                    'data' => ApplicationJobResource::make($this->job)->identifierOnly(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $this->resource->loadMissing(['candidate.profile', 'job.recruiter']);

        return [
            'included' => [
                ApplicationCandidateResource::make($this->candidate)->resolve($request),
                ApplicationJobResource::make($this->job)->resolve($request),
            ],
        ];
    }
}
