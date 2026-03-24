<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedReport extends Model
{
    protected $table = 'gs_user_reports_generated';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'dt_report', 'name', 'type', 'format',
        'objects', 'markers', 'zones', 'sensors', 'schedule',
        'filename', 'report_file'
    ];

    protected $casts = [
        'dt_report' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
