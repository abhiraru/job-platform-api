<?php

namespace Modules\Profile\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileUserResource extends JsonResource
{
    private bool $includeEmail = false;

    private bool $identifierOnly = false;

    public function withEmail(): self
    {
        $this->includeEmail = true;

        return $this;
    }

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
            'email' => $this->when($this->includeEmail, $this->email),
            'role' => $this->role?->value ?? $this->role,
        ];

        return $resource;
    }
}
