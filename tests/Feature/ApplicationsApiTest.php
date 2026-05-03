<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Applications\Enums\ApplicationStatus;
use Modules\Applications\Models\JobApplication;
use Modules\Jobs\Enums\JobType;
use Modules\Jobs\Models\JobPost;
use Tests\TestCase;

class ApplicationsApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function jsonApiHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/json',
        ];
    }

    public function test_a_candidate_can_apply_to_a_job(): void
    {
        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $candidate->profile()->create([
            'resume_url' => 'http://localhost/storage/resumes/candidate.pdf',
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Laravel Engineer',
            'description' => 'Build APIs.',
            'job_type' => JobType::FullTime->value,
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson('/api/apply', [
            'job_id' => $job->id,
            'cover_letter' => 'I am interested in this role.',
        ], $this->jsonApiHeaders());

        $response->assertCreated()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '1.1')
            ->assertJsonPath('data.type', 'applications')
            ->assertJsonPath('data.attributes.status', ApplicationStatus::Applied->value)
            ->assertJsonPath('data.attributes.status_label', ApplicationStatus::Applied->label())
            ->assertJsonPath('data.attributes.resume_url', 'http://localhost/storage/resumes/candidate.pdf')
            ->assertJsonPath('data.relationships.candidate.data.id', (string) $candidate->id)
            ->assertJsonPath('data.relationships.job.data.id', (string) $job->id);

        $this->assertDatabaseHas('job_applications', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Applied->value,
        ]);
    }

    public function test_a_candidate_cannot_apply_to_the_same_job_twice(): void
    {
        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Laravel Engineer',
            'description' => 'Build APIs.',
        ]);

        JobApplication::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Applied->value,
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson('/api/apply', [
            'job_id' => $job->id,
        ], $this->jsonApiHeaders());

        $response->assertUnprocessable()
            ->assertJsonPath('errors.0.status', '422')
            ->assertJsonPath('errors.0.source.pointer', '/job_id');
    }

    public function test_candidate_can_view_their_applications_with_filters_pagination_and_sorting(): void
    {
        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $laravelJob = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Laravel Engineer',
            'description' => 'Build APIs.',
            'job_type' => JobType::FullTime->value,
        ]);
        $reactJob = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'React Engineer',
            'description' => 'Build UI.',
            'job_type' => JobType::PartTime->value,
        ]);

        JobApplication::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $laravelJob->id,
            'status' => ApplicationStatus::Shortlisted->value,
            'cover_letter' => 'Laravel is my main stack.',
        ]);
        JobApplication::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $reactJob->id,
            'status' => ApplicationStatus::Applied->value,
            'cover_letter' => 'React role.',
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->getJson('/api/my-applications?filter[status]=shortlisted&filter[search]=Laravel&page[size]=1&page[number]=1&sort=status', $this->jsonApiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.status', ApplicationStatus::Shortlisted->value)
            ->assertJsonPath('data.0.relationships.job.data.id', (string) $laravelJob->id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_recruiter_can_view_applications_for_their_job(): void
    {
        $candidate = User::factory()->create([
            'name' => 'Jane Candidate',
            'role' => UserRole::Candidate->value,
        ]);
        $candidate->profile()->create([
            'headline' => 'Laravel Developer',
            'skills' => ['Laravel', 'PHP'],
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Laravel Engineer',
            'description' => 'Build APIs.',
        ]);

        $application = JobApplication::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Applied->value,
        ]);

        Sanctum::actingAs($recruiter);

        $response = $this->getJson("/api/jobs/{$job->id}/applications?filter[candidate]=Jane&page[size]=10&sort=-created_at", $this->jsonApiHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('data.0.id', (string) $application->id)
            ->assertJsonPath('data.0.relationships.candidate.data.id', (string) $candidate->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_recruiter_can_update_application_status_for_their_job(): void
    {
        $candidate = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);
        $recruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $recruiter->id,
            'title' => 'Laravel Engineer',
            'description' => 'Build APIs.',
        ]);
        $application = JobApplication::query()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Applied->value,
        ]);

        Sanctum::actingAs($recruiter);

        $response = $this->postJson("/api/applications/{$application->id}/status", [
            'status' => ApplicationStatus::Shortlisted->value,
        ], $this->jsonApiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.attributes.status', ApplicationStatus::Shortlisted->value)
            ->assertJsonPath('data.attributes.status_label', ApplicationStatus::Shortlisted->label());

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Shortlisted->value,
        ]);
    }

    public function test_recruiter_cannot_view_applications_for_another_recruiters_job(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $otherRecruiter = User::factory()->create([
            'role' => UserRole::Employer->value,
        ]);
        $job = JobPost::query()->create([
            'recruiter_id' => $owner->id,
            'title' => 'Laravel Engineer',
            'description' => 'Build APIs.',
        ]);

        Sanctum::actingAs($otherRecruiter);

        $this->getJson("/api/jobs/{$job->id}/applications", $this->jsonApiHeaders())
            ->assertForbidden()
            ->assertJsonPath('errors.0.status', '403');
    }
}
