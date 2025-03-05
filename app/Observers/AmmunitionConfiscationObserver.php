<?php

namespace App\Observers;

use App\Models\AmmunitionConfiscation;
use Illuminate\Support\Facades\Storage;

class AmmunitionConfiscationObserver
{
    /**
     * Handle the AmmunitionConfiscation "created" event.
     */
    public function creating(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        if (request()->hasFile('foto')) {
            $ammunitionConfiscation->photo = request()->file('foto')->store('ammunitionConfiscation', 'public');
        }
    }

    /**
     * Handle the AmmunitionConfiscation "updated" event.
     */
    public function updating(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        if (request()->hasFile('foto')) {
            if ($ammunitionConfiscation->getOriginal('photo')) {
                Storage::disk('public')->delete($ammunitionConfiscation->getOriginal('photo'));
            }
            $ammunitionConfiscation->photo = request()->file('foto')->store('ammunitionConfiscation', 'public');
        }
    }

    /**
     * Handle the AmmunitionConfiscation "deleted" event.
     */
    public function deleted(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        //
    }

    /**
     * Handle the AmmunitionConfiscation "restored" event.
     */
    public function restored(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        //
    }

    /**
     * Handle the AmmunitionConfiscation "force deleted" event.
     */
    public function forceDeleted(AmmunitionConfiscation $ammunitionConfiscation): void
    {
        //
    }
}
