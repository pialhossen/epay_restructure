<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpayCurrencyDiscountChargeModel extends Model
{
    //
    protected $table = 'gpay_currency_discount_charge';

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = [
        'currency_id',
        'rules_for',
        'apply_for',
        'title',
        'description',
        'charge_percent',
        'charge_fixed',
        'from',
        'to',
        'status',
        'created_by',
        'created_date',
        'updated_by',
        'updated_date'
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
    protected $casts = [

    ];
}
