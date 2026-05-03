<?php

namespace Modules\Jobs\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Jobs\Models\JobPost;

interface JobRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator;

    public function createForRecruiter(User $recruiter, array $attributes): JobPost;

    public function find(int $id): ?JobPost;

    public function delete(JobPost $job): void;
}
