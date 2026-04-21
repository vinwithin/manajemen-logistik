<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCv extends Model
{
    protected $table = 'user_cv';
    protected $fillable = [
        'user_id',
        'cv_id',
        'role',
    ];

    public function cv(){
        return $this->belongsTo(Cv::class, 'cv_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
