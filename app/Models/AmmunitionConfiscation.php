<?php

namespace App\Models;

use App\Http\Resources\AmmunitionConfiscation\AmmunitionConfiscationResource;
use App\Observers\AmmunitionConfiscationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Trait para invocar la funcion getActivitylogOptions */

use App\Traits\Activitylog;

#[ObservedBy([AmmunitionConfiscationObserver::class])]
class AmmunitionConfiscation extends Model
{
    /** @use HasFactory<\Database\Factories\AmmunitionConfiscationFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = AmmunitionConfiscationResource::class;

    protected $fillable = [
        'amount',
        'confiscation_id',
        'ammunition_id',
        'photo'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['amount','confiscation_id','ammunition_id','photo'], 'ammunitionConfiscation');
    }

    public function confiscation()
    {
        return $this->belongsTo('App\Models\Confiscation')->withTrashed();
    }

    public function ammunition()
    {
        return $this->belongsTo('App\Models\Ammunition')->withTrashed();
    }
}
