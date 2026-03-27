<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModularReportSession extends Model
{
    protected $table = 'gs_modular_reports_sessions';
    public $timestamps = false;

    protected $fillable = [
        'hash_id',
        'user_id',
        'report_type',
        'report_keys',
        'status',
        'dt_created'
    ];

    protected $casts = [
        'dt_created' => 'datetime',
    ];
}
