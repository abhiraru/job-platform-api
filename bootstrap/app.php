<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthorizationException $exception) {
            return response()
                ->json([
                    'jsonapi' => [
                        'version' => '1.1',
                    ],
                    'errors' => [
                        [
                            'status' => '403',
                            'title' => 'Forbidden',
                            'detail' => $exception->getMessage() ?: 'This action is unauthorized.',
                        ],
                    ],
                ], 403)
                ->header('Content-Type', 'application/vnd.api+json');
        });

        $exceptions->render(function (ValidationException $exception) {
            $errors = collect($exception->errors())
                ->flatMap(function (array $messages, string $field): array {
                    return collect($messages)
                        ->map(fn (string $message): array => [
                            'status' => '422',
                            'title' => 'Validation Error',
                            'detail' => $message,
                            'source' => [
                                'pointer' => '/'.str_replace('.', '/', $field),
                            ],
                        ])
                        ->all();
                })
                ->values()
                ->all();

            return response()
                ->json([
                    'jsonapi' => [
                        'version' => '1.1',
                    ],
                    'errors' => $errors,
                ], $exception->status)
                ->header('Content-Type', 'application/vnd.api+json');
        });
    })->create();
