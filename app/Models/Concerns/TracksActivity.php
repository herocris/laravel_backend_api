<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\CauserResolver;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Trait TracksActivity
 *
 * Proporciona funcionalidad de registro de actividad para modelos usando Spatie Activity Log.
 * Configura el registro automático de cambios en campos especificados, incluyendo el usuario
 * que realizó la acción y el tipo de evento (crear, actualizar, eliminar, restaurar).
 *
 * Uso: incluir este trait en modelos que requieran auditoría de cambios.
 */
trait TracksActivity
{
    use LogsActivity;

    /**
     * Configura las opciones de registro de actividad para el modelo.
     *
     * Define qué campos se registran, activa el registro solo de campos modificados (dirty),
     * genera descripciones en español y usa el mismo actor que Spatie para el nombre del log.
     * Sin actor explícito ni sesión, Sistema se representa con causer nulo, sin crear usuarios.
     *
     * @param  list<string>  $fields  Campos del modelo que deben ser registrados
     */
    protected function RecordLog(array $fields, string $model): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($fields)
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => 'Se ha '.$this->eventName($eventName).' el '.$model)
            ->useLogName(app(CauserResolver::class)->resolve()?->name ?? 'Sistema');
    }

    /**
     * Traduce nombres de eventos de Eloquent al español.
     *
     * Convierte eventos del ciclo de vida del modelo (created, updated, deleted, restored)
     * a sus equivalentes en español para descripciones más legibles en los logs.
     *
     * @param  string  $evento  Nombre del evento en inglés (created, updated, deleted, restored)
     * @return string Nombre del evento traducido al español (creado, actualizado, borrado, restaurado)
     */
    private function eventName(string $evento): string
    {
        return match ($evento) {
            'created' => 'creado',
            'updated' => 'actualizado',
            'deleted' => 'borrado',
            'restored' => 'restaurado',
        };
    }
}
