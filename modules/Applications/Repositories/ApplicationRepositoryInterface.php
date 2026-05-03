<?php

namespace Modules\Applications\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Applications\Models\JobApplication;
use Modules\Jobs\Models\JobPost;

interface ApplicationRepositoryInterface
{
    public function createForCandidate(User $candidate, JobPost $job, array $attributes): JobApplication;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForCandidate(User $candidate, array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForJob(JobPost $job, array $filters): LengthAwarePaginator;

    public function updateStatus(JobApplication $application, string $status): JobApplication;
}
