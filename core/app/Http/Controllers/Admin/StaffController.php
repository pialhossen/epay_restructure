<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use Hash;
use Illuminate\Http\Request;
use App\Models\AdminUserModel;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Controller;

class StaffController extends Controller
{
    public function index(){
        $pageTitle = 'All Staff';
        $staffs = AdminUserModel::where('id', '!=','1')->get();
        return view('admin.staffs.list', compact('pageTitle','staffs'));
    }
    public function create(){
        $pageTitle = 'Staff - Create';
        $roles = Role::all();
        $currentRolesId = [];
        return view('admin.staffs.form', compact('pageTitle', 'roles','currentRolesId'));
    }
    public function edit(AdminUserModel $user){
        $pageTitle = 'Staff - Edit';
        $roles = Role::all();
        $currentRolesId = $user->roles->pluck('id')->toArray();
        return view('admin.staffs.form', compact('pageTitle', 'user', 'roles','currentRolesId'));
    }
    public function store(Request $request, AdminUserModel $user = null)
    {
        $routeName = $request->route()->getName();
        $validation_rules  = [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'username' => [
                'required',
                'string',
                'min:6',
                'regex:/^[a-z0-9]+$/',
                'unique:admin_users,username',
            ],
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ];
        if($routeName != "admin.employee.staffs.update"){
            $validation_rules['username'][] = 'unique:admin_users,username';
        }
        $request->validate($validation_rules);
        $user_creating = false;
        if(!$user){
            $user = new AdminUserModel();
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
        $notify[] = ['success', 'Staff updated successfully'];

        if($user_creating){
            return redirect()->route('admin.employee.staffs.edit', $user->id)->withNotify($notify);
        } else {
            return back()->withNotify($notify);
        }
    }
    public function password(Request $request, AdminUserModel $user){
        $request->validate([
            'password' => 'required|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();
        $notify[] = ['success', 'Staff password updated successfully'];

        return back()->withNotify($notify);
    }
}
