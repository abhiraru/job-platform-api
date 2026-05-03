<?php

namespace Modules\Jobs\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('recruiter');

        return [
            'type' => 'jobs',
            'id' => (string) $this->id,
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
                'skills_required' => $this->skills_required ?? [],
                'location' => $this->location,
                'job_type' => $this->job_type?->value ?? $this->job_type,
                'job_type_label' => $this->job_type?->label(),
                'salary_range' => $this->salary_range,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'recruiter' => [
                    'data' => JobRecruiterResource::make($this->recruiter)->identifierOnly(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $this->resource->loadMissing('recruiter');

        return [
            'included' => [
                JobRecruiterResource::make($this->recruiter)->resolve($request),
            ],
        ];
    }
}
