<?php

namespace Modules\AI\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AI\Http\Resources\AIMatchResource;
use Modules\AI\Services\AIMatchService;
use Modules\Jobs\Models\JobPost;

class AIController extends Controller
{
    public function __construct(
        private readonly AIMatchService $matchService,
    ) {
    }

    public function matchJob(Request $request, JobPost $job): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return AIMatchResource::make($this->matchService->matchJob($user, $job))
            ->additional([
                'jsonapi' => [
                    'version' => '1.1',
                ],
            ])
            ->response()
            ->setStatusCode(200)
            ->header('Content-Type', 'application/vnd.api+json');
    }
}
