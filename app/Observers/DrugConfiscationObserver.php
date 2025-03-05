<?php

namespace App\Observers;

use App\Models\DrugConfiscation;
use Illuminate\Support\Facades\Storage;

class DrugConfiscationObserver
{
    /**
     * Handle the DrugConfiscation "created" event.
     */
    public function creating(DrugConfiscation $drugConfiscation): void
    {
        if (request()->hasFile('foto')) {
            $drugConfiscation->photo = request()->file('foto')->store('drugConfiscation', 'public');
        }
    }

    /**
     * Handle the DrugConfiscation "updated" event.
     */
    public function updating(DrugConfiscation $drugConfiscation): void
    {
        if (request()->hasFile('foto')) {
            if ($drugConfiscation->getOriginal('photo')) {
                Storage::disk('public')->delete($drugConfiscation->getOriginal('photo'));
            }
            $drugConfiscation->photo = request()->file('foto')->store('drugConfiscation', 'public');
        }
    }

    /**
     * Handle the DrugConfiscation "deleted" event.
     */
    public function deleted(DrugConfiscation $drugConfiscation): void
    {
        //
    }

    /**
     * Handle the DrugConfiscation "restored" event.
     */
    public function restored(DrugConfiscation $drugConfiscation): void
    {
        //
    }

    /**
     * Handle the DrugConfiscation "force deleted" event.
     */
    public function forceDeleted(DrugConfiscation $drugConfiscation): void
    {
        //
    }
}
