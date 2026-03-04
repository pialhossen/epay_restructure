<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function parent(){
        return $this->belongsTo(Permission::class, 'parent_id', 'id');
    }
    public function childs(){
        return $this->hasMany(Permission::class, 'parent_id', 'id');
    }
}
