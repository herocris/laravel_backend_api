<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\DrugObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Http\Resources\Drug\DrugResource;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
/** Trait para invocar la funcion getActivitylogOptions */
use App\Traits\Activitylog;

#[ObservedBy([DrugObserver::class])]
class Drug extends Model
{
    /** @use HasFactory<\Database\Factories\DrogaFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = DrugResource::class;

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
        return $this->RecordLog(['description','logo'],'drug');
    }

    public function drugConfiscations(){
        return $this->hasMany('App\Models\DrugConfiscation');
    }

}
