<?php

namespace Modules\AI\Repositories;

use App\Models\User;
use Modules\AI\Models\AIMatch;
use Modules\Jobs\Models\JobPost;

class EloquentAIMatchRepository implements AIMatchRepositoryInterface
{
    public function findReusable(User $user, JobPost $job, array $userSkills, array $jobSkills): ?AIMatch
    {
        $match = AIMatch::query()
            ->where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->first();

        if ($match === null) {
            return null;
        }

        return $match->user_skills === array_values($userSkills)
            && $match->job_skills === array_values($jobSkills)
                ? $match->loadMissing(['user.profile', 'job.recruiter'])
                : null;
    }

    public function saveResult(User $user, JobPost $job, array $userSkills, array $jobSkills, array $result): AIMatch
    {
        $match = AIMatch::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'job_id' => $job->id,
            ],
            [
                'match_score' => $result['match_score'],
                'missing_skills' => $result['missing_skills'],
                'summary' => $result['summary'],
                'source' => $result['source'],
                'user_skills' => array_values($userSkills),
                'job_skills' => array_values($jobSkills),
            ],
        );

        return $match->loadMissing(['user.profile', 'job.recruiter']);
    }
}
