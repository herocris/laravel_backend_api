<?php

namespace App\Support\Auth;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard;

/**
 * JWT Auth does not emit Laravel authentication events by itself.
 */
class EventfulJwtGuard extends JWTGuard
{
    public function __construct(JWT $jwt, UserProvider $provider, Request $request, private Dispatcher $events, private string $name)
    {
        parent::__construct($jwt, $provider, $request);
    }

    public function attempt(array $credentials = [], mixed $login = true): bool|string
    {
        $result = parent::attempt($credentials, $login);

        if ($result === false) {
            $this->events->dispatch(new Failed($this->name, $this->getLastAttempted(), $credentials));
        }

        return $result;
    }

    public function login(JWTSubject $user): string
    {
        $token = parent::login($user);
        $this->events->dispatch(new Login($this->name, $user, false));

        return $token;
    }

    public function logout(mixed $forceForever = false): void
    {
        $user = $this->user();
        parent::logout($forceForever);
        $this->events->dispatch(new Logout($this->name, $user));
    }
}
