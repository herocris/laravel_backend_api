<?php

namespace App\Observers;

use App\Models\AmmunitionConfiscation;
use Illuminate\Support\Facades\Storage;

/**
 * Observer para el modelo AmmunitionConfiscation.
 * 
 * Gestiona automáticamente la carga y eliminación de archivos photo (imagen PNG) asociados a confiscaciones de municiones.
 * Los archivos se almacenan en storage/app/public/ammunitionConfiscation.
 * Nota: El request envía 'foto' pero el modelo almacena en 'photo'.
 */
class AmmunitionConfiscationObserver
{
    /**
     * Maneja el evento "creating" del modelo AmmunitionConfiscation.
     * 
     * Si el request contiene un archivo 'foto', lo almacena en el disco público bajo el directorio 'ammunitionConfiscation'
     * y asigna la ruta al atributo photo del modelo antes de guardarlo.
     * 
     * @param AmmunitionConfiscation $ammunitionConfiscation La confiscación de munición que está siendo creada
     * @return void
     */
    public function creating(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        if (request()->hasFile('foto')) {
            $ammunitionConfiscation->photo = request()->file('foto')->store('ammunitionConfiscation', 'public');
        }
    }

    /**
     * Maneja el evento "updating" del modelo AmmunitionConfiscation.
     * 
     * Si el request contiene un nuevo archivo 'foto':
     * - Elimina la foto anterior del disco público si existe
     * - Almacena la nueva foto en el directorio 'ammunitionConfiscation'
     * - Actualiza el atributo photo con la nueva ruta
     * 
     * @param AmmunitionConfiscation $ammunitionConfiscation La confiscación de munición que está siendo actualizada
     * @return void
     */
    public function updating(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        if (request()->hasFile('foto')) {
            if ($ammunitionConfiscation->getOriginal('photo')) {
                Storage::disk('public')->delete($ammunitionConfiscation->getOriginal('photo'));
            }
            $ammunitionConfiscation->photo = request()->file('foto')->store('ammunitionConfiscation', 'public');
        }
    }

    /**
     * Handle the AmmunitionConfiscation "deleted" event.
     */
    public function deleted(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        //
    }

    /**
     * Handle the AmmunitionConfiscation "restored" event.
     */
    public function restored(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        //
    }

    /**
     * Handle the AmmunitionConfiscation "force deleted" event.
     */
    public function forceDeleted(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        //
    }
}
