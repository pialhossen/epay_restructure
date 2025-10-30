<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceStatement extends Model
{
    protected $guarded = ['id','created_at','updated_at'];
    protected $casts = [
        'created_at' => 'datetime',
    ];
    public function admin(){
        return $this->belongsTo(Admin::class);
    }
}
