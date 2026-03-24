<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GsObjectService extends Model
{
    protected $table = 'gs_object_services';
    protected $primaryKey = 'service_id';
    public $timestamps = false;

    protected $fillable = [
        'imei',
        'name',
        'data_list',
        'popup',
        'odo',
        'odo_interval',
        'odo_last',
        'engh',
        'engh_interval',
        'engh_last',
        'days',
        'days_interval',
        'days_last'
    ];
}
