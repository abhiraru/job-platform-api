<?php

namespace Modules\Applications\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Applications\Models\JobApplication;
use Modules\Applications\Repositories\ApplicationRepositoryInterface;
use Modules\Jobs\Models\JobPost;

class ApplicationService
{
    public function __construct(
        private readonly ApplicationRepositoryInterface $applications,
    ) {
    }

    public function apply(User $candidate, JobPost $job, array $attributes): JobApplication
    {
        if (! array_key_exists('resume_url', $attributes) || $attributes['resume_url'] === null) {
            $candidate->loadMissing('profile');
            $attributes['resume_url'] = $candidate->profile?->resume_url;
        }

        return $this->applications->createForCandidate($candidate, $job, $attributes);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getCandidateApplications(User $candidate, array $filters): LengthAwarePaginator
    {
        return $this->applications->paginateForCandidate($candidate, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getJobApplications(JobPost $job, array $filters): LengthAwarePaginator
    {
        return $this->applications->paginateForJob($job, $filters);
    }

    public function updateStatus(JobApplication $application, string $status): JobApplication
    {
        return $this->applications->updateStatus($application, $status);
    }
}
