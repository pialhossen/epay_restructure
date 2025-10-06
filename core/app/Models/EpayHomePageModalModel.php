<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpayHomePageModalModel extends Model
{
    //
    protected $table = 'epay_home_page_modal';

    const CREATED_AT = 'cd';

    // const UPDATED_AT = 'ud';

    protected $fillable = [
        'title',
        'description',
        'button_name',
        'image',
        'status',
        // 'remarks',
        // 'cb',
        // 'ub',
    ];
}
