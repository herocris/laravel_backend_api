<?php

namespace App\Observers;

use App\Models\Ammunition;
use Illuminate\Support\Facades\Storage;

/**
 * Observer para el modelo Ammunition.
 * 
 * Gestiona automáticamente la carga y eliminación de archivos logo (imagen PNG) asociados a municiones.
 * Los archivos se almacenan en storage/app/public/ammunition.
 */
class AmmunitionObserver
{
    /**
     * Maneja el evento "creating" del modelo Ammunition.
     * 
     * Si el request contiene un archivo 'logo', lo almacena en el disco público bajo el directorio 'ammunition'
     * y asigna la ruta al atributo logo del modelo antes de guardarlo.
     * 
     * @param Ammunition $ammunition La munición que está siendo creada
     * @return void
     */
    public function creating(Ammunition $ammunition): void
    {
        if (request()->hasFile('logo')) {
            $ammunition->logo = request()->file('logo')->store('ammunition', 'public');
        }
    }

    /**
     * Maneja el evento "updating" del modelo Ammunition.
     * 
     * Si el request contiene un nuevo archivo 'logo':
     * - Elimina el logo anterior del disco público si existe
     * - Almacena el nuevo logo en el directorio 'ammunition'
     * - Actualiza el atributo logo con la nueva ruta
     * 
     * @param Ammunition $ammunition La munición que está siendo actualizada
     * @return void
     */
    public function updating(Ammunition $ammunition): void
    {
        if (request()->hasFile('logo')) {
            if ($ammunition->getOriginal('logo')) {
                Storage::disk('public')->delete($ammunition->getOriginal('logo'));
            }
            $ammunition->logo = request()->file('logo')->store('ammunition', 'public');
        }
    }

    /**
     * Maneja el evento "deleted" del modelo Ammunition.
     * 
     * Elimina el archivo logo del disco público cuando se elimina (soft delete) una munición.
     * Esto evita archivos huérfanos en el almacenamiento.
     * 
     * @param Ammunition $ammunition La munición eliminada
     * @return void
     */
    public function deleted(Ammunition $ammunition): void
    {
        if ($ammunition->logo) {
            Storage::disk('public')->delete($ammunition->logo);
        }
    }

    /**
     * Handle the Ammunition "restored" event.
     */
    public function restored(Ammunition $ammunition): void
    {
        //
    }

    /**
     * Handle the Ammunition "force deleted" event.
     */
    public function forceDeleted(Ammunition $ammunition): void
    {
        //
    }
}
