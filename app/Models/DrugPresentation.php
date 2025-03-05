<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\DrugPresentationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Http\Resources\DrugPresentation\DrugPresentationResource;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
/** Trait para invocar la funcion getActivitylogOptions */
use App\Traits\Activitylog;

#[ObservedBy([DrugPresentationObserver::class])]
class DrugPresentation extends Model
{
    /** @use HasFactory<\Database\Factories\DrugPresentationFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = DrugPresentationResource::class;

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
        return $this->RecordLog(['description','logo'],'drugPresentation');
    }

    public function drugConfiscations(){
        return $this->hasMany('App\Models\DrugConfiscation');
    }
}
