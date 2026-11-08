<?php

use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\TransformInput;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'transformInput' => TransformInput::class,
            'jwt.auth' => AuthenticateJWT::class,
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

        $exceptions->render(function (ThrottleRequestsException $e, Request $request): JsonResponse {
            return response()->json(['error' => 'Se ha revasado el numero de solicitudes', 'code' => 429], 429, $e->getHeaders());
        });

        $exceptions->render(function (RoleDoesNotExist $e, Request $request): JsonResponse {
            return response()->json(['error' => 'No existe el o los roles que esta intentando asignar', 'code' => 422], 422);
        });

        $exceptions->render(function (PermissionDoesNotExist $e, Request $request): JsonResponse {
            return response()->json(['error' => 'No existe el o los permisos que esta intentando asignar', 'code' => 422], 422);
        });

        // $exceptions->render(function (QueryException $e, Request $request) { //exepcion de entrada duplicada en base de datos

        //     $ex = $e->getPrevious();
        //     //dd($ex->errorInfo);
        //     if ($ex->getCode() === '23000') {
        //         return response()->json([
        //             'message' => 'El correo ya está registrado.',
        //             'code' => 409
        //         ], 409);
        //     }
        // });

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
