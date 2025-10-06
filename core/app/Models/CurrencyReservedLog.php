<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyReservedLog extends Model
{
    protected $fillable = ['currency_id', 'currency_name', 'reserved', 'business_day'];
}
