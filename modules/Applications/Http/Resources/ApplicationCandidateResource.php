<?php

namespace Modules\Applications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationCandidateResource extends JsonResource
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
            'type' => 'users',
            'id' => (string) $this->id,
        ];

        if ($this->identifierOnly) {
            return $resource;
        }

        $this->resource->loadMissing('profile');

        $resource['attributes'] = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->value ?? $this->role,
            'headline' => $this->profile?->headline,
            'current_title' => $this->profile?->current_title,
            'location' => $this->profile?->location,
            'skills' => $this->profile?->skills ?? [],
        ];

        return $resource;
    }
}
