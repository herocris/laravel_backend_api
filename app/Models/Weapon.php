<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\WeaponObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Http\Resources\Weapon\WeaponResource;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
/** Trait para invocar la funcion getActivitylogOptions */
use App\Traits\Activitylog;

#[ObservedBy([WeaponObserver::class])]
class Weapon extends Model
{
    /** @use HasFactory<\Database\Factories\ArmaFactory> */
    use HasFactory, SoftDeletes, Activitylog;
    public $resource = WeaponResource::class;

    protected $fillable = [
        'description',
        'logo',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return $this->RecordLog(['description','logo'],'weapon');
    }

    public function weaponConfiscations(){
        return $this->hasMany('App\Models\WeaponConfiscation');
    }
}
