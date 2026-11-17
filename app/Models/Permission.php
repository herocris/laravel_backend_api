<?php

namespace App\Models;

use App\Traits\Activitylog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use Activitylog, HasFactory, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['name'], 'permission');
    }
}
