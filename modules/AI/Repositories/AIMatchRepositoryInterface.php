<?php

namespace Modules\AI\Repositories;

use App\Models\User;
use Modules\AI\Models\AIMatch;
use Modules\Jobs\Models\JobPost;

interface AIMatchRepositoryInterface
{
    public function findReusable(User $user, JobPost $job, array $userSkills, array $jobSkills): ?AIMatch;

    public function saveResult(User $user, JobPost $job, array $userSkills, array $jobSkills, array $result): AIMatch;
}
