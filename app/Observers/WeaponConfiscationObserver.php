<?php

namespace App\Observers;

use App\Models\WeaponConfiscation;
use Illuminate\Support\Facades\Storage;

class WeaponConfiscationObserver
{
    /**
     * Handle the WeaponConfiscation "created" event.
     */
    public function creating(WeaponConfiscation $weaponConfiscation): void
    {
        if (request()->hasFile('foto')) {
            $weaponConfiscation->photo = request()->file('foto')->store('weaponConfiscation', 'public');
        }
    }

    /**
     * Handle the WeaponConfiscation "updated" event.
     */
    public function updating(WeaponConfiscation $weaponConfiscation): void
    {
        if (request()->hasFile('foto')) {
            if ($weaponConfiscation->getOriginal('photo')) {
                Storage::disk('public')->delete($weaponConfiscation->getOriginal('photo'));
            }
            $weaponConfiscation->photo = request()->file('foto')->store('weaponConfiscation', 'public');
        }
    }

    /**
     * Handle the WeaponConfiscation "deleted" event.
     */
    public function deleted(WeaponConfiscation $weaponConfiscation): void
    {
        //
    }

    /**
     * Handle the WeaponConfiscation "restored" event.
     */
    public function restored(WeaponConfiscation $weaponConfiscation): void
    {
        //
    }

    /**
     * Handle the WeaponConfiscation "force deleted" event.
     */
    public function forceDeleted(WeaponConfiscation $weaponConfiscation): void
    {
        //
    }
}
