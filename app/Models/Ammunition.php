<?php

namespace App\Models;

use App\Observers\AmmunitionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Http\Resources\Ammunition\AmmunitionResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
/** Trait para invocar la funcion getActivitylogOptions */
use App\Traits\Activitylog;



#[ObservedBy([AmmunitionObserver::class])]
class Ammunition extends Model
{
    /** @use HasFactory<\Database\Factories\AmmunitionFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = AmmunitionResource::class;

    protected $fillable = [
        'description',
        'logo',
    ];

    /**
     * Invocando funcion RecordLog desde
     * el trait Activitylog dentro de getActivitylogOptions
     * la cual tiene que forzosamente ser implementada en el modelo
     */
    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['description','logo'],'ammunition');
    }

    public function ammunitionConfiscations(){
        return $this->hasMany('App\Models\AmmunitionConfiscation');
    }
}
