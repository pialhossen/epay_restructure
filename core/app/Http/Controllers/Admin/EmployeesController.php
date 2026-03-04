<?php

namespace App\Http\Controllers\Admin;

use Hash;
use App\Models\Role;
use App\Models\Admin;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class EmployeesController extends Controller
{
    public function __construct()
    {
        $user = auth()->guard('admin')->user();
        $this->check_permission("View - Manage Employees");
    }
    public static function checkPermission($user, $scope){
        if($scope == 'Manage Employees' && $user->can("View - Employees List")){
            return true;
        }
        if($scope == 'Manage Roles' && $user->can("View - Manage Roles")){
            return true;
        }
        if($scope == 'Manage Permissions' && $user->can("View - Manage Permissions")){
            return true;
        }
    }
    public function index(){
        $this->check_permission('View - Employees List');
        $pageTitle = 'All Employee';
        $employees = Admin::where('id', '!=','1')->paginate(getPaginate());
        return view('admin.employees.list', compact('pageTitle','employees'));
    }
    public function create(){
        $this->check_permission('Create - Employees');
        $pageTitle = 'Employee - Create';
        $roles = Role::all();
        $currentRolesId = [];
        return view('admin.employees.form', compact('pageTitle', 'roles','currentRolesId'));
    }
    public function edit(Admin $user){
        $this->check_permission('Update - Employees');
        $pageTitle = 'Employee - Edit';
        $roles = Role::all();
        $currentRolesId = $user->roles->pluck('id')->toArray();
        return view('admin.employees.form', compact('pageTitle', 'user', 'roles','currentRolesId'));
    }
    public function store(Request $request, Admin $user = null)
    {
        if($user){
            $this->check_permission('Update - Employees');
        } else {
            $this->check_permission('Create - Employees');
        }
        $routeName = $request->route()->getName();
        $validation_rules  = [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'username' => [
                'required',
                'string',
                'regex:/^[a-z0-9]+$/',
                Rule::unique('admins', 'username')->ignore($user->id ?? null)
            ],
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ];
        $request->validate($validation_rules);
        $user_creating = false;
        if(!$user){
            $user = new Admin();
            $user->password = Hash::make($request->password);
            $user_creating = true;
        }
        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('adminProfile'), getFileSize('adminProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];

                return back()->withNotify($notify);
            }
        }

        $role_ids = $request->roles;
        $roles = Role::whereIn('id', $role_ids)->get();
        $user->syncRoles($roles);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->is_active = $request->is_active == '1'? 1: 0;
        $user->save();
        
        if($user_creating){
            $notify[] = ['success', 'Employee created successfully'];
            if(checkSpecificPermission('Update - Employees')){
                return redirect()->route('admin.employees.edit', $user->id)->withNotify($notify);
            }
            return redirect()->route('admin.employees.index')->withNotify($notify);
        } else {
            $notify[] = ['success', 'Employee updated successfully'];
            return back()->withNotify($notify);
        }
    }
    public function delete(Admin $user){
        $this->check_permission('Delete - Employees');
        $user->delete();
        $notify[] = ['success', 'Employee updated successfully'];
        return redirect()->route('admin.employees.index')->withNotify($notify);
    }
    public function password(Request $request, Admin $user){
        $request->validate([
            'password' => 'required|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();
        $notify[] = ['success', 'Employee password updated successfully'];

        return back()->withNotify($notify);
    }
    
    public function permission_index(){
        $this->check_permission('View - Manage Permissions');
        $pageTitle = "All Permissions";
        $permissions = Permission::paginate(getPaginate());
        return view('admin.permissions.list', compact('permissions','pageTitle'));
    }
    public function permission_store(Request $request, Permission $permission = null){
        if($permission){
            $this->check_permission('Update - Permissions');
        } else {
            $this->check_permission('Create - Permissions');
        }
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
                $permission->guard_name = 'admin';;
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
    public function permission_delete(Permission $permission){
        $this->check_permission('Delete - Permissions');
        $permission->delete();
        $notify[] = ['success', 'Permission Delete'];
        return redirect()->back()->withNotify($notify); 
    }

    public function role_index(){
        $this->check_permission('View - Manage Roles');
        $pageTitle = 'All Roles';
        $roles = Role::all();
        return view('admin.roles.list', compact('pageTitle','roles'));
    }
    public function role_edit(Role $role){
        $this->check_permission('Update - Roles');
        $pageTitle = "Edit - $role->name - Role";
        $permissions = Permission::with('childs')->whereNull('parent_id')->get();
        return view('admin.roles.form', compact('pageTitle','role','permissions'));
    }
    public function role_store(Request $request){
        $this->check_permission('Create - Roles');
        $request->validate([
            "name" => "required"
        ]);
        $role = new Role();
        $role->name = $request->name;
        $role->guard_name = 'admin';
        $role->save();
        $notify[] = ['success', 'Role Created'];
        return redirect()->back()->withNotify($notify);    
    }
    public function role_update(Role $role,Request $request){
        $this->check_permission('Update - Roles');
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
    public function role_delete(Role $role){
        $this->check_permission('Delete - Roles');
        $role->delete();
        $notify[] = ['success', 'Role Deleted'];
        return redirect()->route('admin.employees.roles.index')->withNotify($notify);
    }
}