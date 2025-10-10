<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permission;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function index(){
        $pageTitle = 'All Roles';
        $roles = Role::all();
        return view('admin.roles.list', compact('pageTitle','roles'));
    }
    public function edit(Role $role){
        $pageTitle = "Edit - $role->name - Role";
        $permissions = Permission::with('childs')->whereNull('parent_id')->get();
        return view('admin.roles.form', compact('pageTitle','role','permissions'));
    }
    public function store(Request $request){
        $request->validate([
            "name" => "required"
        ]);
        $role = new Role();
        $role->name = $request->name;
        $role->save();
        $notify[] = ['success', 'Role Created'];
        return redirect()->back()->withNotify($notify);    
    }
    public function update(Role $role,Request $request){
        $request->validate([
            "name" => "required"
        ]);
        $permission_ids = array_keys($request->permissions);
        $permissions = Permission::whereIn('id',$permission_ids)->get();
        $role->name = $request->name;
        $role->syncPermissions($permissions);
        $role->save();
        $notify[] = ['success', 'Role Updated'];
        return redirect()->back()->withNotify($notify);
    }
    public function delete(Role $role){
        $role->delete();
        $notify[] = ['success', 'Role Deleted'];
        return redirect()->route('admin.employee.roles.index')->withNotify($notify);
    }
}
