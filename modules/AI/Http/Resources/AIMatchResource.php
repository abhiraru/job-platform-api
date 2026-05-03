<?php

namespace Modules\AI\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AIMatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'ai-matches',
            'id' => (string) $this->id,
            'attributes' => [
                'match_score' => $this->match_score,
                'missing_skills' => $this->missing_skills ?? [],
                'summary' => $this->summary,
                'source' => $this->source,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $this->user_id,
                    ],
                ],
                'job' => [
                    'data' => [
                        'type' => 'jobs',
                        'id' => (string) $this->job_id,
                    ],
                ],
            ],
        ];
    }
}
