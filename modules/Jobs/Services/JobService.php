<?php

namespace Modules\Jobs\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Jobs\Models\JobPost;
use Modules\Jobs\Repositories\JobRepositoryInterface;

class JobService
{
    public function __construct(
        private readonly JobRepositoryInterface $jobs,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getJobs(array $filters): LengthAwarePaginator
    {
        return $this->jobs->paginate($filters);
    }

    public function createJob(User $recruiter, array $attributes): JobPost
    {
        return $this->jobs->createForRecruiter($recruiter, $attributes);
    }

    public function getJob(int $id): ?JobPost
    {
        return $this->jobs->find($id);
    }

    public function deleteJob(JobPost $job): void
    {
        $this->jobs->delete($job);
    }
}
