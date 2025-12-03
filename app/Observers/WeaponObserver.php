<?php

namespace App\Observers;

use App\Models\Weapon;
use Illuminate\Support\Facades\Storage;

/**
 * Observer para el modelo Weapon.
 * 
 * Gestiona automáticamente la carga y eliminación de archivos logo (imagen PNG) asociados a armas.
 * Los archivos se almacenan en storage/app/public/weapon.
 */
class WeaponObserver
{
    /**
     * Maneja el evento "creating" del modelo Weapon.
     * 
     * Si el request contiene un archivo 'logo', lo almacena en el disco público bajo el directorio 'weapon'
     * y asigna la ruta al atributo logo del modelo antes de guardarlo.
     * 
     * @param Weapon $weapon El arma que está siendo creada
     * @return void
     */
    public function creating(Weapon $weapon): void
    {
        if (request()->hasFile('logo')) {
            $weapon->logo = request()->file('logo')->store('weapon', 'public');
        }
    }

    /**
     * Maneja el evento "updating" del modelo Weapon.
     * 
     * Si el request contiene un nuevo archivo 'logo':
     * - Elimina el logo anterior del disco público si existe
     * - Almacena el nuevo logo en el directorio 'weapon'
     * - Actualiza el atributo logo con la nueva ruta
     * 
     * @param Weapon $weapon El arma que está siendo actualizada
     * @return void
     */
    public function updating(Weapon $weapon): void
    {
        if (request()->hasFile('logo')) {
            if ($weapon->getOriginal('logo')) {
                Storage::disk('public')->delete($weapon->getOriginal('logo'));
            }
            $weapon->logo = request()->file('logo')->store('weapon', 'public');
        }
    }

    /**
     * Handle the Weapon "deleted" event.
     */
    public function deleted(Weapon $weapon): void
    {
        //
    }

    /**
     * Handle the Weapon "restored" event.
     */
    public function restored(Weapon $weapon): void
    {
        //
    }

    /**
     * Handle the Weapon "force deleted" event.
     */
    public function forceDeleted(Weapon $weapon): void
    {
        //
    }
}
