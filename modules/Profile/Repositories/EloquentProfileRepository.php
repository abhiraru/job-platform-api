<?php

namespace Modules\Profile\Repositories;

use App\Models\User;
use Modules\Profile\Models\Profile;

class EloquentProfileRepository implements ProfileRepositoryInterface
{
    public function firstOrCreateForUser(User $user): Profile
    {
        $profile = $user->profile()->firstOrCreate([]);

        return $profile->loadMissing('user');
    }

    public function updateOrCreateForUser(User $user, array $attributes): Profile
    {
        $profile = $user->profile()->firstOrCreate([]);
        $profile->fill($attributes);
        $profile->save();

        return $profile->loadMissing('user');
    }
}
