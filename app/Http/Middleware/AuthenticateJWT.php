<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Middleware de autenticación vía JWT usando cookie HttpOnly.
 *
 * Flujo:
 * 1. Extrae token de la cookie `token`.
 * 2. Configura el token en JWTAuth y autentica.
 * 3. Si falta o es inválido responde 401 con mensaje genérico.
 * 4. Deja pasar la petición si la autenticación es exitosa.
 *
 * Consideraciones de seguridad:
 * - Usa cookie HttpOnly para mitigar XSS.
 * - Recomendado combinar con verificación CSRF si se emplean métodos state-changing.
 * - Tokens expirados generan excepción capturada y devuelven 401.
 */
class AuthenticateJWT
{
    /**
     * Maneja la solicitud entrante validando el token JWT.
     *
     * @param Request $request Petición HTTP actual.
     * @param Closure $next    Siguiente middleware/capa.
     * @return Response Respuesta HTTP (401 si falla, flujo normal si éxito).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $token = $request->cookie('token')) {
            return response()->json(['error' => 'Token not provided'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            JWTAuth::setToken($token);
            JWTAuth::authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
