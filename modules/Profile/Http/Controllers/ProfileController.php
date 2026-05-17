<?php

namespace Modules\Profile\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Profile\Http\Requests\UpsertProfileRequest;
use Modules\Profile\Http\Resources\OwnProfileResource;
use Modules\Profile\Http\Resources\PublicProfileResource;
use Modules\Profile\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {
    }

    public function showCurrent(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            OwnProfileResource::make($this->profileService->getCurrentProfile($user)),
        );
    }

    public function upsert(UpsertProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            OwnProfileResource::make(
                $this->profileService->upsertProfile(
                    $user,
                    $request->profileAttributes(),
                    $request->resumeFile(),
                    $request->profilePictureFile(),
                ),
            ),
        );
    }

    public function showPublic(User $user): JsonResponse
    {
        return $this->success(
            PublicProfileResource::make($this->profileService->getPublicProfile($user)),
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
}
