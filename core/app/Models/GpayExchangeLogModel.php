<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpayExchangeLogModel extends Model
{
    protected $table = 'gpay_exchange_log';

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    public function adminUser()
    {
        return $this->belongsTo(AdminUserModel::class, 'updated_by');
    }
}
