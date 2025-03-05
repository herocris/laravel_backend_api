<?php

namespace App\Observers;

use App\Models\DrugPresentation;
use Illuminate\Support\Facades\Storage;

class DrugPresentationObserver
{
    /**
     * Handle the DrugPresentation "created" event.
     */
    public function creating(DrugPresentation $drugPresentation): void
    {
        if (request()->hasFile('logo')) {
            $drugPresentation->logo = request()->file('logo')->store('drugPresentation', 'public');
        }
    }

    /**
     * Handle the DrugPresentation "updated" event.
     */
    public function updating(DrugPresentation $drugPresentation): void
    {
        if (request()->hasFile('logo')) {
            if ($drugPresentation->getOriginal('logo')) {
                Storage::disk('public')->delete($drugPresentation->getOriginal('logo'));
            }
            $drugPresentation->logo = request()->file('logo')->store('drugPresentation', 'public');
        }
    }

    /**
     * Handle the DrugPresentation "deleted" event.
     */
    public function deleted(DrugPresentation $drugPresentation): void
    {
        //
    }

    /**
     * Handle the DrugPresentation "restored" event.
     */
    public function restored(DrugPresentation $drugPresentation): void
    {
        //
    }

    /**
     * Handle the DrugPresentation "force deleted" event.
     */
    public function forceDeleted(DrugPresentation $drugPresentation): void
    {
        //
    }
}
