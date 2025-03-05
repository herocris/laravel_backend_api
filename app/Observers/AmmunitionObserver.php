<?php

namespace App\Observers;

use App\Models\Ammunition;
use Illuminate\Support\Facades\Storage;

class AmmunitionObserver
{
    /**
     * Handle the Ammunition "created" event.
     */
    public function creating(Ammunition $ammunition): void
    {
        if (request()->hasFile('logo')) {
            $ammunition->logo = request()->file('logo')->store('ammunition', 'public');
        }
    }

    /**
     * Handle the Ammunition "updated" event.
     */
    public function updating(Ammunition $ammunition): void
    {
        if (request()->hasFile('logo')) {
            if ($ammunition->getOriginal('logo')) {
                Storage::disk('public')->delete($ammunition->getOriginal('logo'));
            }
            $ammunition->logo = request()->file('logo')->store('ammunition', 'public');
        }
    }

    /**
     * Handle the Ammunition "deleted" event.
     */
    public function deleted(Ammunition $ammunition): void
    {
        if ($ammunition->logo) {
            Storage::disk('public')->delete($ammunition->logo);
        }
    }

    /**
     * Handle the Ammunition "restored" event.
     */
    public function restored(Ammunition $ammunition): void
    {
        //
    }

    /**
     * Handle the Ammunition "force deleted" event.
     */
    public function forceDeleted(Ammunition $ammunition): void
    {
        //
    }
}
