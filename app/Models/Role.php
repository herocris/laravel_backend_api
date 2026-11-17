<?php

namespace App\Models;

use App\Traits\Activitylog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use Activitylog, HasFactory,SoftDeletes;

    /**
     * Persiste datos administrativos ya autorizados y validados, sin depender de HTTP.
     * Los permisos omitidos se conservan; una lista vacía los revoca.
     *
     * @param  array{name?: string, permissions?: list<int|string>}  $data
     */
    public function saveWithPermissions(array $data): self
    {
        return $this->getConnection()->transaction(function () use ($data): self {
            $this->fill(Arr::only($data, ['name']));
            if (! $this->exists) {
                $this->guard_name = 'api';
            }
            $this->save();

            if (array_key_exists('permissions', $data)) {
                $this->syncPermissions(array_map('intval', $data['permissions']));
            }

            return $this;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['name'], 'role');
    }
}
