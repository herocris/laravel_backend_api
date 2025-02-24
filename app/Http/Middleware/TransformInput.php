<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class TransformInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $resource): Response
    {
        // Transformar la entrada usando colecciones
        $transformedInput = collect($request->all())
            ->mapWithKeys(fn ($value, $key) => [
                $resource::originalAttribute($key) => $value
            ])
            ->toArray();

        $request->replace($transformedInput);

        // Procesar la solicitud y capturar la respuesta
        $response = $next($request);

        // Validar si existe una excepción de validación
        if ($response->exception instanceof ValidationException) {
            $data = $response->getData();

            $transformedErrors = collect($data->error ?? [])
                ->mapWithKeys(fn ($error, $field) => [
                    $transformedField = $resource::transformedAttribute($field) =>
                        str_replace($field, $transformedField, $error)
                ])
                ->toArray();

            $data->error = $transformedErrors;
            $response->setData($data);
        }

        return $response;
    }
}
