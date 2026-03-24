<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GsObject extends Model
{
    protected $table = 'gs_objects';
    protected $primaryKey = 'imei';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'imei',
        'name',
        'icon',
        'map_icon',
        'map_arrows',
        'tail_color',
        'tail_points',
        'device',
        'sim_number',
        'model',
        'vin',
        'plate_number',
        'fcr',
        'accuracy',
        'unassign_driver',
        'accvirt',
        'accvirt_cn',
        'forward_loc_data',
        'forward_loc_data_imei',
        'odometer_type',
        'engine_hours_type',
        'odometer',
        'engine_hours',
        'active',
        'object_expire',
        'object_expire_dt'
    ];

    public function profile()
    {
        return $this->hasOne(GsProfile::class, 'imei', 'imei');
    }

    public function userObject()
    {
        return $this->hasOne(GsUserObject::class, 'imei', 'imei');
    }
}
