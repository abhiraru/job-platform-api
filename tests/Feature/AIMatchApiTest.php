<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Modules\AI\Models\AIMatch;
use Modules\Jobs\Enums\JobType;
use Modules\Jobs\Models\JobPost;
use Tests\TestCase;

class AIMatchApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function jsonApiHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.api+json',
        ];
    }

    public function test_candidate_can_match_their_profile_to_a_job_with_openai(): void
    {
        Config::set('services.openai.key', 'test-key');
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'match_score' => 78,
                                'missing_skills' => ['Docker', 'AWS'],
                                'summary' => 'Strong backend developer, lacks cloud exposure',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $candidate->profile()->create([
            'skills' => ['Laravel', 'PHP', 'MySQL'],
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Backend Engineer',
            'description' => 'Build APIs.',
            'skills_required' => ['Laravel', 'Docker', 'AWS'],
            'job_type' => JobType::FullTime->value,
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->getJson("/api/ai/match/{$job->id}", $this->jsonApiHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('data.type', 'ai-matches')
            ->assertJsonPath('data.attributes.match_score', 78)
            ->assertJsonPath('data.attributes.missing_skills.0', 'Docker')
            ->assertJsonPath('data.attributes.missing_skills.1', 'AWS')
            ->assertJsonPath('data.attributes.summary', 'Strong backend developer, lacks cloud exposure')
            ->assertJsonPath('data.attributes.source', 'openai');

        $this->assertDatabaseHas('ai_matches', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'match_score' => 78,
            'source' => 'openai',
        ]);
    }

    public function test_match_uses_fallback_when_openai_fails(): void
    {
        Config::set('services.openai.key', 'test-key');
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Nope']], 500),
        ]);

        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $candidate->profile()->create([
            'skills' => ['Laravel', 'PHP'],
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Backend Engineer',
            'description' => 'Build APIs.',
            'skills_required' => ['Laravel', 'Docker'],
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->getJson("/api/ai/match/{$job->id}", $this->jsonApiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.attributes.match_score', 50)
            ->assertJsonPath('data.attributes.missing_skills.0', 'Docker')
            ->assertJsonPath('data.attributes.summary', 'Basic skill matching')
            ->assertJsonPath('data.attributes.source', 'fallback');
    }

    public function test_match_returns_cached_result_for_same_skill_snapshots(): void
    {
        Config::set('services.openai.key', 'test-key');

        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $candidate->profile()->create([
            'skills' => ['Laravel'],
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Backend Engineer',
            'description' => 'Build APIs.',
            'skills_required' => ['Laravel'],
        ]);

        AIMatch::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'match_score' => 100,
            'missing_skills' => [],
            'summary' => 'Cached match',
            'source' => 'openai',
            'user_skills' => ['Laravel'],
            'job_skills' => ['Laravel'],
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([], 500),
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->getJson("/api/ai/match/{$job->id}", $this->jsonApiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.attributes.match_score', 100)
            ->assertJsonPath('data.attributes.summary', 'Cached match');

        Http::assertNothingSent();
    }

    public function test_match_returns_fallback_when_profile_skills_are_missing(): void
    {
        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Backend Engineer',
            'description' => 'Build APIs.',
            'skills_required' => ['Laravel'],
        ]);

        Sanctum::actingAs($candidate);

        $this->getJson("/api/ai/match/{$job->id}", $this->jsonApiHeaders())
            ->assertOk()
            ->assertJsonPath('data.attributes.match_score', 0)
            ->assertJsonPath('data.attributes.missing_skills.0', 'Laravel')
            ->assertJsonPath('data.attributes.summary', 'Add skills to your profile to generate a stronger match.')
            ->assertJsonPath('data.attributes.source', 'fallback');
    }
}
