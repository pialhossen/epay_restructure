<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Currency;
use App\Models\Exchange;
use App\Constants\Status;
use Illuminate\Http\Request;
use App\Traits\TransactionTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\GpayHiddenChargeModel;
use App\Models\GpayCurrencyDiscountChargeModel;

class ImportExcelController extends Controller
{
    use TransactionTrait;
    public function createExchange($data, $user_id, $cacheCharges = false)
    {
        // $data= [
        //     "sending_currency" => null,
        //     "receiving_currency" => null,
        //     "received_rate" => null,
        //     "sending_amount" => null,
        //     "receiving_amount" => null,
        //     "selling_rate" => null,
        //     "buying_rate" => null,
        // ];

        $randomDay = rand(4, 6);

        // Pick a random hour, minute, and second
        $randomHour = rand(0, 23);
        $randomMinute = rand(0, 59);
        $randomSecond = rand(0, 59);

        // Build the random datetime
        $randomDate = Carbon::create(2025, 11, $randomDay, $randomHour, $randomMinute, $randomSecond);


        $sendCurrency = Currency::enabled()->availableForSell()->find($data['sending_currency']);
        $receiveCurrency = Currency::enabled()->availableForBuy()->find($data['receiving_currency']);
        $buyRateInput = $data['received_rate'];
        $sendAmount = $data['sending_amount'];
        $receiveAmount = $data['receiving_amount'];
        if (!$buyRateInput || $buyRateInput <= 0) {
            $buyRateInput = $sendCurrency->buy_at;
        }

        $exchange = new Exchange();
        $exchange->user_id = $user_id;
        $exchange->send_currency_id = $sendCurrency->id;
        $exchange->receive_currency_id = $receiveCurrency->id;
        $exchange->exchange_id = getTrx();
        $exchange->custom_rate = $buyRateInput;

        $exchange->sending_charge = 0;
        $exchange->receiving_charge = 0;
        $exchange->status = Status::WITHDRAW_PENDING;
        $exchange->created_at = $randomDate;
        $exchange->updated_at = $randomDate->copy()->addMinutes(rand(1, 120)); // updated_at slightly after created_at


        // Start with base values

        $finalSendingAmount = $sendAmount;
        $finalReceivingAmount = $receiveAmount;

        // ✅ SELL Discount/Charge Logic — Apply All Matching Rules
        $sellCharges = GpayCurrencyDiscountChargeModel::where('currency_id', $sendCurrency->id)
            ->where('rules_for', 'sell')
            ->get();

        $sell_charge_percent = 0;
        $sell_charge_fixed = 0;
        $charges = [];

        foreach ($sellCharges as $sellCharge) {
            $from   = (float) $sellCharge->from;
            $to     = (float) $sellCharge->to;
            $amount = (float) $finalSendingAmount;

            if ($amount < $from || $amount > $to) {
                continue;
            }

            if (!empty($sellCharge->charge_percent)) {
                $sell_charge_percent += (float) $sellCharge->charge_percent;
                if($cacheCharges){
                    $charges['sell']['percent'][] = $sellCharge;
                } else {
                    $charges['sell']['percent'][] = $sellCharge->id;
                }
            }

            if (!empty($sellCharge->charge_fixed)) {
                $sell_charge_fixed += (float) $sellCharge->charge_fixed;
                if($cacheCharges){
                    $charges['sell']['fixed'][] = $sellCharge;
                }else{
                    $charges['sell']['fixed'][] = $sellCharge->id;
                }
            }
        }

        $sell_charge_percent_amount = ((float) $finalSendingAmount * $sell_charge_percent) / 100;
        $sell_charge_fixed_amount   = (float) $sell_charge_fixed;
        $sendingCharge = $sell_charge_percent_amount + $sell_charge_fixed_amount;

        // dump($sell_charge_percent);
        // dd($sell_charge_fixed);

        $exchange->sell_charge_percent = $sell_charge_percent;
        $exchange->sell_charge_fixed = $sell_charge_fixed;

        // ✅ BUY Discount/Charge Logic — Apply All Matching Rules
        $buyCharges = GpayCurrencyDiscountChargeModel::where('currency_id', $receiveCurrency->id)
            ->where('rules_for', 'buy')
            ->get();

        $receivingCharge = 0;
        $buy_charge_percent = 0;
        $buy_charge_fixed = 0;
        foreach ($buyCharges as $buyCharge) {
            $from = (float) $buyCharge->from;
            $to = (float) $buyCharge->to;
            $amount = (float) $finalReceivingAmount;

            if ($amount < $from || $amount > $to) {
                continue;
            }

            if (!empty($buyCharge->charge_percent)){
                $buy_charge_percent += $buyCharge->charge_percent;
                if($cacheCharges){
                    $charges['buy']['percent'][] = $buyCharge;
                } else {
                    $charges['buy']['percent'][] = $buyCharge->id;
                }
            }
            if (!empty($buyCharge->charge_fixed)) {
                $buy_charge_fixed += $buyCharge->charge_fixed;
                if($cacheCharges){
                    $charges['buy']['fixed'][] = $buyCharge;
                } else {
                    $charges['buy']['fixed'][] = $buyCharge->id;
                }
            }

            
        }
        $buy_charge_percent_amount = ((float) $finalReceivingAmount * (float) $buy_charge_percent) / 100; 
        $buy_charge_fixed_amount = (float) $buy_charge_fixed;
        $receivingCharge = $buy_charge_percent_amount + $buy_charge_fixed_amount;

        $exchange->buy_charge_percent = $buy_charge_percent;
        $exchange->buy_charge_fixed = $buy_charge_fixed;

        $exchange->sending_amount = $finalSendingAmount;
        $exchange->receiving_amount = $finalReceivingAmount;
        $exchange->sending_charge = $sendingCharge;
        $exchange->receiving_charge = $receivingCharge;

        $exchange->sell_rate = $receiveCurrency->sell_at;
        $exchange->buy_rate = $sendCurrency->buy_at;

        $exchange->customer_selling_rate = $data['selling_rate'];
        $exchange->customer_buying_rate = $data['buying_rate'];

        $exchange->charge = json_encode($charges);
        
        
        $exchange->save();

        return $exchange;
    }
    public function createDeposit($data, $user_id){
        // $data = [
        //     "sending_currency" => null,
        //     "receiving_currency" => null,
        //     "received_rate" => null,
        //     "sending_amount" => null,
        //     "receiving_amount" => null,
        //     "selling_rate" => null,
        //     "buying_rate" => null,
        // ];

        $randomDay = rand(4, 6);

        // Pick a random hour, minute, and second
        $randomHour = rand(0, 23);
        $randomMinute = rand(0, 59);
        $randomSecond = rand(0, 59);

        // Build the random datetime
        $randomDate = Carbon::create(2025, 11, $randomDay, $randomHour, $randomMinute, $randomSecond);

        $requestedCurrencyRate = $data['buying_rate'];
        
        $currency = Currency::enabled()->availableForBuy()->where('id', $data['sending_currency'])->firstOrFail();
        $selling_amount = $data['sending_amount'];

        $recv_currency = Currency::enabled()->availableForBuy()->where('name', 'A/C BALANCE')->first();

        $amount = $data['receiving_amount'];

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

        $buying_amount = $amount;

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
        $deposit->receiving_amount = $buying_amount;

        $deposit->sending_charge = $totalSellChargeAmount;
        $deposit->receiving_charge = $totalBuyChargeAmount;
        $deposit->user_id = $user_id;
        $deposit->charge = gs('cur_text');
        $deposit->status = Status::WITHDRAW_PENDING;
        $deposit->admin_trx_no = getTrx();
        $deposit->buy_rate = $requestedCurrencyRate ? $requestedCurrencyRate : $currency->buy_at;
        $deposit->custom_rate = $requestedCurrencyRate ? $requestedCurrencyRate: $currency->buy_at;
        $deposit->transaction_type = 'DEPOSIT';

        $deposit->created_at = $randomDate;
        $deposit->updated_at = $randomDate->copy()->addMinutes(rand(1, 120)); // updated_at slightly after created_at

        $deposit->save();

        return $deposit;
    }
    public function createWithdraw($data, $user_id)
    {
        // $data = [
        //     "sending_currency" => null,
        //     "receiving_currency" => null,
        //     "received_rate" => null,
        //     "sending_amount" => null,
        //     "receiving_amount" => null,
        //     "selling_rate" => null,
        //     "buying_rate" => null,
        // ];
        $amount = $data['sending_amount'];

        $requestedCurrencyRate = $data['selling_rate'];
        $user = User::find($user_id);

        
        $recv_currency = Currency::enabled()->availableForSell()->where('id', $data['receiving_currency'])->firstOrFail();
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

        
        $getAmount = $data['receiving_amount'];


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


        $withdraw = new Exchange();
        $withdraw->exchange_id = $this->getTransactionSerial('WITHDRAW');
        $withdraw->send_currency_id =  $currency->id;
        $withdraw->receive_currency_id = $recv_currency->id;
        $withdraw->sending_amount = $amount;
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
        $withdraw->refund_amount = $amount + $acc_charge;
        $withdraw->admin_trx_no = getTrx();
        $withdraw->status = Status::WITHDRAW_PENDING;
        $withdraw->transaction_type = 'WITHDRAW';

        $randomDay = rand(4, 6);

        // Pick a random hour, minute, and second
        $randomHour = rand(0, 23);
        $randomMinute = rand(0, 59);
        $randomSecond = rand(0, 59);

        // Build the random datetime
        $randomDate = Carbon::create(2025, 11, $randomDay, $randomHour, $randomMinute, $randomSecond);

        $withdraw->created_at = $randomDate;
        $withdraw->updated_at = $randomDate->copy()->addMinutes(rand(1, 120));

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

        return $withdraw;
    }
    function excel_to_exchange()
    {
        try {
            DB::beginTransaction();
            $data = Excel::toArray([], public_path('Excel.xlsx'));
            $rows = $data[0];
    
            $keys = array_shift($rows); // First row = column headers
            $exchanges = [];
    
            foreach ($rows as $row) {
                $exchanges[] = array_combine($keys, $row);
            }
            foreach ($exchanges as &$exchange) {
                $sending_currency = Currency::where('name', $exchange['Customer Send Currency'])->first();
                $receiving_currency = Currency::where('name', $exchange['Customer Received Currency'])->first();
                if(!$sending_currency){
                    continue;
                }
                $user = User::where('username',$exchange['User Username'])->first();
                if(!$user){
                    continue;
                }
                $data= [
                    "sending_currency" => $sending_currency->id,
                    "receiving_currency" => $receiving_currency->id,
                    "sending_amount" => $exchange['Customer Sending Amount'],
                    "receiving_amount" => $exchange['Customer Receiving Amount'],
                    "selling_rate" => $exchange['Customer Sending Amount'] / $exchange['Customer Receiving Amount'],
                    "buying_rate" => $exchange['Customer Receiving Amount'] / $exchange['Customer Sending Amount'],
                    "received_rate" => null,
                ];
                if (str_starts_with($exchange['Exchange Id'], 'WD-')) {
                    $this->createWithdraw($data,$user->id);
                } elseif (str_starts_with($exchange['Exchange Id'], 'DP-')) {
                    $this->createDeposit($data, $user->id);
                } else {
                    $this->createExchange($data,$user->id);
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return "Excel Data Uploaded To Database. DO NOT REFRESH";
    }
}