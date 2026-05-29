<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTujuan extends Model
{
    protected $table = 'user_tujuan';
    protected $fillable = [
        'user_id',
        'tujuan_id',
        'role',
    ];

    public function tujuan(){
        return $this->belongsTo(Tujuan::class, 'tujuan_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
