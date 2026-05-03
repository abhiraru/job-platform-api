<?php

namespace Modules\Applications\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Applications\Enums\ApplicationStatus;
use Modules\Applications\Models\JobApplication;
use Modules\Jobs\Models\JobPost;

class EloquentApplicationRepository implements ApplicationRepositoryInterface
{
    public function createForCandidate(User $candidate, JobPost $job, array $attributes): JobApplication
    {
        $application = $candidate->jobApplications()->create([
            ...$attributes,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Applied->value,
        ]);

        return $application->loadMissing(['candidate.profile', 'job.recruiter']);
    }

    public function paginateForCandidate(User $candidate, array $filters): LengthAwarePaginator
    {
        $query = JobApplication::query()
            ->where('user_id', $candidate->id)
            ->with(['job.recruiter', 'candidate.profile']);

        $this->applySharedFilters($query, $filters);

        return $this->paginate($query, $filters);
    }

    public function paginateForJob(JobPost $job, array $filters): LengthAwarePaginator
    {
        $query = JobApplication::query()
            ->where('job_id', $job->id)
            ->with(['candidate.profile', 'job.recruiter']);

        $this->applySharedFilters($query, $filters);

        $query->when($filters['candidate'] ?? null, function (Builder $query, string $candidate): void {
            $query->whereHas('candidate', function (Builder $query) use ($candidate): void {
                $query
                    ->where('name', 'like', "%{$candidate}%")
                    ->orWhere('email', 'like', "%{$candidate}%");
            });
        });

        return $this->paginate($query, $filters);
    }

    public function updateStatus(JobApplication $application, string $status): JobApplication
    {
        $application->update([
            'status' => $status,
        ]);

        return $application->loadMissing(['candidate.profile', 'job.recruiter']);
    }

    /**
     * @param  Builder<JobApplication>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySharedFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['job_type'] ?? null, function (Builder $query, string $jobType): void {
                $query->whereHas('job', fn (Builder $query) => $query->where('job_type', $jobType));
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('cover_letter', 'like', "%{$search}%")
                        ->orWhereHas('job', function (Builder $query) use ($search): void {
                            $query
                                ->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                });
            });
    }

    /**
     * @param  Builder<JobApplication>  $query
     * @param  array<string, mixed>  $filters
     */
    private function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowedSorts = ['created_at', 'status', 'updated_at'];

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
}
