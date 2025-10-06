<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReview extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'content',
        'rating',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
