<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GsLastEvent extends Model
{
    protected $table = 'gs_user_last_events_data';
    protected $primaryKey = 'event_id';
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'user_id',
        'type',
        'event_desc',
        'dt_server',
        'dt_tracker',
        'lat',
        'lng',
        'altitude',
        'angle',
        'speed',
        'params',
        'imei'
    ];
}
