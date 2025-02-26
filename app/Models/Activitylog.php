<?php

namespace App\Models;

use App\Http\Resources\Admin\ActivityLog\ActivityLogResource;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity as SpatieActivitylog;

class Activitylog extends SpatieActivitylog
{
    public $resource = ActivityLogResource::class;
}
