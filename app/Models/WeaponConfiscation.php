<?php

namespace App\Models;

use App\Traits\Activitylog;
use Database\Factories\WeaponConfiscationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/** Trait para invocar la funcion getActivitylogOptions */

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class WeaponConfiscation extends Model
{
    /** @use HasFactory<WeaponConfiscationFactory> */
    use Activitylog, HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'confiscation_id',
        'weapon_id',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['amount', 'confiscation_id', 'weapon_id', 'photo'], 'weaponConfiscation');
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
