<?php

namespace App\Http\Controllers\Admin;

use App\Models\ForwardEmail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ForwardEmailController extends Controller
{
    public function index(Request $request)
    {
        $emails = ForwardEmail::paginate(getPaginate($request->itemsPerPage ? $request->itemsPerPage : null));
        dd($emails);
    }
}
