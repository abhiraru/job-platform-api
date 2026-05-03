<?php

namespace Modules\AI\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
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

        if ($userSkills === []) {
            throw new HttpResponseException($this->error('User profile skills are incomplete.', 400));
        }

        $existing = $this->matches->findReusable($user, $job, $userSkills, $jobSkills);

        if ($existing !== null) {
            return $existing;
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

    private function error(string $detail, int $status)
    {
        return response()
            ->json([
                'jsonapi' => [
                    'version' => '1.1',
                ],
                'errors' => [
                    [
                        'status' => (string) $status,
                        'title' => $status === 400 ? 'Bad Request' : 'Error',
                        'detail' => $detail,
                    ],
                ],
            ], $status)
            ->header('Content-Type', 'application/vnd.api+json');
    }
}
