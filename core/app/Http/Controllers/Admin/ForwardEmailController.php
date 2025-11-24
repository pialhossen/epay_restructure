<?php

namespace App\Http\Controllers\Admin;

use App\Models\ForwardEmail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ForwardEmailController extends Controller
{
    public function __construct()
    {
        Auth::shouldUse('admin');
        $this->user = auth()->user();
        $this->check_permission("View - Exchange Emails");
    }
    public function check_update_permission()
    {
        if ($this->user->id == 1 || $this->user->can('Update - Exchange Emails')) {
            return 0;
        }
        abort(403);
    }
    public function check_view_permission()
    {
        if ($this->user->id == 1 || $this->user->can('View - Exchange Emails Details')) {
            return 0;
        }
        abort(403);
    }
    public function index(Request $request)
    {
        $pageTitle = "Exchange Emails";
        $emails_query = ForwardEmail::where('is_hidden',false);
        if($request->id){
            $emails_query = $emails_query->where('id', $request->id);
        }
        if($request->from){
            $emails_query = $emails_query->where('from','LIKE', "%$request->from%");
        }
        if($request->subject){
            $emails_query = $emails_query->where('subject','LIKE', "%$request->subject%");
        }
        if($request->body){
            $emails_query = $emails_query->where('body','LIKE', "%$request->body%");
        }
        if ($request->received_from && $request->received_to) {
            $emails_query = $emails_query->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($request->received_from)),
                date('Y-m-d 23:59:59', strtotime($request->received_to))
            ]);
        }

        if ($request->query('sort')) {
            [$column, $direction] = explode(':', request()->query('sort'));
            $emails_query = $emails_query->orderBy($column, $direction);
        } else {
            $emails_query = $emails_query->orderBy('created_at', 'desc');
        }
        $emails = $emails_query->with('checked_by_admin')->latest()->paginate(getPaginate($request->itemsPerPage ? $request->itemsPerPage : null));
        return view('admin.exchange_emails.list', compact('emails','request','pageTitle'));
    }
    public function hidden_index(Request $request){
        $this->check_hidden_view_permission();
        $pageTitle = "Hidden Exchange Emails";
        $emails_query = ForwardEmail::where('is_hidden',true);
        if($request->id){
            $emails_query = $emails_query->where('id', $request->id);
        }
        if($request->from){
            $emails_query = $emails_query->where('from','LIKE', "%$request->from%");
        }
        if($request->subject){
            $emails_query = $emails_query->where('subject','LIKE', "%$request->subject%");
        }
        if($request->body){
            $emails_query = $emails_query->where('body','LIKE', "%$request->body%");
        }
        if ($request->received_from && $request->received_to) {
            $emails_query = $emails_query->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($request->received_from)),
                date('Y-m-d 23:59:59', strtotime($request->received_to))
            ]);
        }

        if ($request->query('sort')) {
            [$column, $direction] = explode(':', request()->query('sort'));
            $emails_query = $emails_query->orderBy($column, $direction);
        } else {
            $emails_query = $emails_query->orderBy('created_at', 'desc');
        }
        $emails = $emails_query->with('checked_by_admin')->latest()->paginate(getPaginate($request->itemsPerPage ? $request->itemsPerPage : null));
        return view('admin.exchange_emails.hidden_list', compact('emails','request','pageTitle'));
    }
    public function show(ForwardEmail $email){
        $this->check_view_permission();
        $pageTitle = "Exchange Emails";
        return view('admin.exchange_emails.details', compact('email','pageTitle'));
    }
    public function check(ForwardEmail $email, Request $request){
        $this->check_update_permission();
        if($request->note){
            $email->note = $request->note;
        }
        $email->is_checked = true;
        $email->checked_by = auth('admin')->id();
        $email->save();
        $notify[] = ['success', 'Email Checked'];
        return back()->withNotify($notify);
    }
    public function uncheck(ForwardEmail $email){
        $this->check_update_permission();
        $email->is_checked = false;
        $email->note = null;
        $email->checked_by = null;
        $email->save();
        $notify[] = ['success', 'Email Unchecked'];
        return back()->withNotify($notify);
    }
}
