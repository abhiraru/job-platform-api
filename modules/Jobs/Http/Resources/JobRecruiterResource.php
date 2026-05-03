<?php

namespace Modules\Jobs\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobRecruiterResource extends JsonResource
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

        $resource['attributes'] = [
            'name' => $this->name,
            'role' => $this->role?->value ?? $this->role,
        ];

        return $resource;
    }
}
