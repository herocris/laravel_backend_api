<?php

namespace App\Observers;

use App\Models\Drug;
use Illuminate\Support\Facades\Storage;

class DrugObserver
{
    /**
     * Handle the Drug "created" event.
     */
    public function creating(Drug $drug): void
    {//dd(request()->all());
        if (request()->hasFile('logo')) {
            $drug->logo = request()->file('logo')->store('drug', 'public');
        }
    }

    /**
     * Handle the Drug "updated" event.
     */
    public function updating(Drug $drug): void
    {
        if (request()->hasFile('logo')) {
            if ($drug->getOriginal('logo')) {
                Storage::disk('public')->delete($drug->getOriginal('logo'));
            }
            $drug->logo = request()->file('logo')->store('drug', 'public');
        }
    }

    /**
     * Handle the Drug "deleted" event.
     */
    public function deleted(Drug $drug): void
    {
        //
    }

    /**
     * Handle the Drug "restored" event.
     */
    public function restored(Drug $drug): void
    {
        //
    }

    /**
     * Handle the Drug "force deleted" event.
     */
    public function forceDeleted(Drug $drug): void
    {
        //
    }
}
