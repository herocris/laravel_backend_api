<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\Activitylog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
/** Trait para invocar la funcion getActivitylogOptions */

use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use Activitylog, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Persiste datos administrativos ya autorizados y validados, sin depender de HTTP.
     * Las asignaciones omitidas se conservan; una lista vacía las revoca.
     *
     * @param  array{name?: string, email?: string, password?: string, roles?: list<int|string>, permissions?: list<int|string>}  $data
     */
    public function saveWithAssignments(array $data): self
    {
        return $this->getConnection()->transaction(function () use ($data): self {
            $this->fill(Arr::only($data, ['name', 'email', 'password']));
            $this->save();

            if (array_key_exists('roles', $data)) {
                $this->syncRoles(array_map('intval', $data['roles']));
            }

            if (array_key_exists('permissions', $data)) {
                $this->syncPermissions(array_map('intval', $data['permissions']));
            }

            return $this;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['name', 'email'], 'user');
    }
}
