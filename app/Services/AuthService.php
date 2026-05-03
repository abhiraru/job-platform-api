<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /**
     * @param  array{name: string, email: string, password: string, role?: string}  $attributes
     * @return array{user: User, token: string}
     */
    public function register(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $user = $this->users->create($attributes);
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->fresh(),
                'token' => $token,
            ];
        });
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}|null
     */
    public function login(array $credentials): ?array
    {
        $user = $this->users->findByEmail($credentials['email']);

        if ($user === null || ! Hash::check($credentials['password'], $user->getAuthPassword())) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
