<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Admin\User\StorePostRequest;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Controlador para flujo de autenticación basado en JWT.
     * Expone endpoints de registro, login, perfil ("me"), logout y refresh.
     * Maneja emisión y rotación del token, además de registrar eventos de
     * actividad (login/logout) para auditoría. El token también se adjunta
     * como cookie HttpOnly y Secure para mitigar riesgos de XSS.
     */

    /**
     * Registra un nuevo usuario usando los datos validados y genera un
     * token JWT inicial. Side effects: dispara eventos Eloquent (creating/created)
     * y registra automáticamente sesión iniciada. Devuelve estructura con
     * metadatos de expiración y permisos asociados.
     *
     * @param StorePostRequest $request Datos ya validados (incluye contraseña).
     * @return \Illuminate\Http\JsonResponse Datos de sesión + usuario.
     */
    public function register(StorePostRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);
        $token = auth()->login($user);
        return $this->respondWithToken($token);
    }

    /**
     * Autentica un usuario con email y password. Si las credenciales son
     * inválidas retorna 401 con mensaje de error. En caso exitoso registra
     * evento de login y devuelve token + roles + permisos.
     *
     * @return \Illuminate\Http\JsonResponse Token y datos de usuario o error 401.
     */
    public function login()
    {
        $validated = request()->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $credentials = $validated;
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }
        $this->logAndLoginEvent(Auth::user(), 'iniciado');
        return $this->respondWithToken($token);
    }

    /**
     * Devuelve el usuario actualmente autenticado según el token enviado.
     * Útil para reconstruir sesión del lado cliente (perfil). No incluye
     * relaciones extendidas para mantener la respuesta ligera.
     *
     * @return \Illuminate\Http\JsonResponse Datos básicos del usuario.
     */
    public function me()
    {
        return response()->json(Auth::user());
    }

    /**
     * Cierra la sesión invalidando el token y removiendo la cookie asociada.
     * Side effects: registra evento de logout en el activity log. Devuelve
     * mensaje genérico de éxito.
     *
     * @return \Illuminate\Http\JsonResponse Confirmación de cierre de sesión.
     */
    public function logout()
    {
        $this->logAndLoginEvent(Auth::user(), 'cerrado');
        Auth::logout(true);
        return response()->json(['message' => 'Logout successful'])
            ->withCookie(cookie()->forget('token'));
    }

    /**
     * Refresca (rota) el token JWT extendiendo su vigencia. Útil para sesiones
     * largas sin requerir reingreso de credenciales. Devuelve nuevo token y
     * TTL actualizado. Mantiene contexto de usuario.
     *
     * @return \Illuminate\Http\JsonResponse Nuevo token y metadatos.
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh(true, true));
    }

    /**
     * Construye respuesta estandarizada de autenticación. Incluye tipo de token,
     * segundos hasta expiración (`expires_in`), datos básicos del usuario y
     * roles/permisos actuales. Adjunta cookie segura (Secure + HttpOnly + SameSite Strict)
     * para uso en navegadores evitando exposición a scripts.
     *
     * @param string $token Token JWT recién emitido o refrescado.
     * @return \Illuminate\Http\JsonResponse Estructura JSON de sesión.
     */
    protected function respondWithToken($token)
    {
        $cookie = cookie('token', $token, 60, '/', null, true, true, false, 'Strict');
        return response()->json([
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()->only(['name', 'email']),
            'roles' => Auth::user()->getRoleNames(),
            'permissions' => Auth::user()->getAllPermissions()->pluck('name'),
        ])->cookie($cookie);
    }

    /**
     * Registra en el activity log un evento de inicio o cierre de sesión.
     * Permite auditoría y rastreo de accesos. Se guardan propiedades clave
     * (nombre y email) para facilitar búsquedas posteriores.
     *
     * @param User   $user      Usuario involucrado.
     * @param string $activity  'iniciado' | 'cerrado'
     * @return void
     */
    private function logAndLoginEvent(User $user, string $activity): void
    {
        activity($user->name)
            ->causedBy($user)
            ->event($activity === 'iniciado' ? 'login' : 'logout')
            ->withProperties(['name' => $user->name, 'email' => $user->email])
            ->log("El usuario {$user->name} ha {$activity} sesión");
    }
}
