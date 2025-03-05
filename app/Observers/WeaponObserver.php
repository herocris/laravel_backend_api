<?php

namespace App\Observers;

use App\Models\Weapon;
use Illuminate\Support\Facades\Storage;

class WeaponObserver
{
    /**
     * Handle the Weapon "created" event.
     */
    public function creating(Weapon $weapon): void
    {
        if (request()->hasFile('logo')) {
            $weapon->logo = request()->file('logo')->store('weapon', 'public');
        }
    }

    /**
     * Handle the Weapon "updated" event.
     */
    public function updating(Weapon $weapon): void
    {
        if (request()->hasFile('logo')) {
            if ($weapon->getOriginal('logo')) {
                Storage::disk('public')->delete($weapon->getOriginal('logo'));
            }
            $weapon->logo = request()->file('logo')->store('weapon', 'public');
        }
    }

    /**
     * Handle the Weapon "deleted" event.
     */
    public function deleted(Weapon $weapon): void
    {
        //
    }

    /**
     * Handle the Weapon "restored" event.
     */
    public function restored(Weapon $weapon): void
    {
        //
    }

    /**
     * Handle the Weapon "force deleted" event.
     */
    public function forceDeleted(Weapon $weapon): void
    {
        //
    }
}
