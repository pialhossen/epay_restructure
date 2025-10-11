<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUserModel extends Authenticatable
{
    use HasRoles;
    protected $guard_name = 'admin';
    protected $table = 'admin_users';
}
