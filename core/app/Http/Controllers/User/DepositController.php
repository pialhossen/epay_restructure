<?php

namespace App\Http\Controllers\User;

use App\Models\Deposit;
use App\Models\Currency;
use App\Models\Exchange;
use App\Constants\Status;
use App\Lib\FormProcessor;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use App\Lib\CurrencyExchanger;
use App\Models\GatewayCurrency;
use App\Traits\TransactionTrait;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\GpayCurrencyDiscountChargeModel;

class DepositController extends Controller
{
    use TransactionTrait;

    public function depositMoney()
    {
        $account_currency = Currency::where('name','A/C BALANCE')->first();
        $pageTitle = 'Deposit Money';
        $user = auth()->user();
        $currencies = Currency::enabled()
                    ->where([
                        'available_for_buy' => Status::YES,
                        'available_for_deposit' => Status::YES
                    ])
                    ->with('transactionProvedData')
                    ->get();
        $charges = GpayCurrencyDiscountChargeModel::select('charge_fixed','charge_percent','currency_id','description','from','id','rules_for','title','to')->where('rules_for','sell')->whereJsonContains('apply_for','deposit')->get();

        $ac_charges = GpayCurrencyDiscountChargeModel::select('charge_fixed','charge_percent','currency_id','description','from','id','rules_for','title','to')->where('rules_for','buy')->where('currency_id', $account_currency->id)->whereJsonContains('apply_for','deposit')->get();

        foreach($currencies as $currency){
            $currency->form_fields = json_decode($currency->transactionProvedData, true);
        }

        return view('Template::user.deposit.requestForm', compact('pageTitle', 'currencies', 'user', 'charges', 'ac_charges', 'account_currency'));
    }

    public function depositStore(Request $request)
    {
        $request->validate([
            'currency_id' => 'required',
            'amount' => 'required|numeric|gte:0',
            'get_amount' => 'required'
        ]);
        
        
        $requestedCurrencyRate = $request->custom_rate;
        
        $user = auth()->user();
        $notify = [];
        
        $currency = Currency::enabled()->availableForBuy()->where('id', $request->currency_id)->firstOrFail();
        $selling_amount = (float)$request->get_amount;

        if($selling_amount > $currency->maximum_limit_for_buy || $selling_amount < $currency->minimum_limit_for_buy){
            if($selling_amount > $currency->maximum_limit_for_buy){
                $notify[] = ['error', 'Sending Currency Amount is Bigger Then Allowed Limit!'];
            }
            if($selling_amount < $currency->minimum_limit_for_buy){
                $notify[] = ['error', 'Sending Currency Amount is Smaller Then Allowed Limit!'];
            }
            return redirect()->back()->withNotify($notify);
        }
        $recv_currency = Currency::enabled()->availableForBuy()->where('name', 'A/C BALANCE')->first();
        if(!$recv_currency){
            $notify[] = ['error', 'A/C BALANCE is not enabled or not Available for buy!'];
            return redirect()->back()->withNotify($notify);
        }

        $amount = $request->amount;

        if($amount > $recv_currency->maximum_limit_for_sell || $amount < $recv_currency->minimum_limit_for_sell){
            if($amount > $recv_currency->maximum_limit_for_sell){
                $notify[] = ['error', 'Deposit Amount is Bigger Then Allowed Limit!'];
            }
            if($amount < $recv_currency->minimum_limit_for_sell){
                $notify[] = ['error', 'Deposit Amount is Smaller Then Allowed Limit!'];
            }
            
            return redirect()->back()->withNotify($notify);
        }
        $formData = $currency->transactionProvedData->form_data ?? null;
        
        
        $formProcessor = new FormProcessor;
        if ($formData) {
            $validationRule = $formProcessor->valueValidation($formData);
            $request->validate($validationRule); // here is the error
        }
        $formValue = $formProcessor->processFormData($request, $formData);

        $amount = $requestedCurrencyRate ? $amount / $requestedCurrencyRate : $amount / $currency->buy_at;

        $totalSellChargeAmount = 0;
        $sell_charges = GpayCurrencyDiscountChargeModel::where('rules_for','sell')->where('currency_id',$currency->id)->whereJsonContains('apply_for','deposit')->get();

        foreach($sell_charges as $charge){
            if($amount >= $charge->from && $amount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $amount;
                $totalSellChargeAmount += $charge_fixed_amount + $charge_percent_amount;
            }
        }

        $totalBuyChargeAmount = 0;
        $buy_charges = GpayCurrencyDiscountChargeModel::where('rules_for','buy')->where('currency_id',$recv_currency->id)->whereJsonContains('apply_for','deposit')->get();

        $buying_amount = (float)$request->amount;

        foreach($buy_charges as $charge){
            if($buying_amount >= $charge->from && $buying_amount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $buying_amount;
                $totalBuyChargeAmount += $charge_fixed_amount + $charge_percent_amount;
            }
        }

        $deposit = new Exchange();
        $deposit->exchange_id = $this->getTransactionSerial('DEPOSIT');

        $deposit->receive_currency_id = $recv_currency->id;
        $deposit->send_currency_id = $currency->id;
        $deposit->sending_amount = $selling_amount;
        $deposit->receiving_amount = $request->amount;

        $deposit->sending_charge = $totalSellChargeAmount;
        $deposit->receiving_charge = $totalBuyChargeAmount;
        $deposit->user_id = $user->id;
        $deposit->charge = gs('cur_text');
        $deposit->status = Status::WITHDRAW_PENDING;
        $deposit->admin_trx_no = getTrx();
        $deposit->buy_rate = $requestedCurrencyRate ? $requestedCurrencyRate : $currency->buy_at;
        $deposit->custom_rate = $requestedCurrencyRate ? $requestedCurrencyRate: $currency->buy_at;
        $deposit->transaction_type = 'DEPOSIT';
        $deposit->transaction_proof_data = $formValue;
        if(Auth::guard('web')->check() && Auth::guard('admin')->check()){
            $admin = Auth::guard('admin')->user();
            $deposit->order_place_admin_id = $admin->id;
        }
        $deposit->save();

        if ($deposit->sendCurrency->gateway_id != 0) {
            return $this->createDeposit($deposit);
        }

        $adminNotification = new AdminNotification;
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New deposit request from '.$user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.data.details', $deposit->id);
        $adminNotification->save();

        $notify[] = ['success', 'Please wait for admin approval'];

        $this->sendNotificationToTheAdmin($deposit);

        return redirect()->back()->withNotify($notify);
    }
    private function createDeposit($exchange)
    {
        $curSymbol = $exchange->sendCurrency->cur_sym;
        $code = $exchange->sendCurrency->gatewayCurrency->code;
        $gateway = GatewayCurrency::where('method_code', $code)->where('currency', $curSymbol)->first();

        if (! $gateway) {
            $notify[] = ['error', 'Something went the wrong with exchange processing'];

            return back()->withNotify($notify);
        }
        $amount = $exchange->sending_amount + $exchange->sending_charge;

        $deposit = new Deposit;
        $deposit->user_id = auth()->id();
        $deposit->method_code = $code;
        $deposit->method_currency = strtoupper($curSymbol);
        $deposit->amount = $amount;
        $deposit->charge = $exchange->sending_charge;
        $deposit->rate = $exchange->buy_rate;
        $deposit->final_amount = $amount;
        $deposit->btc_amount = 0;
        $deposit->btc_wallet = '';
        $deposit->trx = $exchange->exchange_id;
        $deposit->try = 0;
        $deposit->success_url = urlPath('user.exchange.list');
        $deposit->failed_url = urlPath('user.exchange.list');
        $deposit->status = 0;
        $deposit->exchange_id = $exchange->id;
        $deposit->save();

        session()->put('Track', $deposit->trx);
        
        return redirect()->route('user.deposit.confirm');
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
        $formData = @$currency->transactionProvedData->form_data ?? null;
        $html = $formData ? view('components.viser-form', compact('formData'))->render() : '';

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }
}
