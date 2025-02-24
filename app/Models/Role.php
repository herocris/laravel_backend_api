<?php

namespace App\Models;

use App\Http\Resources\RoleResource;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;

class Role extends SpatieRole
{
    use HasFactory, LogsActivity;

    public $resource = RoleResource::class;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Se ha " . eventName($eventName) . " el rol")
            ->useLogName(Auth::user()->name);
    }
}
