<?php

use App\Http\Middleware\ApiCookieSession;
use App\Http\Middleware\SerializeAdministrativeMutations;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Session\Middleware\AuthenticatesSessions;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [ApiCookieSession::class]);
        $middleware->encryptCookies(except: ['token']);

        $middleware->priority([
            HandlePrecognitiveRequests::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            AuthenticatesRequests::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
            AuthenticatesSessions::class,
            SerializeAdministrativeMutations::class,
            Authorize::class,
            SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (ValidationException $e, Request $request): JsonResponse {
            return response()->json([
                'error' => $e->errors(),
                'code' => 422,
            ], 422);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request): JsonResponse {
            return response()->json(['error' => 'Metodo no permitido', 'code' => 405], 405, $e->getHeaders());
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request): JsonResponse {
            $ex = $e->getPrevious();
            if ($ex instanceof ModelNotFoundException) {
                $modelo = strtolower(class_basename($ex->getModel()));

                return response()->json(['error' => "No existe el recurso con ese id para el modelo {$modelo}", 'code' => 404], 404, $e->getHeaders());
            }

            return response()->json(['error' => 'Url no encontrada', 'code' => 404], 404, $e->getHeaders());
        });

        $exceptions->render(function (AuthenticationException $e, Request $request): JsonResponse {
            return response()->json(['error' => 'No autenticado', 'code' => 401], 401);
        });

        $exceptions->render(function (TokenExpiredException|TokenInvalidException $e, Request $request): JsonResponse {
            return response()->json(['error' => 'No autenticado', 'code' => 401], 401);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request): JsonResponse {
            return response()->json(['error' => 'Se ha revasado el numero de solicitudes', 'code' => 429], 429, $e->getHeaders());
        });

        $exceptions->render(function (RoleDoesNotExist $e, Request $request): JsonResponse {
            return response()->json(['error' => 'No existe el o los roles que esta intentando asignar', 'code' => 422], 422);
        });

        $exceptions->render(function (PermissionDoesNotExist $e, Request $request): JsonResponse {
            return response()->json(['error' => 'No existe el o los permisos que esta intentando asignar', 'code' => 422], 422);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request): JsonResponse {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: (Response::$statusTexts[$status] ?? 'Error HTTP');

            if ($status >= 500 && ! config('app.debug')) {
                $message = 'Error interno del servidor';
            }

            return response()->json(['error' => $message, 'code' => $status], $status, $e->getHeaders());
        });

        $exceptions->render(function (Throwable $e, Request $request): JsonResponse {
            if (config('app.debug')) {
                return response()->json([
                    'message' => 'Excepción no controlada',
                    'error' => $e->getMessage(),
                ], 500);
            } else {
                return response()->json(['error' => 'Error interno del servidor', 'code' => 500], 500);
            }
        });
    })->create();
