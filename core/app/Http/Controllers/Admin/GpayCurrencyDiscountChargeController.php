<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\GpayCurrencyDiscountChargeModel;
use Illuminate\Http\Request;

class GpayCurrencyDiscountChargeController extends Controller
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
        if($scope == 'index' && $user->can('View - Discount/Charge')){
            return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        $pageTitle = 'Currency Discount Charges';
        $query = GpayCurrencyDiscountChargeModel::with('currency');

        if ($request->currency_id) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->rules_for) {
            $query->where('rules_for', $request->rules_for);
        }

        $charges = $query->latest()->paginate(getPaginate($request->itemsPerPage? $request->itemsPerPage: null));
        $currencies = Currency::select('id', 'name', 'cur_sym')->get();

        return view('admin.discount-charge.index', compact('pageTitle', 'charges', 'currencies'));
    }

    public function create()
    {
        $pageTitle = 'Add Discount Charge';
        $currencies = Currency::select('id', 'name', 'cur_sym')->get();
        return view('admin.discount-charge.form', compact('pageTitle', 'currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'charge_percent' => 'nullable|numeric',
            'charge_fixed' => 'nullable|numeric',
            'from' => 'required|string',
            'to' => 'required|string',
            'rules_for' => 'required|string',
            'apply_for' => 'required|array',
        ]);

        GpayCurrencyDiscountChargeModel::create([
            ...$request->only(['currency_id', 'title', 'description', 'charge_percent', 'charge_fixed', 'rules_for', 'apply_for', 'from', 'to']),
            'apply_for' => json_encode($request->apply_for),
            'created_by' => auth()->id(),
        ]);

        if ($request->has('continue')) {
            return redirect()
                ->route('admin.discount.charge.create', ['currency_id' => $request->currency_id])
                ->with('success', 'Charge created successfully. You can create another one.');
        }

        return redirect()->route('admin.discount.charge.index', ['currency_id' => $request->currency_id])->with('success', 'Discount charge created successfully.');
    }

    public function edit($id)
    {
        $pageTitle = 'Edit Discount Charge';
        $charge = GpayCurrencyDiscountChargeModel::findOrFail($id);
        $charge->apply_for_array = $charge->apply_for? json_decode($charge->apply_for,true) : [];
        $currencies = Currency::select('id', 'name', 'cur_sym')->get();

        return view('admin.discount-charge.form', compact('pageTitle', 'charge', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        $charge = GpayCurrencyDiscountChargeModel::findOrFail($id);

        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'charge_percent' => 'nullable|numeric',
            'charge_fixed' => 'nullable|numeric',
            'from' => 'required|string',
            'to' => 'required|string',
            'rules_for' => 'required|string',
            'apply_for' => 'required|array',
        ]);

        $charge->update([
            ...$request->only(['currency_id', 'title', 'description', 'charge_percent', 'charge_fixed', 'rules_for', 'from', 'to']),
            'apply_for' => json_encode($request->apply_for),
            'updated_by' => auth()->id(),
            'updated_date' => now(),
        ]);

        return redirect()->route('admin.discount.charge.index', ['currency_id' => $request->currency_id])->with('success', 'Discount charge updated successfully.');
    }

    public function toggleStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:gpay_currency_discount_charges,id']);
        $charge = GpayCurrencyDiscountChargeModel::findOrFail($request->id);
        $charge->status = !$charge->status;
        $charge->save();

        return response()->json(['success' => true, 'status' => $charge->status]);
    }

    public function delete($id)
    {
        $charge = GpayCurrencyDiscountChargeModel::findOrFail($id);
        $charge->delete();

        return back()->with('success', 'Discount charge deleted successfully.');
    }
}
