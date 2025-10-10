<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;

class AdminUserModel extends Model
{
    use HasRoles;
    protected $guard_name = 'admin';
    protected $table = 'admin_users';
}
