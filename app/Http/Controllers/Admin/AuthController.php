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
     * Create a new AuthController instance.
     *
     * @return void
     */


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function register(StorePostRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);
        $token = auth()->login($user);
        return $this->respondWithToken($token);
    }
    public function login()
    {
        $credentials = request(['email', 'password']);
        if (! $token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }
        $this->logAndLoginEvent(Auth::user(), 'iniciado');

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(Auth::user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->logAndLoginEvent(Auth::user(), 'cerrado');
        Auth::logout(true);
        //
        //return response()->json(['message' => 'Successfully logged out']);
        return response()->json(['message' => 'Logout successful'])
            ->withCookie(cookie()->forget('token'));
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh(true, true));
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        // return response()->json([
        //     'access_token' => $token,
        //     'token_type' => 'bearer',
        //     'expires_in' => Auth::factory()->getTTL() * 60,
        //     'roles' => Auth::user()->getRoleNames(),
        //     'permissions' => Auth::user()->getAllPermissions()->pluck('name'),
        // ]);

        $cookie = cookie('token', $token, 60, '/', null, true, true, false, 'Strict');
        return response()->json([
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => Auth::user()->only(['name', 'email']),
            'roles' => Auth::user()->getRoleNames(),
            'permissions' => Auth::user()->getAllPermissions()->pluck('name'),
        ])->cookie($cookie);
    }

    private function logAndLoginEvent(User $user, String $activity)
    {
        activity($user->name)
            ->causedBy($user)
            ->event($activity == 'iniciado' ? 'login' : 'logout')
            ->withProperties(['name' => $user->name, 'email' => $user->email])
            ->log("El usuario {$user->name} ha {$activity} secion ");
    }
}
