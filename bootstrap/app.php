<?php


use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\ApiResponser;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'error' => $e->errors(),
                'code' => 422
            ], 422);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            return response()->json(['error' => 'Metodo no permitido', 'code' => 405], 405);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $ex = $e->getPrevious();
            if ($ex instanceof ModelNotFoundException) {
                $modelo = strtolower(class_basename($ex->getModel()));
                return response()->json(['error' => "No existe el recurso con ese id para el modelo {$modelo}", 'code' => 404], 404);
            }
            return response()->json(['error' => 'Url no encontrada', 'code' => 404], 404);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['error' => 'No autenticado', 'code' => 401], 401);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return response()->json(['error' => 'Se ha revasado el numero de solicitudes', 'code' => 429], 429);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
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
