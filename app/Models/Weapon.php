<?php

namespace App\Models;

use App\Traits\Activitylog;
use Database\Factories\ArmaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/** Trait para invocar la funcion getActivitylogOptions */
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class Weapon extends Model
{
    /** @use HasFactory<ArmaFactory> */
    use Activitylog, HasFactory, SoftDeletes;

    protected $fillable = [
        'description',
        'logo',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['description', 'logo'], 'weapon');
    }

    public function weaponConfiscations()
    {
        return $this->hasMany('App\Models\WeaponConfiscation');
    }
}
