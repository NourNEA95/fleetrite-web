<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GsUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'gs_users';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'email',
        'active'
    ];

    protected $hidden = [
        'password',
    ];

    public function userObjects()
    {
        return $this->hasMany(GsUserObject::class, 'user_id', 'id');
    }
}
