<?php
use App\Http\Controllers\Controller;
class ForwardEmailController
{
    public function index(Request $request)
    {
        $emails = ForwardEmail::paginate(getPaginate($request->itemsPerPage ? $request->itemsPerPage : null));
        dd($emails);
    }
}