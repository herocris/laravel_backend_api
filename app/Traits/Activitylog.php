<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait Activitylog
{
    use LogsActivity;
    protected function RecordLog($fields, $model): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($fields)
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Se ha " . $this->eventName($eventName) . " el " . $model)
            ->useLogName(($model == 'user' ? Auth::user()->name ?? 'Sistema' : Auth::user()->name)); //ternario para determinar el modelo que en caso de ser user añade logica necesario para la ejecución del sedder
        // Chain fluent methods for configuration options
    }

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
