<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Jobs\Enums\JobType;
use Modules\Jobs\Models\JobPost;
use Tests\TestCase;

class JobsApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function jsonApiHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ];
    }

    public function test_an_employer_can_create_a_job(): void
    {
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);

        Sanctum::actingAs($recruiter);

        $response = $this->postJson('/api/jobs', [
            'data' => [
                'type' => 'jobs',
                'attributes' => [
                    'title' => 'Senior Laravel Engineer',
                    'description' => 'Build APIs for a hiring platform.',
                    'skills_required' => ['Laravel', 'MySQL', 'Redis'],
                    'location' => 'Chennai, India',
                    'job_type' => JobType::FullTime->value,
                    'salary_range' => '12L-18L',
                ],
            ],
        ], $this->jsonApiHeaders());

        $response->assertCreated()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '1.1')
            ->assertJsonPath('data.type', 'jobs')
            ->assertJsonPath('data.attributes.title', 'Senior Laravel Engineer')
            ->assertJsonPath('data.attributes.skills_required.0', 'Laravel')
            ->assertJsonPath('data.attributes.job_type', JobType::FullTime->value)
            ->assertJsonPath('data.attributes.job_type_label', JobType::FullTime->label())
            ->assertJsonPath('data.relationships.recruiter.data.id', (string) $recruiter->id);

        $this->assertDatabaseHas('job_posts', [
            'recruiter_id' => $recruiter->id,
            'title' => 'Senior Laravel Engineer',
            'job_type' => JobType::FullTime->value,
        ]);
    }

    public function test_a_candidate_cannot_create_a_job(): void
    {
        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson('/api/jobs', [
            'data' => [
                'type' => 'jobs',
                'attributes' => [
                    'title' => 'Senior Laravel Engineer',
                    'description' => 'Build APIs.',
                ],
            ],
        ], $this->jsonApiHeaders());

        $response->assertForbidden()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('errors.0.status', '403');
    }

    public function test_jobs_can_be_listed_with_pagination_and_filters(): void
    {
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);

        JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Laravel API Engineer',
            'description' => 'Build Laravel APIs.',
            'skills_required' => ['Laravel', 'PHP'],
            'location' => 'Chennai, India',
            'job_type' => JobType::FullTime->value,
            'salary_range' => '12L-18L',
        ]);

        JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Frontend Engineer',
            'description' => 'Build UI.',
            'skills_required' => ['React'],
            'location' => 'Bangalore, India',
            'job_type' => JobType::PartTime->value,
            'salary_range' => '8L-12L',
        ]);

        $response = $this->getJson('/api/jobs?filter[job_type]=full-time&filter[skill]=Laravel&page[size]=1', $this->jsonApiHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '1.1')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.title', 'Laravel API Engineer')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_jobs_pagination_uses_json_api_page_number(): void
    {
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);

        JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Only Job',
            'description' => 'Only available job.',
        ]);

        $response = $this->getJson('/api/jobs?page[size]=30&page[number]=5', $this->jsonApiHeaders());

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.current_page', 5)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_jobs_can_be_sorted(): void
    {
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);

        JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Backend Engineer',
            'description' => 'Build APIs.',
            'salary_range' => '12L-18L',
        ]);

        JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Android Engineer',
            'description' => 'Build mobile apps.',
            'salary_range' => '10L-14L',
        ]);

        $ascending = $this->getJson('/api/jobs?sort=title', $this->jsonApiHeaders());

        $ascending->assertOk()
            ->assertJsonPath('data.0.attributes.title', 'Android Engineer')
            ->assertJsonPath('data.1.attributes.title', 'Backend Engineer');

        $descending = $this->getJson('/api/jobs?sort=-title', $this->jsonApiHeaders());

        $descending->assertOk()
            ->assertJsonPath('data.0.attributes.title', 'Backend Engineer')
            ->assertJsonPath('data.1.attributes.title', 'Android Engineer');
    }

    public function test_a_single_job_can_be_viewed(): void
    {
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);

        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Platform Engineer',
            'description' => 'Own platform systems.',
            'skills_required' => ['Laravel'],
            'job_type' => JobType::Contract->value,
        ]);

        $response = $this->getJson("/api/jobs/{$job->id}", $this->jsonApiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.type', 'jobs')
            ->assertJsonPath('data.id', (string) $job->id)
            ->assertJsonPath('data.attributes.title', 'Platform Engineer')
            ->assertJsonPath('included.0.id', (string) $recruiter->id);
    }

    public function test_an_employer_can_delete_their_own_job(): void
    {
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);

        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Platform Engineer',
            'description' => 'Own platform systems.',
        ]);

        Sanctum::actingAs($recruiter);

        $this->deleteJson("/api/jobs/{$job->id}", [], $this->jsonApiHeaders())
            ->assertNoContent();

        $this->assertDatabaseMissing('job_posts', [
            'id' => $job->id,
        ]);
    }

    public function test_an_employer_cannot_delete_another_recruiters_job(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $otherRecruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);

        $job = JobPost::query()->create([
            'recruiter_id' => $owner->id,
            'title' => 'Platform Engineer',
            'description' => 'Own platform systems.',
        ]);

        Sanctum::actingAs($otherRecruiter);

        $this->deleteJson("/api/jobs/{$job->id}", [], $this->jsonApiHeaders())
            ->assertForbidden()
            ->assertJsonPath('errors.0.status', '403');

        $this->assertDatabaseHas('job_posts', [
            'id' => $job->id,
        ]);
    }
}
