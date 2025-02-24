<?php

namespace App\Models;

use App\Http\Resources\PermissionResource;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;

class Permission extends SpatiePermission
{
    use HasFactory, LogsActivity;

    public $resource = PermissionResource::class;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name','guard_name'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Se ha " . eventName($eventName) . " el permiso")
            ->useLogName(Auth::user()->name);
    }
}
