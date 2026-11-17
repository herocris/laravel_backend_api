<?php

namespace App\Models;

use App\Traits\Activitylog;
use Database\Factories\AmmunitionConfiscationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/** Trait para invocar la funcion getActivitylogOptions */

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class AmmunitionConfiscation extends Model
{
    /** @use HasFactory<AmmunitionConfiscationFactory> */
    use Activitylog, HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'confiscation_id',
        'ammunition_id',
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
        return $this->RecordLog(['amount', 'confiscation_id', 'ammunition_id', 'photo'], 'ammunitionConfiscation');
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
