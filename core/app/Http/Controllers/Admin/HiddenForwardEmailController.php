<?php

namespace App\Http\Controllers\Admin;

use App\Models\ForwardEmail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HiddenForwardEmailController extends Controller
{
    public function __construct()
    {
        Auth::shouldUse('admin');
        $this->user = auth()->user();
        $this->check_permission("View - Hidden Exchange Emails");
    }
    public function hidden_index(Request $request){
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
}
