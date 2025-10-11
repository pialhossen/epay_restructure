<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\GpayCurrencyManagerModel;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GpayCurrencyManagerController extends Controller
{
    private $user;
    public function __construct()
    {
        $this->user = auth()->guard('admin')->user();
        if($this->user->cannot("View - Currency") && $this->user->id != 1){
            abort(403);
        }
    }
    public static function checkPermission($user, $scope){
        if($user->id == 1){
            return true;
        }
        if($scope == 'index' && $user->can('View - Currency Exchange')){
            return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        $this->checkPermission($this->user, 'index');

        $pageTitle = 'Currency Exchange';

        $query = GpayCurrencyManagerModel::query();

        // Apply filters
        if ($request->currency_form) {
            $query->where('currency_form', $request->currency_form);
        }
        if ($request->currency_to) {
            $query->where('currency_to', $request->currency_to);
        }

        $users = $query->with(['currencyFrom', 'currencyTo'])->latest()->paginate(getPaginate(getPaginate($request->itemsPerPage? $request->itemsPerPage: null )));
        $currencies = Currency::select('id', 'name', 'cur_sym')->get();

        return view('admin.currency-manager.index', compact('pageTitle', 'users', 'currencies'));
    }

    public function toggleStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:gpay_currency_manager,id',
            'status' => 'required|boolean'
        ]);

        $currency = GpayCurrencyManagerModel::findOrFail($request->id);
        $currency->status = $request->status;
        $currency->save();

        return response()->json(['success' => true]);
    }

}
