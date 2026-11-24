<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticación JWT por cookie HttpOnly o Bearer, con auditoría de sesión.
 */
class AuthController extends Controller
{
    public function csrfCookie(): Response
    {
        return response()->noContent();
    }

    /**
     * Registra un nuevo usuario usando los datos validados y genera un
     * token JWT inicial. Side effects: dispara eventos Eloquent (creating/created)
     * y registra automáticamente sesión iniciada. Devuelve estructura con
     * metadatos de expiración y permisos asociados.
     *
     * @param  RegisterRequest  $request  Datos públicos validados, sin asignaciones.
     * @return JsonResponse Datos de sesión + usuario.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::create($validated);
        $token = auth()->login($user);
        $request->session()->regenerate();

        return $this->respondWithToken($token);
    }

    /**
     * Autentica un usuario con email y password. Si las credenciales son
     * inválidas retorna 401 con mensaje de error. En caso exitoso registra
     * evento de login y devuelve token + roles + permisos.
     *
     * @return JsonResponse Token y datos de usuario o error 401.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $credentials = $validated;
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }
        $request->session()->regenerate();

        return $this->respondWithToken($token);
    }

    /**
     * Consulta perfil y autorización vigentes sin emitir ni modificar el JWT.
     *
     * @return JsonResponse Datos básicos, roles y permisos efectivos del usuario.
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();
        $user->load(['roles.permissions', 'permissions']);

        return response()->json($this->sessionData($user));
    }

    /**
     * Cierra la sesión invalidando el token y removiendo la cookie asociada.
     * Side effects: registra evento de logout en el activity log. Devuelve
     * mensaje genérico de éxito.
     *
     * @return JsonResponse Confirmación de cierre de sesión.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logout successful'])
            ->withCookie(cookie()->forget('token', config('session.path'), config('session.domain')));
    }

    /**
     * Refresca (rota) el token JWT extendiendo su vigencia. Útil para sesiones
     * largas sin requerir reingreso de credenciales. Devuelve nuevo token y
     * TTL actualizado. Mantiene contexto de usuario.
     *
     * @return JsonResponse Nuevo token y metadatos.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Construye respuesta estandarizada de autenticación. Incluye tipo de token,
     * segundos hasta expiración (`expires_in`), datos básicos del usuario y
     * roles/permisos actuales. Adjunta cookie HttpOnly con atributos del entorno
     * para uso en navegadores evitando exposición a scripts.
     *
     * @param  string  $token  Token JWT recién emitido o refrescado.
     * @return JsonResponse Estructura JSON de sesión.
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        $expiresAt = Auth::setToken($token)->payload()->get('exp');
        $expiresIn = max(0, $expiresAt - now()->timestamp);
        $cookie = cookie(
            'token', $token, 0, config('session.path'), config('session.domain'),
            config('session.secure'), true, false, config('session.same_site'),
        )->withExpires($expiresAt);

        return response()->json([
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
            ...$this->sessionData(Auth::user()),
        ])->cookie($cookie);
    }

    /**
     * @return array{user: array{name: string, email: string}, roles: list<string>, permissions: list<string>}
     */
    private function sessionData(User $user): array
    {
        return [
            'user' => $user->only(['name', 'email']),
            'roles' => $user->getRoleNames()->unique()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->unique()->values()->all(),
        ];
    }
}
