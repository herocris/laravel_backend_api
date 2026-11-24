<?php

namespace App\Support\Authorization;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class AdministrativeTransaction
{
    public function __construct(private PermissionRegistrar $permissions) {}

    /**
     * Serializa el grafo de autorización completo, también cuando aún no hay pivotes.
     * Debe envolver la primera lectura de autorización de la operación.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed
    {
        $connection = DB::connection();
        $cache = $this->permissions->getCacheRepository();
        $cacheKey = $this->permissions->cacheKey;
        $expiration = $this->permissions->cacheExpirationTime;

        try {
            return $connection->transaction(function () use ($callback, $connection, $cache, $cacheKey): mixed {
                if ($connection->table('authorization_locks')->where('name', 'api')->increment('version') !== 1) {
                    throw new RuntimeException('Falta el bloqueo administrativo api; ejecuta las migraciones.');
                }

                $this->permissions->clearPermissionsCollection();
                // Una clave privada sin persistencia impide leer instantáneas ajenas de Spatie.
                $this->permissions->cacheKey = $cacheKey.':administrative:'.Str::uuid();
                $this->permissions->cacheExpirationTime = 0;
                $connection->afterCommit(fn () => $cache->forget($cacheKey));

                return $callback();
            });
        } finally {
            $this->permissions->forgetCachedPermissions();
            $this->permissions->cacheKey = $cacheKey;
            $this->permissions->cacheExpirationTime = $expiration;
            $this->permissions->forgetCachedPermissions();
        }
    }
}
