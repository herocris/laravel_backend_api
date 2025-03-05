<?php

namespace App\Models;

use App\Http\Resources\Admin\Permission\PermissionResource;
use App\Traits\Activitylog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends SpatiePermission
{
    use HasFactory, Activitylog, SoftDeletes;

    public $resource = PermissionResource::class;

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['name'],'permission');
    }
}
