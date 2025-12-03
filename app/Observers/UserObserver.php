<?php

namespace App\Observers;

use App\Models\User;

/**
 * Observer para el modelo User.
 * 
 * Gestiona la sincronización automática de roles y permisos cuando se crea o actualiza un usuario.
 * Los roles y permisos se obtienen del request actual y se sincronizan mediante Spatie Permission.
 */
class UserObserver
{
    /**
     * Maneja el evento "created" del modelo User.
     * 
     * Sincroniza roles y permisos del usuario recién creado con los valores recibidos en el request.
     * Si no se proporcionan roles o permisos, sincroniza con arrays vacíos.
     * 
     * @param User $user El usuario recién creado
     * @return void
     */
    public function created(User $user): void
    {
        $user->syncRoles(request()->roles ?? []);
        $user->syncPermissions(request()->permissions ?? []);
    }

    /**
     * Maneja el evento "updated" del modelo User.
     * 
     * Resincroniza roles y permisos del usuario actualizado con los valores recibidos en el request.
     * Reemplaza completamente los roles y permisos existentes con los nuevos.
     * 
     * @param User $user El usuario actualizado
     * @return void
     */
    public function updated(User $user): void
    {
        $user->syncRoles(request()->roles ?? []);
        $user->syncPermissions(request()->permissions ?? []);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
