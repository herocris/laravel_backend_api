<?php

namespace App\Observers;

use App\Models\DrugConfiscation;
use Illuminate\Support\Facades\Storage;

/**
 * Observer para el modelo DrugConfiscation.
 * 
 * Gestiona automáticamente la carga y eliminación de archivos photo (imagen PNG) asociados a confiscaciones de drogas.
 * Los archivos se almacenan en storage/app/public/drugConfiscation.
 * Nota: El request envía 'foto' pero el modelo almacena en 'photo'.
 */
class DrugConfiscationObserver
{
    /**
     * Maneja el evento "creating" del modelo DrugConfiscation.
     * 
     * Si el request contiene un archivo 'foto', lo almacena en el disco público bajo el directorio 'drugConfiscation'
     * y asigna la ruta al atributo photo del modelo antes de guardarlo en la base de datos.
     * 
     * @param DrugConfiscation $drugConfiscation La confiscación de droga que está siendo creada
     * @return void
     */
    public function creating(DrugConfiscation $drugConfiscation): void
    {
        if (request()->hasFile('foto')) {
            $drugConfiscation->photo = request()->file('foto')->store('drugConfiscation', 'public');
        }
    }

    /**
     * Maneja el evento "updating" del modelo DrugConfiscation.
     * 
     * Si el request contiene un nuevo archivo 'foto':
     * - Elimina la foto anterior del disco público si existe
     * - Almacena la nueva foto en el directorio 'drugConfiscation'
     * - Actualiza el atributo photo del modelo con la nueva ruta
     * 
     * @param DrugConfiscation $drugConfiscation La confiscación de droga que está siendo actualizada
     * @return void
     */
    public function updating(DrugConfiscation $drugConfiscation): void
    {
        if (request()->hasFile('foto')) {
            if ($drugConfiscation->getOriginal('photo')) {
                Storage::disk('public')->delete($drugConfiscation->getOriginal('photo'));
            }
            $drugConfiscation->photo = request()->file('foto')->store('drugConfiscation', 'public');
        }
    }

    /**
     * Handle the DrugConfiscation "deleted" event.
     */
    public function deleted(DrugConfiscation $drugConfiscation): void
    {
        //
    }

    /**
     * Handle the DrugConfiscation "restored" event.
     */
    public function restored(DrugConfiscation $drugConfiscation): void
    {
        //
    }

    /**
     * Handle the DrugConfiscation "force deleted" event.
     */
    public function forceDeleted(DrugConfiscation $drugConfiscation): void
    {
        //
    }
}
