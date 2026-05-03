<?php

namespace Modules\Profile\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('user');

        return [
            'type' => 'profiles',
            'id' => (string) $this->id,
            'attributes' => [
                'headline' => $this->headline,
                'current_title' => $this->current_title,
                'desired_job_title' => $this->desired_job_title,
                'bio' => $this->bio,
                'location' => $this->location,
                'years_of_experience' => $this->years_of_experience,
                'skills' => $this->skills ?? [],
                'linkedin_url' => $this->linkedin_url,
                'portfolio_url' => $this->portfolio_url,
                'resume_url' => $this->resume_url,
                'availability_status' => $this->availability_status?->value ?? $this->availability_status,
                'availability_status_label' => $this->availability_status?->label(),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'user' => [
                    'data' => ProfileUserResource::make($this->user)->identifierOnly(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $this->resource->loadMissing('user');

        return [
            'included' => [
                ProfileUserResource::make($this->user)->resolve($request),
            ],
        ];
    }
}
