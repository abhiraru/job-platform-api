<?php

namespace Modules\Profile\Repositories;

use App\Models\User;
use Modules\Profile\Models\Profile;

interface ProfileRepositoryInterface
{
    public function firstOrCreateForUser(User $user): Profile;

    public function updateOrCreateForUser(User $user, array $attributes): Profile;
}
