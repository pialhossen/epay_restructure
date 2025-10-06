<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_currency',
        'to_currency',
        'alert_rate',
        'email',
        'status',
    ];
}
