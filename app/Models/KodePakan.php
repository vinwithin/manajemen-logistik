<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodePakan extends Model
{
    protected $table = 'kode_pakan';

    protected $fillable = ['nama', 'kode'];
}
