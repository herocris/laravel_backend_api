<?php

namespace App\Models;

use App\Http\Resources\Admin\ActivityLog\ActivityLogResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity as SpatieActivitylog;
use OpenApi\Attributes as OA;

class Activitylog extends SpatieActivitylog
{
    use HasFactory;
    public $resource = ActivityLogResource::class;
}
