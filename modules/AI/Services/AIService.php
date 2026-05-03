<?php

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class AIService
{
    /**
     * @param  array<int, string>  $userSkills
     * @param  array<int, string>  $jobSkills
     * @return array{match_score: int, missing_skills: array<int, string>, summary: string, source: string}
     */
    public function matchSkills(array $userSkills, array $jobSkills): array
    {
        $fallback = $this->fallbackMatch($userSkills, $jobSkills);
        $apiKey = config('services.openai.key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return $fallback;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You compare candidate skills with job requirements. Return only valid JSON.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->prompt($userSkills, $jobSkills),
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'job_match',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'match_score' => [
                                        'type' => 'integer',
                                        'minimum' => 0,
                                        'maximum' => 100,
                                    ],
                                    'missing_skills' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                    'summary' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'required' => ['match_score', 'missing_skills', 'summary'],
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $content = $response->json('choices.0.message.content');
            $decoded = is_string($content) ? json_decode($content, true) : null;
            $result = $this->normalizeResult(is_array($decoded) ? $decoded : null);

            return $result ?? $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * @param  array<int, string>  $userSkills
     * @param  array<int, string>  $jobSkills
     */
    private function prompt(array $userSkills, array $jobSkills): string
    {
        return 'Compare these two skill sets for a job application.'."\n\n"
            .'User Skills: '.implode(', ', $userSkills)."\n"
            .'Job Requirements: '.implode(', ', $jobSkills)."\n\n"
            .'Return match_score from 0-100, missing_skills from job requirements, and a short summary.';
    }

    /**
     * @param  array<int, string>  $userSkills
     * @param  array<int, string>  $jobSkills
     * @return array{match_score: int, missing_skills: array<int, string>, summary: string, source: string}
     */
    private function fallbackMatch(array $userSkills, array $jobSkills): array
    {
        $normalizedUserSkills = array_map(fn (string $skill): string => mb_strtolower(trim($skill)), $userSkills);
        $matched = array_filter($jobSkills, function (string $skill) use ($normalizedUserSkills): bool {
            return in_array(mb_strtolower(trim($skill)), $normalizedUserSkills, true);
        });

        $missing = array_values(array_filter($jobSkills, function (string $skill) use ($normalizedUserSkills): bool {
            return ! in_array(mb_strtolower(trim($skill)), $normalizedUserSkills, true);
        }));

        $score = count($jobSkills) > 0
            ? (count($matched) / count($jobSkills)) * 100
            : 0;

        return [
            'match_score' => (int) round($score),
            'missing_skills' => $missing,
            'summary' => 'Basic skill matching',
            'source' => 'fallback',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $result
     * @return array{match_score: int, missing_skills: array<int, string>, summary: string, source: string}|null
     */
    private function normalizeResult(?array $result): ?array
    {
        if ($result === null || ! isset($result['match_score'], $result['missing_skills'], $result['summary'])) {
            return null;
        }

        if (! is_numeric($result['match_score']) || ! is_array($result['missing_skills']) || ! is_string($result['summary'])) {
            return null;
        }

        return [
            'match_score' => max(0, min(100, (int) round((float) $result['match_score']))),
            'missing_skills' => array_values(array_filter($result['missing_skills'], 'is_string')),
            'summary' => $result['summary'],
            'source' => 'openai',
        ];
    }
}
