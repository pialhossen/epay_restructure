<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PermissionController extends Controller
{
    public function index(){
        $pageTitle = "All Permissions";
        $permissions = Permission::paginate(getPaginate());
        return view('admin.permissions.list', compact('permissions','pageTitle'));
    }
    public function store(Request $request, Permission $permission = null){
        $request->validate([
            "name" => "required",
            "parent_id" => ["nullable","sometimes","exists:permissions,id"]
        ]);
        try {
            $edit_permission = false;
            if($permission){
                $edit_permission = true;
            } else {
                $permission = new Permission();
            }
            $permission->name = $request->name;
            $permission->parent_id = $request->parent_id;
            $permission->save();
            if($edit_permission){
                $notify[] = ['success', 'Permission Updated'];
            } else {
                $notify[] = ['success', 'Permission Created'];
            }
            return redirect()->back()->withNotify($notify); 
        } catch (\Throwable $th) {
            $notify[] = ['error', 'Error '. $th->getMessage()];
            return redirect()->back()->withNotify($notify); 
        }
    }
    public function delete (Permission $permission){
        $permission->delete();
        $notify[] = ['success', 'Permission Delete'];
        return redirect()->back()->withNotify($notify); 
    }
}
