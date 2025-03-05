<?php

namespace App\Models;

use App\Http\Resources\Admin\Role\RoleResource;
use App\Traits\Activitylog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends SpatieRole
{
    use HasFactory, SoftDeletes,Activitylog;

    public $resource = RoleResource::class;

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['name'],'role');
    }
}
