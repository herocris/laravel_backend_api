<?php

namespace App\Observers;

use App\Models\WeaponConfiscation;
use Illuminate\Support\Facades\Storage;

/**
 * Observer para el modelo WeaponConfiscation.
 * 
 * Gestiona automáticamente la carga y eliminación de archivos photo (imagen PNG) asociados a confiscaciones de armas.
 * Los archivos se almacenan en storage/app/public/weaponConfiscation.
 * Nota: El request envía 'foto' pero el modelo almacena en 'photo'.
 */
class WeaponConfiscationObserver
{
    /**
     * Maneja el evento "creating" del modelo WeaponConfiscation.
     * 
     * Si el request contiene un archivo 'foto', lo almacena en el disco público bajo el directorio 'weaponConfiscation'
     * y asigna la ruta al atributo photo del modelo antes de guardarlo.
     * 
     * @param WeaponConfiscation $weaponConfiscation La confiscación de arma que está siendo creada
     * @return void
     */
    public function creating(WeaponConfiscation $weaponConfiscation): void
    {
        if (request()->hasFile('foto')) {
            $weaponConfiscation->photo = request()->file('foto')->store('weaponConfiscation', 'public');
        }
    }

    /**
     * Maneja el evento "updating" del modelo WeaponConfiscation.
     * 
     * Si el request contiene un nuevo archivo 'foto':
     * - Elimina la foto anterior del disco público si existe
     * - Almacena la nueva foto en el directorio 'weaponConfiscation'
     * - Actualiza el atributo photo con la nueva ruta
     * 
     * @param WeaponConfiscation $weaponConfiscation La confiscación de arma que está siendo actualizada
     * @return void
     */
    public function updating(WeaponConfiscation $weaponConfiscation): void
    {
        if (request()->hasFile('foto')) {
            if ($weaponConfiscation->getOriginal('photo')) {
                Storage::disk('public')->delete($weaponConfiscation->getOriginal('photo'));
            }
            $weaponConfiscation->photo = request()->file('foto')->store('weaponConfiscation', 'public');
        }
    }

    /**
     * Handle the WeaponConfiscation "deleted" event.
     */
    public function deleted(WeaponConfiscation $weaponConfiscation): void
    {
        //
    }

    /**
     * Handle the WeaponConfiscation "restored" event.
     */
    public function restored(WeaponConfiscation $weaponConfiscation): void
    {
        //
    }

    /**
     * Handle the WeaponConfiscation "force deleted" event.
     */
    public function forceDeleted(WeaponConfiscation $weaponConfiscation): void
    {
        //
    }
}
