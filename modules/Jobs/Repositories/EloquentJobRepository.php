<?php

namespace Modules\Jobs\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Jobs\Models\JobPost;

class EloquentJobRepository implements JobRepositoryInterface
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = JobPost::query()->with('recruiter');

        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['location'] ?? null, fn (Builder $query, string $location) => $query->where('location', 'like', "%{$location}%"))
            ->when($filters['job_type'] ?? null, fn (Builder $query, string $jobType) => $query->where('job_type', $jobType))
            ->when($filters['recruiter_id'] ?? null, fn (Builder $query, int $recruiterId) => $query->where('recruiter_id', $recruiterId))
            ->when($filters['skill'] ?? null, fn (Builder $query, string $skill) => $query->whereJsonContains('skills_required', $skill));

        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowedSorts = ['created_at', 'title', 'location', 'job_type', 'salary_range'];

        if (! in_array($column, $allowedSorts, true)) {
            $column = 'created_at';
            $direction = 'desc';
        }

        return $query
            ->orderBy($column, $direction)
            ->paginate(
                $filters['per_page'] ?? 15,
                ['*'],
                'page[number]',
                $filters['page'] ?? 1,
            )
            ->withQueryString();
    }

    public function createForRecruiter(User $recruiter, array $attributes): JobPost
    {
        $job = $recruiter->jobPosts()->create($attributes);

        return $job->loadMissing('recruiter');
    }

    public function find(int $id): ?JobPost
    {
        return JobPost::query()->with('recruiter')->find($id);
    }

    public function update(JobPost $job, array $attributes): JobPost
    {
        $job->fill($attributes);
        $job->save();

        return $job->loadMissing('recruiter');
    }

    public function delete(JobPost $job): void
    {
        $job->delete();
    }
}
