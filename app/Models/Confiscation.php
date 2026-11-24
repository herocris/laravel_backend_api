<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Database\Factories\DecomisoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class Confiscation extends Model
{
    /** @use HasFactory<DecomisoFactory> */
    use HasFactory, SoftDeletes, TracksActivity;

    protected $fillable = [
        'date',
        'observation',
        'direction',
        'department',
        'municipality',
        'latitude',
        'length',
    ];

    /**
     * Invocando funcion RecordLog desde
     * el trait TracksActivity dentro de getActivitylogOptions
     * la cual tiene que forzosamente ser implementada en el modelo
     */
    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['date', 'observation', 'direction', 'department', 'municipality', 'latitude', 'length'], 'confiscation');
    }

    public function ammunitionConfiscations()
    {
        return $this->hasMany('App\Models\AmmunitionConfiscation');
    }

    public function weaponConfiscations()
    {
        return $this->hasMany('App\Models\WeaponConfiscation');
    }

    public function drugConfiscations()
    {
        return $this->hasMany('App\Models\DrugConfiscation');
    }
}
