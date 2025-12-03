<?php

namespace App\Observers;

use App\Models\DrugPresentation;
use Illuminate\Support\Facades\Storage;

/**
 * Observer para el modelo DrugPresentation.
 * 
 * Gestiona automáticamente la carga y eliminación de archivos logo (imagen PNG) asociados a presentaciones de drogas.
 * Los archivos se almacenan en storage/app/public/drugPresentation.
 */
class DrugPresentationObserver
{
    /**
     * Maneja el evento "creating" del modelo DrugPresentation.
     * 
     * Si el request contiene un archivo 'logo', lo almacena en el disco público bajo el directorio 'drugPresentation'
     * y asigna la ruta al atributo logo del modelo antes de guardarlo.
     * 
     * @param DrugPresentation $drugPresentation La presentación de droga que está siendo creada
     * @return void
     */
    public function creating(DrugPresentation $drugPresentation): void
    {
        if (request()->hasFile('logo')) {
            $drugPresentation->logo = request()->file('logo')->store('drugPresentation', 'public');
        }
    }

    /**
     * Maneja el evento "updating" del modelo DrugPresentation.
     * 
     * Si el request contiene un nuevo archivo 'logo':
     * - Elimina el logo anterior del disco público si existe
     * - Almacena el nuevo logo en el directorio 'drugPresentation'
     * - Actualiza el atributo logo con la nueva ruta
     * 
     * @param DrugPresentation $drugPresentation La presentación de droga que está siendo actualizada
     * @return void
     */
    public function updating(DrugPresentation $drugPresentation): void
    {
        if (request()->hasFile('logo')) {
            if ($drugPresentation->getOriginal('logo')) {
                Storage::disk('public')->delete($drugPresentation->getOriginal('logo'));
            }
            $drugPresentation->logo = request()->file('logo')->store('drugPresentation', 'public');
        }
    }

    /**
     * Handle the DrugPresentation "deleted" event.
     */
    public function deleted(DrugPresentation $drugPresentation): void
    {
        //
    }

    /**
     * Handle the DrugPresentation "restored" event.
     */
    public function restored(DrugPresentation $drugPresentation): void
    {
        //
    }

    /**
     * Handle the DrugPresentation "force deleted" event.
     */
    public function forceDeleted(DrugPresentation $drugPresentation): void
    {
        //
    }
}
