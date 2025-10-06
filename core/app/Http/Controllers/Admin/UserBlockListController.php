<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserBlockListModel;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserBlockListController extends Controller
{
    public function index()
    {
        $pageTitle = 'Blocked Users';
        $users = UserBlockListModel::latest()->paginate(getPaginate());

        return view('admin.block-user.index', compact('pageTitle', 'users'));
    }

    public function create()
    {
        $pageTitle = 'Block New User';

        return view('admin.block-user.form', compact('pageTitle'));
    }

    public function edit($id)
    {
        $pageTitle = 'Edit Blocked User';
        $user = UserBlockListModel::findOrFail($id);

        return view('admin.block-user.form', compact('pageTitle', 'user'));
    }

    public function save(Request $request, $id = 0)
    {
        $request->validate([
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        if ($id) {
            $user = UserBlockListModel::findOrFail($id);
            $message = 'Blocked user updated successfully';

            $user->updated_by = auth()->user()->id;
            $user->updated_date = Carbon::now();
        } else {
            $user = new UserBlockListModel;
            $message = 'User blocked successfully';

            $user->created_by = auth()->user()->id;
            $user->created_date = Carbon::now();
        }

        $user->phone_number = $request->phone_number;
        $user->email = $request->email;
        $user->save();

        $notify[] = ['success', $message];

        return redirect()->route('admin.block.user.index')->withNotify($notify);
    }

    public function delete($id)
    {
        $user = UserBlockListModel::findOrFail($id);
        $user->delete();

        $notify[] = ['success', 'Blocked user removed'];

        return back()->withNotify($notify);
    }
}
