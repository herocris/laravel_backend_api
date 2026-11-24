<?php

namespace App\Support\Auth;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;

class AuthenticationActivityLogger
{
    public function handle(Login|Logout|Failed $event): void
    {
        $request = app()->bound('request') ? request() : null;
        $user = $event->user;
        $properties = [
            'guard' => $event->guard,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ];

        if ($event instanceof Failed) {
            $name = 'failed';
            $description = 'Intento fallido de inicio de sesión';
            $properties['email'] = $event->credentials['email'] ?? null;
        } else {
            $name = $event instanceof Login ? 'login' : 'logout';
            $action = $event instanceof Login ? 'iniciado' : 'cerrado';
            $description = "El usuario {$user?->name} ha {$action} sesión";
            $properties['name'] = $user?->name;
            $properties['email'] = $user?->email;
        }

        $activity = activity($user?->name ?? 'auth')->event($name)->withProperties($properties);

        if ($user instanceof Model) {
            $activity->causedBy($user);
        } else {
            $activity->causedByAnonymous();
        }

        $activity->log($description);
    }
}
