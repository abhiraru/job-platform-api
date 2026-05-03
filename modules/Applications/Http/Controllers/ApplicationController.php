<?php

namespace Modules\Applications\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Applications\Http\Requests\ApplyJobRequest;
use Modules\Applications\Http\Requests\IndexApplicationsRequest;
use Modules\Applications\Http\Requests\UpdateApplicationStatusRequest;
use Modules\Applications\Http\Resources\JobApplicationResource;
use Modules\Applications\Models\JobApplication;
use Modules\Applications\Services\ApplicationService;
use Modules\Jobs\Models\JobPost;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationService $applicationService,
    ) {
    }

    public function apply(ApplyJobRequest $request): JsonResponse
    {
        /** @var User $candidate */
        $candidate = $request->user();
        $job = JobPost::query()->findOrFail($request->integer('job_id'));

        return $this->success(
            JobApplicationResource::make(
                $this->applicationService->apply($candidate, $job, $request->applicationAttributes()),
            ),
            201,
        );
    }

    public function myApplications(IndexApplicationsRequest $request): JsonResponse
    {
        /** @var User $candidate */
        $candidate = $request->user();

        return JobApplicationResource::collection(
            $this->applicationService->getCandidateApplications($candidate, $request->filters()),
        )
            ->additional([
                'jsonapi' => [
                    'version' => '1.1',
                ],
            ])
            ->response()
            ->header('Content-Type', 'application/vnd.api+json');
    }

    public function jobApplications(JobPost $job, IndexApplicationsRequest $request): JsonResponse
    {
        /** @var User $recruiter */
        $recruiter = $request->user();

        if ($recruiter->role !== UserRole::Employer || $job->recruiter_id !== $recruiter->id) {
            return $this->forbidden('You are not allowed to view applications for this job.');
        }

        return JobApplicationResource::collection(
            $this->applicationService->getJobApplications($job, $request->filters()),
        )
            ->additional([
                'jsonapi' => [
                    'version' => '1.1',
                ],
            ])
            ->response()
            ->header('Content-Type', 'application/vnd.api+json');
    }

    public function updateStatus(JobApplication $application, UpdateApplicationStatusRequest $request): JsonResponse
    {
        /** @var User $recruiter */
        $recruiter = $request->user();
        $application->loadMissing('job');

        if ($recruiter->role !== UserRole::Employer || $application->job->recruiter_id !== $recruiter->id) {
            return $this->forbidden('You are not allowed to update this application.');
        }

        return $this->success(
            JobApplicationResource::make(
                $this->applicationService->updateStatus($application, $request->validated('status')),
            ),
        );
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

    private function forbidden(string $detail): JsonResponse
    {
        return response()
            ->json([
                'jsonapi' => [
                    'version' => '1.1',
                ],
                'errors' => [
                    [
                        'status' => '403',
                        'title' => 'Forbidden',
                        'detail' => $detail,
                    ],
                ],
            ], 403)
            ->header('Content-Type', 'application/vnd.api+json');
    }
}
