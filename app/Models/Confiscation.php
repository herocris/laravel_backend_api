<?php

namespace App\Models;

use App\Traits\Activitylog;
use Database\Factories\DecomisoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class Confiscation extends Model
{
    /** @use HasFactory<DecomisoFactory> */
    use Activitylog, HasFactory, SoftDeletes;

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
     * el trait Activitylog dentro de getActivitylogOptions
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
