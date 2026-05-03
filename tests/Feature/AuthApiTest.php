<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_with_the_default_candidate_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Candidate',
            'email' => 'JANE@example.com',
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'jane@example.com')
            ->assertJsonPath('user.role', UserRole::Candidate->value)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role', 'created_at', 'updated_at'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => UserRole::Candidate->value,
        ]);
    }

    public function test_a_user_can_login_and_receive_a_token(): void
    {
        $user = User::query()->create([
            'name' => 'Employer User',
            'email' => 'employer@example.com',
            'password' => 'secret123',
            'role' => UserRole::Employer->value,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'employer@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', UserRole::Employer->value)
            ->assertJsonStructure(['user', 'token']);
    }
}
