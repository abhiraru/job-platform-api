<?php

namespace Modules\Applications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationJobResource extends JsonResource
{
    private bool $identifierOnly = false;

    public function identifierOnly(): self
    {
        $this->identifierOnly = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = [
            'type' => 'jobs',
            'id' => (string) $this->id,
        ];

        if ($this->identifierOnly) {
            return $resource;
        }

        $resource['attributes'] = [
            'title' => $this->title,
            'description' => $this->description,
            'skills_required' => $this->skills_required ?? [],
            'location' => $this->location,
            'job_type' => $this->job_type?->value ?? $this->job_type,
            'job_type_label' => $this->job_type?->label(),
            'salary_range' => $this->salary_range,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        return $resource;
    }
}
