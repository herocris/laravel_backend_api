<?php

namespace App\Models;

use App\Http\Resources\Confiscation\ConfiscationResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Activitylog;

class Confiscation extends Model
{
    /** @use HasFactory<\Database\Factories\DecomisoFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = ConfiscationResource::class;

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
        return $this->RecordLog(['date','observation','direction','department','municipality','latitude','length'],'confiscation');
    }

    public function ammunitionConfiscations(){
        return $this->hasMany('App\Models\AmmunitionConfiscation');
    }

    public function weaponConfiscations(){
        return $this->hasMany('App\Models\WeaponConfiscation');
    }

    public function drugConfiscations(){
        return $this->hasMany('App\Models\DrugConfiscation');
    }
}
