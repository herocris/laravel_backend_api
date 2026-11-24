<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Authorization\AdministrativeTransaction;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SerializeAdministrativeMutations
{
    public function __construct(private AdministrativeTransaction $transaction) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $this->transaction->run(function () use ($request, $next): Response {
                $actor = User::query()->find($request->user()->getAuthIdentifier());
                if ($actor === null) {
                    throw new AuthorizationException('El usuario ya no está activo.');
                }

                Auth::guard('api')->setUser($actor);
                $response = $next($request);

                // El pipeline puede convertir excepciones en respuestas antes de retornar.
                if ($response->getStatusCode() >= 400) {
                    throw new HttpResponseException($response);
                }

                return $response;
            });
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        }
    }
}
