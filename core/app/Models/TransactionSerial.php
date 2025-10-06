<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSerial extends Model
{
    protected $fillable = [
        'transaction_type',
        'serial_no'
    ];
}
