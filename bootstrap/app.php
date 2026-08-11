<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveOrganization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['organization' => ResolveOrganization::class]);
        $middleware->appendToPriorityList(AuthenticatesRequests::class, ResolveOrganization::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('webhooks/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof ValidationException => response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'VALIDATION_FAILED',
                    'errors' => $e->errors(),
                ], 422),
                $e instanceof AuthenticationException => response()->json([
                    'message' => 'Não autenticado.',
                    'error_code' => 'UNAUTHENTICATED',
                ], 401),
                $e instanceof AuthorizationException => response()->json([
                    'message' => 'Ação não autorizada.',
                    'error_code' => 'FORBIDDEN',
                ], 403),
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => response()->json([
                    'message' => 'Recurso não encontrado.',
                    'error_code' => 'NOT_FOUND',
                ], 404),
                $e instanceof ThrottleRequestsException => response()->json([
                    'message' => 'Muitas requisições. Tente novamente em instantes.',
                    'error_code' => 'RATE_LIMITED',
                ], 429),
                $e instanceof HttpExceptionInterface => response()->json([
                    'message' => $e->getMessage() ?: 'Erro na requisição.',
                    'error_code' => 'HTTP_ERROR',
                ], $e->getStatusCode()),
                default => null,
            };
        });
    })->create();
