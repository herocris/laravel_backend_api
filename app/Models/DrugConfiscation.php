<?php

namespace App\Models;

use App\Http\Resources\DrugConfiscation\DrugConfiscationResource;
use App\Observers\DrugConfiscationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Trait para invocar la funcion getActivitylogOptions */

use App\Traits\Activitylog;

#[ObservedBy([DrugConfiscationObserver::class])]
class DrugConfiscation extends Model
{
    /** @use HasFactory<\Database\Factories\DrugConfiscationFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = DrugConfiscationResource::class;

    protected $fillable = [
        'amount',
        'weight',
        'photo',
        'confiscation_id',
        'drug_id',
        'drug_presentation_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog([
            'amount',
            'weight',
            'photo',
            'confiscation_id',
            'drug_id',
            'drug_presentation_id'
        ], 'drugConfiscation');
    }

    public function confiscation()
    {
        return $this->belongsTo('App\Models\Confiscation')->withTrashed();
    }

    public function drug()
    {
        return $this->belongsTo('App\Models\Drug')->withTrashed();
    }

    public function drugPresentation()
    {
        return $this->belongsTo('App\Models\DrugPresentation')->withTrashed();
    }
}
