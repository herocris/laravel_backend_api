<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class LogActivityController extends ApiController
{
    public function index()
    {
        $activities=Activity::all();
        return $this->showAll($activities);
    }
}
