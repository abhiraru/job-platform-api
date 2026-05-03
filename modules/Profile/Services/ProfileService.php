<?php

namespace Modules\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Profile\Models\Profile;
use Modules\Profile\Repositories\ProfileRepositoryInterface;

class ProfileService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profiles,
    ) {
    }

    public function getCurrentProfile(User $user): Profile
    {
        return $this->profiles->firstOrCreateForUser($user);
    }

    public function getPublicProfile(User $user): Profile
    {
        return $this->profiles->firstOrCreateForUser($user);
    }

    public function upsertProfile(User $user, array $attributes, ?UploadedFile $resume = null): Profile
    {
        if ($resume !== null) {
            $path = $resume->store('resumes', 'public');

            if ($path !== false) {
                $attributes['resume_url'] = Storage::disk('public')->url($path);
            }
        }

        return $this->profiles->updateOrCreateForUser($user, $attributes);
    }
}
