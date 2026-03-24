<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GsProfile extends Model
{
    protected $table = 'gs_profile';
    protected $primaryKey = 'imei';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'imei',
        'type',
        'brand',
        'model',
        'vin',
        'year',
        'color',
        'ex_day',
        'stop'
    ];
}
