<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Activity as SpatieActivitylog;

class Activitylog extends SpatieActivitylog
{
    use HasFactory;
}
