<?php

namespace Modules\AI\Services;

use App\Models\User;
use Modules\AI\Models\AIMatch;
use Modules\AI\Repositories\AIMatchRepositoryInterface;
use Modules\Jobs\Models\JobPost;

class AIMatchService
{
    public function __construct(
        private readonly AIService $ai,
        private readonly AIMatchRepositoryInterface $matches,
    ) {
    }

    public function matchJob(User $user, JobPost $job): AIMatch
    {
        $user->loadMissing('profile');

        $userSkills = $this->cleanSkills($user->profile?->skills ?? []);
        $jobSkills = $this->cleanSkills($job->skills_required ?? []);

        $existing = $this->matches->findReusable($user, $job, $userSkills, $jobSkills);

        if ($existing !== null) {
            return $existing;
        }

        if ($userSkills === []) {
            return $this->matches->saveResult($user, $job, $userSkills, $jobSkills, [
                'match_score' => 0,
                'missing_skills' => $jobSkills,
                'summary' => 'Add skills to your profile to generate a stronger match.',
                'source' => 'fallback',
            ]);
        }

        $result = $this->ai->matchSkills($userSkills, $jobSkills);

        return $this->matches->saveResult($user, $job, $userSkills, $jobSkills, $result);
    }

    /**
     * @param  array<int, mixed>  $skills
     * @return array<int, string>
     */
    private function cleanSkills(array $skills): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $skill): ?string => is_string($skill) && trim($skill) !== '' ? trim($skill) : null,
            $skills,
        )));
    }

}
