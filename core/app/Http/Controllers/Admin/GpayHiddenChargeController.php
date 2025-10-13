<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\GpayHiddenChargeModel;
use Illuminate\Http\Request;

class GpayHiddenChargeController extends Controller
{
    private $user;
    public function __construct()
    {
        $this->user = auth()->guard('admin')->user();
        $this->check_permission("View - Currency");
    }
    public static function checkPermission($user, $scope){
        if($user->id == 1){
            return true;
        }
        if($scope == 'index' && $user->can('View - Hidden Charges')){
            return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        $this->checkPermission($this->user, 'index');
        $pageTitle = 'Hidden Charges';

        $query = GpayHiddenChargeModel::query()->with('currency');

        if ($request->currency_id) {
            $query->where('currency_id', $request->currency_id);
        }

        $hiddenCharges = $query->latest()->paginate(getPaginate($request->itemsPerPage? $request->itemsPerPage: null));
        $currencies = Currency::select('id', 'name', 'cur_sym')->get();

        return view('admin.hidden-charge.index', compact('pageTitle', 'hiddenCharges', 'currencies'));
    }


    public function create()
    {
        $pageTitle = 'Add Hidden Charge';
        $currencies = Currency::select('id', 'name', 'cur_sym')->get();
        return view('admin.hidden-charge.form', compact('pageTitle', 'currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'charge_percent' => 'nullable|numeric',
            'charge_fixed' => 'nullable|numeric',
        ]);

        GpayHiddenChargeModel::create([
            ...$request->only(['currency_id', 'title', 'description', 'charge_percent', 'charge_fixed']),
            'created_by' => auth()->id(),
        ]);

        if ($request->has('continue')) {
            return redirect()
                ->route('admin.hidden.charge.create', ['currency_id' => $request->currency_id])
                ->with('success', 'Charge created successfully. You can create another one.');
        }

        return redirect()
            ->route('admin.hidden.charge.index', ['currency_id' => $request->currency_id])
            ->with('success', 'Charge created successfully.');

    }


    public function edit($id)
    {
        $pageTitle = 'Edit Hidden Charge';
        $charge = GpayHiddenChargeModel::findOrFail($id);
        $currencies = Currency::select('id', 'name', 'cur_sym')->get();

        return view('admin.hidden-charge.form', compact('pageTitle', 'charge', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        $chargeRecord = GpayHiddenChargeModel::findOrFail($id);
        $oldCharge = $chargeRecord->charge;

        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'charge_percent' => 'nullable|numeric',
            'charge_fixed' => 'nullable|numeric',

        ]);

        $chargeRecord->update([
            ...$request->only(['currency_id', 'title', 'description', 'charge_percent', 'charge_fixed']),
            'updated_by' => auth()->id(),
            'updated_date' => now(),
        ]);

        return redirect()->route('admin.hidden.charge.index', ['currency_id' => $request->currency_id])->with('success', 'Charge updated successfully.');
    }


    public function toggleStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:gpay_hidden_charge,id']);
        $item = GpayHiddenChargeModel::findOrFail($request->id);
        $item->status = !$item->status;
        $item->save();

        return response()->json(['success' => true, 'status' => $item->status]);
    }

    public function delete($id)
    {
        $charge = GpayHiddenChargeModel::findOrFail($id);
        $charge->delete();

        return back()->with('success', 'Deleted successfully.');
    }

}
