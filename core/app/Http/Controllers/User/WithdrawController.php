<?php

namespace App\Http\Controllers\User;

use App\Models\Currency;
use App\Models\Exchange;
use App\Constants\Status;
use App\Lib\FormProcessor;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use App\Models\BalanceStatement;
use App\Traits\TransactionTrait;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\GpayHiddenChargeModel;
use App\Models\GpayCurrencyDiscountChargeModel;

class WithdrawController extends Controller
{
    use TransactionTrait;

    public function withdrawMoney()
    {
        $pageTitle = 'Withdraw Money';
        $user = auth()->user();
        $account_currency = Currency::where('name','A/C BALANCE')->first();

        $currencies = Currency::enabled()
                    ->where([
                        'available_for_buy' => Status::YES,
                        'available_for_withdraw' => Status::YES
                    ])
                    ->with('userDetailsData')
                    ->get();

        $charges = GpayCurrencyDiscountChargeModel::select('charge_fixed','charge_percent','currency_id','description','from','id','rules_for','title','to')->where('rules_for','buy')->whereJsonContains('apply_for','withdraw')->get();
        
        foreach ($charges as $charge) {
            $charge->charge_fixed = (float) $charge->charge_fixed;
            $charge->charge_percent = (float) $charge->charge_percent;
            $charge->from = (float) $charge->from;
            $charge->to = (float) $charge->to;
        }

        $ac_charges = GpayCurrencyDiscountChargeModel::select('charge_fixed','charge_percent','currency_id','description','from','id','rules_for','title','to')->where('rules_for','sell')->where('currency_id', $account_currency->id)->whereJsonContains('apply_for','withdraw')->get();
        
        foreach ($ac_charges as $charge) {
            $charge->charge_fixed = (float) $charge->charge_fixed;
            $charge->charge_percent = (float) $charge->charge_percent;
            $charge->from = (float) $charge->from;
            $charge->to = (float) $charge->to;
        }

        foreach($currencies as $currency){
            $currency->form_fields = json_decode($currency->userDetailsData, true);
        }

        return view('Template::user.withdraw.methods', compact('pageTitle', 'currencies', 'user', 'charges', 'account_currency', 'ac_charges'));
    }

    public function withdrawStore(Request $request)
    {
        $request->validate([
            'currency_id' => 'required',
            'amount' => 'required|numeric|gte:0',
        ]);

        $amount = $request->amount;

        $requestedCurrencyRate = $request->custom_rate;
        $user = auth()->user();

        
        $recv_currency = Currency::enabled()->availableForSell()->where('id', $request->currency_id)->with('userDetailsData')->firstOrFail();
        $currency = Currency::enabled()->availableForSell()->where('name', 'A/C BALANCE')->firstOrFail();
        
        $acc_charge = 0;
        $sell_charges = GpayCurrencyDiscountChargeModel::where('rules_for','sell')->where('currency_id',$currency->id)->whereJsonContains('apply_for','withdraw')->get();

        foreach($sell_charges as $charge){
            if($amount >= $charge->from && $amount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $amount;
                $acc_charge += $charge_fixed_amount + $charge_percent_amount;
            }
        }

        if ($user->neg_bal_allowed != 1 && ($request->amount + $acc_charge) > $user->balance) {
            $notify[] = ['error', 'You have not enough balance'];
            return back()->withNotify($notify);
        }

        $formData = $recv_currency->userDetailsData->form_data ?? null;

        $formProcessor = new FormProcessor;
        if ($formData) {
            $validationRule = $formProcessor->valueValidation($formData);
            $request->validate($validationRule);
        }
        $formValue = $formProcessor->processFormData($request, $formData);


        if ($user->neg_bal_allowed != 1 && $request->amount < ($currency->minimum_limit_for_sell * $currency->buy_at)) {
            $notify[] = ['error', 'Please follow the minimum limit'];
            return back()->withNotify($notify);
        }

        
        $getAmount = $requestedCurrencyRate ? $request->amount / $requestedCurrencyRate : $request->amount / $recv_currency->sell_at;
        if($getAmount > $recv_currency->maximum_limit_for_sell || $getAmount < $recv_currency->minimum_limit_for_sell){
            if($getAmount > $recv_currency->maximum_limit_for_sell){
                $notify[] = ['error', 'Withdraw Amount is Bigger Then Allowed Limit!'];
            }
            if($getAmount < $recv_currency->minimum_limit_for_sell){
                $notify[] = ['error', 'Withdraw Amount is Smaller Then Allowed Limit!'];
            }
            return redirect()->back()->withNotify($notify);
        }


        $buyingCharge = 0;
        $buy_charges = GpayCurrencyDiscountChargeModel::where('rules_for','buy')->where('currency_id',$recv_currency->id)->whereJsonContains('apply_for','withdraw')->get();
        

        foreach($buy_charges as $charge){
            if($getAmount >= $charge->from && $getAmount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $getAmount;
                $buyingCharge += $charge_fixed_amount + $charge_percent_amount;
            }
        }

        if ($recv_currency->neg_bal_allowed != 1 && $getAmount > $recv_currency->reserve) {
            $notify[] = ['error', 'Currently there is not enough balance in reserve.'];
            return back()->withNotify($notify);
        }

        $withdraw = new Exchange();
        $withdraw->exchange_id = $this->getTransactionSerial('WITHDRAW');
        $withdraw->send_currency_id =  $currency->id;
        $withdraw->receive_currency_id = $recv_currency->id;
        $withdraw->sending_amount =  $request->amount;
        $withdraw->receiving_amount = $getAmount;
        $withdraw->receiving_charge = $buyingCharge;
        $withdraw->sending_charge = $acc_charge;
        $withdraw->charge = gs('cur_text');
        $withdraw->buy_rate = $requestedCurrencyRate ? $requestedCurrencyRate : $recv_currency->sell_at;
        $withdraw->sell_rate = $currency->buy_at;

        
        $hiddenCharges = GpayHiddenChargeModel::where('currency_id', $recv_currency->id)->get();
        foreach ($hiddenCharges as $hidden) {
            if ($hidden->charge_percent && $hidden->charge_percent > 0) {
                $withdraw->hidden_charge_percent += $hidden->charge_percent;
            } 
            if ($hidden->charge_fixed && $hidden->charge_fixed > 0) {
                $withdraw->hidden_charge_fixed += $hidden->charge_fixed;
            }
        }


        $withdraw->user_id = $user->id;
        $withdraw->refund_amount = $request->amount + $acc_charge;
        $withdraw->admin_trx_no = getTrx();
        $withdraw->status = Status::WITHDRAW_PENDING;
        $withdraw->transaction_type = 'WITHDRAW';
        $withdraw->user_data = $formValue;

        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone_no' => $request->input('phone_no'),
        ];
        if(Auth::guard('web')->check() && Auth::guard('admin')->check()){
            $admin = Auth::guard('admin')->user();
            $withdraw->order_place_admin_id = $admin->id;
        }
        $withdraw->save();
        
        $user->balance -= $withdraw->refund_amount;
        $user->save();
        $user->balanceStatement()->create([
            "via" => "Withdraw Placed",
            "admin_id" => null,
            "before" => $user->balance + $withdraw->refund_amount,
            "after" => $user->balance,
            "exchange_id" => $withdraw->id,
        ]);

        $adminNotification = new AdminNotification;
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New withdraw request from '.$user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.data.details', $withdraw->id);
        $adminNotification->save();

        notify($user, 'WITHDRAW_REQUEST', [
            'method_name' => $withdraw->sendCurrency->name,
            'method_currency' => '',
            'method_amount' => showAmount($withdraw->receiving_amount, currencyFormat: false),
            'amount' => showAmount($withdraw->sending_amount, currencyFormat: false),
            'charge' => showAmount($withdraw->sending_charge, currencyFormat: false),
            'rate' => showAmount($withdraw->buy_rate, currencyFormat: false),
            'trx' => $withdraw->admin_trx_no,
            'post_balance' => showAmount($user->balance, currencyFormat: false),
            'balance_after_charge' => showAmount($withdraw->refund_amount, currencyFormat: false),
        ]);

        $notify[] = ['success', 'Please wait for admin approval'];

        $this->sendNotificationToTheAdmin($withdraw);

        return redirect()->back()->withNotify($notify);
    }

    public function withdrawLog(Request $request)
    {
        $pageTitle = 'Withdrawal Log';
        $withdraws = Withdrawal::where('user_id', auth()->id())->where('status', '!=', Status::PAYMENT_INITIATE);
        if ($request->search) {
            $withdraws = $withdraws->where('trx', $request->search);
        }
        $withdraws = $withdraws->with('method')->orderBy('id', 'desc')->paginate(getPaginate());

        return view('Template::user.withdraw.log', compact('pageTitle', 'withdraws'));
    }

    public function currencyUserData($id)
    {
        $currency = Currency::enabled()->where('available_for_buy', Status::YES)->where('id', $id)->first();
        if (! $currency) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found',
            ]);
        }
        $formData = @$currency->userDetailsData->form_data ?? null;
        $html = $formData ? view('components.viser-form', compact('formData'))->render() : '';

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }
}
