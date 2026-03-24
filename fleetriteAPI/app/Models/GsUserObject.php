<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GsUserObject extends Model
{
    protected $table = 'gs_user_objects';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'imei',
        'group_id',
        'driver_id',
        'trailer_id'
    ];

    public function gsObject()
    {
        return $this->belongsTo(GsObject::class, 'imei', 'imei');
    }
}
