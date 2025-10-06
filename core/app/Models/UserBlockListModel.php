<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBlockListModel extends Model
{
    protected $table = 'gpay_user_block_list';

    const CREATED_AT = 'created_date';

    const UPDATED_AT = 'updated_date';

    protected $fillable = ['phone_number', 'email', 'status', 'remarks', 'created_by', 'created_date', 'updated_by', 'updated_date'];
}
