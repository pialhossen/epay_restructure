<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpayCurrencyManagerModel extends Model
{
    //
    protected $table = 'gpay_currency_manager';

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    public function currencyFrom()
    {
        return $this->belongsTo(Currency::class, 'currency_form');
    }

    public function currencyTo()
    {
        return $this->belongsTo(Currency::class, 'currency_to');
    }

    protected $fillable = ['currency_form', 'currency_to', 'status'];

}
