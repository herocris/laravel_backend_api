<?php

namespace App\Http\Controllers;

use App\Models\Activitylog;
use Illuminate\Http\Request;


class LogActivityController extends ApiController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $activities = Activitylog::all();
        return $this->showAll($activities);
    }
}
