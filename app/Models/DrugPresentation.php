<?php

namespace App\Models;

use App\Traits\Activitylog;
use Database\Factories\DrugPresentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/** Trait para invocar la funcion getActivitylogOptions */
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class DrugPresentation extends Model
{
    /** @use HasFactory<DrugPresentationFactory> */
    use Activitylog, HasFactory, SoftDeletes;

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
        return $this->RecordLog(['description', 'logo'], 'drugPresentation');
    }

    public function drugConfiscations()
    {
        return $this->hasMany('App\Models\DrugConfiscation');
    }
}
