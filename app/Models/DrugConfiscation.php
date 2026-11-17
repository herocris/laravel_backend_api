<?php

namespace App\Models;

use App\Traits\Activitylog;
use Database\Factories\DrugConfiscationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/** Trait para invocar la funcion getActivitylogOptions */

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class DrugConfiscation extends Model
{
    /** @use HasFactory<DrugConfiscationFactory> */
    use Activitylog, HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'weight',
        'photo',
        'confiscation_id',
        'drug_id',
        'drug_presentation_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'weight' => 'float',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog([
            'amount',
            'weight',
            'photo',
            'confiscation_id',
            'drug_id',
            'drug_presentation_id',
        ], 'drugConfiscation');
    }

    public function confiscation()
    {
        return $this->belongsTo('App\Models\Confiscation')->withTrashed();
    }

    public function drug()
    {
        return $this->belongsTo('App\Models\Drug')->withTrashed();
    }

    public function drugPresentation()
    {
        return $this->belongsTo('App\Models\DrugPresentation')->withTrashed();
    }
}
