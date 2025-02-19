<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;

class Role extends SpatieRole
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name','guard_name'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Se ha " . eventName($eventName) . " el rol")
            ->useLogName(Auth::user()->name);
    }
}
