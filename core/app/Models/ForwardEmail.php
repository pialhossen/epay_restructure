<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForwardEmail extends Model
{
    protected $guarded = ['id', 'created_at','updated_at'];
    protected $casts = [
        'created_at' => 'datetime',
    ];

}
