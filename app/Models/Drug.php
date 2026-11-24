<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Database\Factories\DrogaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/** Trait para invocar la funcion getActivitylogOptions */
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class Drug extends Model
{
    /** @use HasFactory<DrogaFactory> */
    use HasFactory, SoftDeletes, TracksActivity;

    protected $fillable = [
        'description',
        'logo',
    ];

    /**
     * Invocando funcion RecordLog desde
     * el trait TracksActivity dentro de getActivitylogOptions
     * la cual tiene que forzosamente ser implementada en el modelo
     */
    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['description', 'logo'], 'drug');
    }

    public function drugConfiscations()
    {
        return $this->hasMany('App\Models\DrugConfiscation');
    }
}
