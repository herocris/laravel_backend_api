<?php

namespace App\Models;

use App\Http\Resources\WeaponConfiscation\WeaponConfiscationResource;
use App\Observers\WeaponConfiscationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Trait para invocar la funcion getActivitylogOptions */

use App\Traits\Activitylog;

#[ObservedBy([WeaponConfiscationObserver::class])]
class WeaponConfiscation extends Model
{
    /** @use HasFactory<\Database\Factories\WeaponConfiscationFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = WeaponConfiscationResource::class;

    protected $fillable = [
        'amount',
        'confiscation_id',
        'weapon_id',
        'photo'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['amount','confiscation_id','weapon_id','photo'], 'weaponConfiscation');
    }

    public function confiscation()
    {
        return $this->belongsTo('App\Models\Confiscation')->withTrashed();
    }

    public function weapon()
    {
        return $this->belongsTo('App\Models\Weapon')->withTrashed();
    }
}
