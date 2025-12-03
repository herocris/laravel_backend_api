<?php

namespace App\Observers;

use App\Models\Drug;
use Illuminate\Support\Facades\Storage;

/**
 * Observer para el modelo Drug.
 * 
 * Gestiona automáticamente la carga y eliminación de archivos logo (imagen PNG) asociados a drogas.
 * Los archivos se almacenan en storage/app/public/drug.
 */
class DrugObserver
{
    /**
     * Maneja el evento "creating" del modelo Drug.
     * 
     * Si el request contiene un archivo 'logo', lo almacena en el disco público bajo el directorio 'drug'
     * y asigna la ruta al atributo logo del modelo antes de guardarlo en la base de datos.
     * 
     * @param Drug $drug La droga que está siendo creada
     * @return void
     */
    public function creating(Drug $drug): void
    {//dd(request()->all());
        if (request()->hasFile('logo')) {
            $drug->logo = request()->file('logo')->store('drug', 'public');
        }
    }

    /**
     * Maneja el evento "updating" del modelo Drug.
     * 
     * Si el request contiene un nuevo archivo 'logo':
     * - Elimina el logo anterior del disco público si existe
     * - Almacena el nuevo logo en el directorio 'drug'
     * - Actualiza el atributo logo del modelo con la nueva ruta
     * 
     * @param Drug $drug La droga que está siendo actualizada
     * @return void
     */
    public function updating(Drug $drug): void
    {
        if (request()->hasFile('logo')) {
            if ($drug->getOriginal('logo')) {
                Storage::disk('public')->delete($drug->getOriginal('logo'));
            }
            $drug->logo = request()->file('logo')->store('drug', 'public');
        }
    }

    /**
     * Handle the Drug "deleted" event.
     */
    public function deleted(Drug $drug): void
    {
        //
    }

    /**
     * Handle the Drug "restored" event.
     */
    public function restored(Drug $drug): void
    {
        //
    }

    /**
     * Handle the Drug "force deleted" event.
     */
    public function forceDeleted(Drug $drug): void
    {
        //
    }
}
