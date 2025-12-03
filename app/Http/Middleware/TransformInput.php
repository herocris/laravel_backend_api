<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

/**
 * Middleware para transformar claves de entrada/salida según un Resource.
 *
 * Propósito:
 * - Permite que el cliente envíe nombres de atributos "de dominio" mientras el
 *   backend trabaja con nombres internos distintos.
 * - Reescribe errores de validación para reflejar las claves que conoce el cliente.
 *
 * Requisitos del Resource:
 * - Métodos estáticos `originalAttribute($key)` y `transformedAttribute($key)`.
 *
 * Flujo:
 * 1. Construye nuevo array de request mapeando cada clave entrante.
 * 2. Reemplaza el contenido del Request con las claves internas.
 * 3. Ejecuta la acción siguiente.
 * 4. Si hubo ValidationException, re-mapea los nombres de campo en los errores.
 */
class TransformInput
{
    /**
     * Ejecuta la transformación y reescritura de errores.
     *
     * @param Request $request  Petición HTTP.
     * @param Closure $next     Siguiente middleware.
     * @param string  $resource Nombre FQCN del Resource que define el mapeo.
     * @return Response Respuesta HTTP (posiblemente con errores transformados).
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $transformedInput = collect($request->all())
            ->mapWithKeys(fn ($value, $key) => [
                $resource::originalAttribute($key) => $value
            ])
            ->toArray();

        $request->replace($transformedInput);

        $response = $next($request);

        if ($response->exception instanceof ValidationException) {
            $data = $response->getData();

            $transformedErrors = collect($data->error ?? [])
                ->mapWithKeys(function ($error, $field) use ($resource) {
                    $transformedField = $resource::transformedAttribute($field);
                    return [$transformedField => str_replace($field, $transformedField, $error)];
                })
                ->toArray();

            $data->error = $transformedErrors;
            $response->setData($data);
        }

        return $response;
    }
}
