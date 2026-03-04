<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use App\Models\Currency;
use App\Models\Exchange;
use App\Constants\Status;
use Illuminate\Http\Request;
use App\Traits\TransactionTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        $exchange->exchange_id = $data['exchange_id'];
        $exchange->custom_rate = $buyRateInput;

        $exchange->sending_charge = 0;
        $exchange->receiving_charge = 0;
        $exchange->status = Status::WITHDRAW_PENDING;
        $exchange->created_at = $this->excelToDateTime($data['placed_at']);

        $exchange->transaction_proof_data = $data['aditional_field_payment_prove'];
        $exchange->order_place_admin_id = $data['placed_by'];
        $exchange->transaction_type = 'EXCHANGE';


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

        $recv_currency = Currency::enabled()->availableForBuy()->where('currency_id', 'account_balance')->first();

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
        $deposit->exchange_id = $data['exchange_id'];

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

        $deposit->created_at = $this->excelToDateTime($data['placed_at']);
        $deposit->updated_at = $this->excelToDateTime($data['updated_at']);
        $deposit->transaction_proof_data = $data['aditional_field_payment_prove'];
        $deposit->order_place_admin_id = $data['placed_by'];
        $deposit->save();

        return $deposit;
    }
    public function createWithdraw($data, $user_id)
    {
        $amount = $data['sending_amount'];

        $requestedCurrencyRate = $data['selling_rate'];
        $user = User::find($user_id);

        $recv_currency = Currency::enabled()->availableForSell()->where('id', $data['receiving_currency'])->firstOrFail();
        $currency = Currency::enabled()->availableForSell()->where('currency_id', 'account_balance')->firstOrFail();
        
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
        $withdraw->exchange_id = $data['exchange_id'];
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

        $withdraw->created_at = $this->excelToDateTime($data['placed_at']);
        $withdraw->updated_at = $this->excelToDateTime($data['updated_at']);
        
        $withdraw->transaction_proof_data = $data['aditional_field_payment_prove'];
        $withdraw->order_place_admin_id = $data['placed_by'];

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
    function excelToDateTime($excelSerial) {
        $unixTime = ($excelSerial - 25569) * 86400;
        return Carbon::createFromTimestamp($unixTime);
    }
    function excel_to_exchange()
    {
        try {
            DB::beginTransaction();
            $data = Excel::toArray([], public_path('Excel4th.xls'));
            $rows = $data[0];
    
            $keys = array_shift($rows); // First row = column headers
            $exchanges = [];
    
            foreach ($rows as $row) {
                $exchanges[] = array_combine($keys, $row);
            }
            foreach ($exchanges as $exchange) {
                if (Exchange::where('exchange_id', $exchange['Exchange Id'])->exists()) {
                    continue;
                }
                $lines = preg_split("/\r\n|\n|\r/", $exchange['aditional_field_payment_prove']);

                $result = [];

                foreach ($lines as $line) {
                    // Skip empty lines
                    if (trim($line) === '') continue;

                    // Split by the first colon
                    $parts = explode(":", $line, 2);

                    // Trim spaces
                    $key = isset($parts[0]) ? trim($parts[0]) : '';
                    $value = isset($parts[1]) ? trim($parts[1]) : '';

                    $result[] = [$key, $value];
                }

                $json = [];
                foreach($result as $index => $result_item){
                    $json[] = [
                        'name' => $result_item[0],
                        'type' => 'text',
                        'value' => $result_item[1],
                    ];
                };


                $sending_currency = Currency::where('name', $exchange['Send Currency'])->first();
                $receiving_currency = Currency::where('name', $exchange['Received Currency'])->first();
                $placed_by = $exchange['placed_by'] == 'User'? null: Admin::where('username', $exchange['placed_by'])->first()->id ?? null; 
                if(!$sending_currency){
                    continue;
                }
                $user = User::where('username',$exchange['User Username'])->first();
                if(!$user){
                    continue;
                }
                $data= [
                    "exchange_id" => $exchange['Exchange Id'],
                    "sending_currency" => $sending_currency->id,
                    "receiving_currency" => $receiving_currency->id,
                    "sending_amount" => $exchange['Sending Amount'],
                    "receiving_amount" => $exchange['Receiving Amount'],
                    "selling_rate" => $exchange['Sending Amount'] / $exchange['Receiving Amount'],
                    "buying_rate" => $exchange['Receiving Amount'] / $exchange['Sending Amount'],
                    "received_rate" => $exchange['Receiving Amount'] / $exchange['Sending Amount'],
                    "transaction_type" => $exchange['Transaction Type'],
                    "placed_by" => $placed_by,
                    "aditional_field_payment_prove" => $json,
                    "placed_at" => $exchange['placed_at'],
                    "updated_at" => $exchange['updated_at']
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
    function fix_currency(){
        $currencies = Currency::all();
        foreach($currencies as $currency){
            $currency->currency_id = Str::snake($currency->name);
            $currency->save();
        }
        return "Currency Fixed";
    }
}