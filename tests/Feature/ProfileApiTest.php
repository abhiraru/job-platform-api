<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Profile\Enums\ProfileAvailability;
use Tests\TestCase;

class ProfileApiTest extends TestCase
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

    public function test_an_authenticated_user_can_view_their_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/profile/me', $this->jsonApiHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '1.1')
            ->assertJsonPath('data.type', 'profiles')
            ->assertJsonPath('data.relationships.user.data.type', 'users')
            ->assertJsonPath('data.relationships.user.data.id', (string) $user->id)
            ->assertJsonPath('data.attributes.skills', [])
            ->assertJsonPath('included.0.type', 'users')
            ->assertJsonPath('included.0.id', (string) $user->id)
            ->assertJsonPath('included.0.attributes.email', $user->email)
            ->assertJsonPath('included.0.attributes.role', UserRole::Candidate->value);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_an_authenticated_user_can_create_or_update_their_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile/me', [
            'data' => [
                'type' => 'profiles',
                'attributes' => [
                    'headline' => 'Backend Engineer focused on AI hiring flows',
                    'current_title' => 'Laravel Developer',
                    'desired_job_title' => 'Senior Backend Engineer',
                    'bio' => 'Building hiring platforms with Laravel and AI tooling.',
                    'location' => 'Chennai, India',
                    'phone' => '+91-9000000000',
                    'years_of_experience' => 4,
                    'skills' => ['Laravel', 'PHP', 'OpenAI'],
                    'linkedin_url' => 'https://www.linkedin.com/in/jane-candidate',
                    'portfolio_url' => 'https://portfolio.example.com',
                    'availability_status' => ProfileAvailability::OpenToWork->value,
                ],
            ],
        ], $this->jsonApiHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('jsonapi.version', '1.1')
            ->assertJsonPath('data.type', 'profiles')
            ->assertJsonPath('data.attributes.headline', 'Backend Engineer focused on AI hiring flows')
            ->assertJsonPath('data.attributes.current_title', 'Laravel Developer')
            ->assertJsonPath('data.attributes.skills.0', 'Laravel')
            ->assertJsonPath('data.attributes.availability_status', ProfileAvailability::OpenToWork->value)
            ->assertJsonPath('data.attributes.availability_status_label', ProfileAvailability::OpenToWork->label());

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'headline' => 'Backend Engineer focused on AI hiring flows',
            'current_title' => 'Laravel Developer',
            'availability_status' => ProfileAvailability::OpenToWork->value,
        ]);
    }

    public function test_public_profile_view_hides_sensitive_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Recruitable',
            'role' => UserRole::Candidate->value,
        ]);

        $user->profile()->create([
            'headline' => 'API Engineer',
            'bio' => 'Open to platform engineering roles.',
            'phone' => '+91-9000000000',
            'skills' => ['Laravel', 'MySQL'],
            'resume_url' => 'https://storage.example.com/resumes/jane.pdf',
            'availability_status' => ProfileAvailability::OpenToWork->value,
        ]);

        $response = $this->getJson("/api/profiles/{$user->id}", $this->jsonApiHeaders());

        $response->assertOk()
            ->assertJsonPath('jsonapi.version', '1.1')
            ->assertJsonPath('data.type', 'profiles')
            ->assertJsonPath('data.attributes.headline', 'API Engineer')
            ->assertJsonPath('data.attributes.resume_url', 'https://storage.example.com/resumes/jane.pdf')
            ->assertJsonPath('data.attributes.availability_status', ProfileAvailability::OpenToWork->value)
            ->assertJsonPath('data.attributes.availability_status_label', ProfileAvailability::OpenToWork->label())
            ->assertJsonPath('data.relationships.user.data.type', 'users')
            ->assertJsonPath('data.relationships.user.data.id', (string) $user->id)
            ->assertJsonPath('included.0.attributes.name', 'Jane Recruitable')
            ->assertJsonPath('included.0.attributes.role', UserRole::Candidate->value)
            ->assertJsonMissingPath('included.0.attributes.email')
            ->assertJsonMissingPath('data.attributes.phone');
    }

    public function test_profile_skills_are_validated_as_json_arrays(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile/me', [
            'data' => [
                'type' => 'profiles',
                'attributes' => [
                    'skills' => 'Laravel,PHP',
                ],
            ],
        ], $this->jsonApiHeaders());

        $response->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonMissingPath('data')
            ->assertJsonPath('jsonapi.version', '1.1')
            ->assertJsonPath('errors.0.status', '422')
            ->assertJsonPath('errors.0.source.pointer', '/data/attributes/skills');
    }

    public function test_an_authenticated_user_can_upload_a_resume_when_updating_their_profile(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => UserRole::Candidate->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this->post('/api/profile/me', [
            '_method' => 'PATCH',
            'data' => [
                'type' => 'profiles',
                'attributes' => [
                    'headline' => 'Backend Engineer',
                ],
            ],
            'resume' => UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf'),
        ], [
            'Accept' => 'application/vnd.api+json',
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJsonPath('data.attributes.headline', 'Backend Engineer');

        $resumeUrl = $response->json('data.attributes.resume_url');
        $this->assertIsString($resumeUrl);
        $this->assertStringContainsString('/storage/resumes/', $resumeUrl);

        $profile = $user->profile()->firstOrFail();
        $this->assertSame($resumeUrl, $profile->resume_url);

        Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($resumeUrl, PHP_URL_PATH)));
    }
}
