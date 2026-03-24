<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'gs_user_reports';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'name', 'name2', 'name3', 'type', 'ignore_empty_reports', 'format',
        'show_coordinates', 'show_addresses', 'markers_addresses', 'zones_addresses',
        'stop_duration', 'speed_limit', 'imei', 'marker_ids', 'zone_ids', 'driver_ids',
        'sensor_names', 'data_items', 'other', 'schedule_period', 'schedule_email_address'
    ];

    protected $casts = [
        'ignore_empty_reports' => 'string',
        'show_coordinates' => 'string',
        'show_addresses' => 'string',
        'markers_addresses' => 'string',
        'zones_addresses' => 'string',
        'other' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
