<?php

namespace Modules\Jobs\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Jobs\Http\Requests\IndexJobsRequest;
use Modules\Jobs\Http\Requests\StoreJobRequest;
use Modules\Jobs\Http\Resources\JobResource;
use Modules\Jobs\Models\JobPost;
use Modules\Jobs\Services\JobService;

class JobController extends Controller
{
    public function __construct(
        private readonly JobService $jobService,
    ) {
    }

    public function index(IndexJobsRequest $request): JsonResponse
    {
        $jobs = $this->jobService->getJobs($request->filters());

        return JobResource::collection($jobs)
            ->additional([
                'jsonapi' => [
                    'version' => '1.1',
                ],
            ])
            ->response()
            ->header('Content-Type', 'application/vnd.api+json');
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        /** @var User $recruiter */
        $recruiter = $request->user();

        return $this->success(
            JobResource::make($this->jobService->createJob($recruiter, $request->jobAttributes())),
            201,
        );
    }

    public function show(JobPost $job): JsonResponse
    {
        return $this->success(JobResource::make($job->loadMissing('recruiter')));
    }

    public function destroy(JobPost $job): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->role !== UserRole::Employer || $job->recruiter_id !== $user->id) {
            return response()
                ->json([
                    'jsonapi' => [
                        'version' => '1.1',
                    ],
                    'errors' => [
                        [
                            'status' => '403',
                            'title' => 'Forbidden',
                            'detail' => 'You are not allowed to delete this job.',
                        ],
                    ],
                ], 403)
                ->header('Content-Type', 'application/vnd.api+json');
        }

        $this->jobService->deleteJob($job);

        return response()
            ->json(null, 204)
            ->header('Content-Type', 'application/vnd.api+json');
    }

    private function success(JsonResource $resource, int $status = 200): JsonResponse
    {
        return $resource
            ->additional([
                'jsonapi' => [
                    'version' => '1.1',
                ],
            ])
            ->response()
            ->setStatusCode($status)
            ->header('Content-Type', 'application/vnd.api+json');
    }
}
