<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait Activitylog
 * 
 * Proporciona funcionalidad de registro de actividad para modelos usando Spatie Activity Log.
 * Configura el registro automático de cambios en campos especificados, incluyendo el usuario
 * que realizó la acción y el tipo de evento (crear, actualizar, eliminar, restaurar).
 * 
 * Uso: incluir este trait en modelos que requieran auditoría de cambios.
 */
trait Activitylog
{
    use LogsActivity;
    
    /**
     * Configura las opciones de registro de actividad para el modelo.
     * 
     * Define qué campos se registran, activa el registro solo de campos modificados (dirty),
     * genera descripciones en español para cada evento, y asigna el nombre del usuario autenticado
     * como log name (o 'Sistema' en caso de seeders).
     * 
     * @param array $fields Lista de campos del modelo que deben ser registrados
     * @param string $model Nombre del modelo en español para descripciones legibles
     * @return LogOptions Configuración de opciones de registro
     */
    protected function RecordLog($fields, $model): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($fields)
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Se ha " . $this->eventName($eventName) . " el " . $model)
            ->useLogName(($model == 'user' ? Auth::user()->name ?? 'Sistema' : Auth::user()->name)); //ternario para determinar el modelo que en caso de ser user añade logica necesario para la ejecución del sedder
        // Chain fluent methods for configuration options
    }

    /**
     * Traduce nombres de eventos de Eloquent al español.
     * 
     * Convierte eventos del ciclo de vida del modelo (created, updated, deleted, restored)
     * a sus equivalentes en español para descripciones más legibles en los logs.
     * 
     * @param string $evento Nombre del evento en inglés (created, updated, deleted, restored)
     * @return string Nombre del evento traducido al español (creado, actualizado, borrado, restaurado)
     */
    private function eventName(String $evento): String
    {
        return match ($evento) {//en lugar de switch
            'created' => 'creado',
            'updated' => 'actualizado',
            'deleted' => 'borrado',
            'restored' => 'restaurado',
        };
    }
}
