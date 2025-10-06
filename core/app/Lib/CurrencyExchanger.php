<?php

namespace App\Lib;

use App\Models\Currency;
use App\Models\Exchange;
use App\Models\GpayCurrencyDiscountChargeModel;
use App\Models\GpayHiddenChargeModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CurrencyExchanger
{
    protected $sendCurrency;
    protected $receiveCurrency;
    protected $sendAmount;
    protected $receiveAmount;
    protected $charge;
    public $exchange;
    public $buyRateInput;

    public function currencyExchanger(Request $request)
    {
        $this->validation($request);

        $this->sendCurrency = Currency::enabled()->availableForSell()->find($request->sending_currency);
        $this->receiveCurrency = Currency::enabled()->availableForBuy()->find($request->receiving_currency);

        if (!$this->sendCurrency) {
            return ['status' => 'error', 'notify' => [['error', 'Sending currency not found']]];
        }

        if (!$this->receiveCurrency) {
            return ['status' => 'error', 'notify' => [['error', 'Receiving currency not found']]];
        }

        $this->sendAmount = $request->sending_amount;
        $this->receiveAmount = $request->receiving_amount;

        // Buy rate input fallback
        $this->buyRateInput = $request->received_rate;
        if (!$this->buyRateInput || $this->buyRateInput <= 0) {
            $this->buyRateInput = $this->sendCurrency->buy_at;
        }

        try {
            // Sending charges
            $sendingPercentCharge = $this->sendAmount * $this->sendCurrency->percent_charge_for_buy / 100;
            $sendingFixedCharge = $this->sendCurrency->fixed_charge_for_buy;
            $totalSendingCharge = $sendingFixedCharge + $sendingPercentCharge;

            // Receiving charges
            $receivingPercentCharge = $this->receiveAmount * $this->receiveCurrency->percent_charge_for_sell / 100;
            $receivingFixedCharge = $this->receiveCurrency->fixed_charge_for_sell;
            $totalReceivingCharge = $receivingFixedCharge + $receivingPercentCharge;

            $totalReceivedAmount = $this->receiveAmount - $totalReceivingCharge;
        } catch (Exception $ex) {
            return ['status' => 'error', 'notify' => [['error', 'Something went wrong with the exchange processing.']]];
        }

        // Validations
        if ($request->sending_amount < $this->sendCurrency->minimum_limit_for_buy) {
            return ['status' => 'error', 'notify' => [['error', 'Minimum sending amount ' . number_format($this->sendCurrency->minimum_limit_for_buy, $this->sendCurrency->show_number_after_decimal) . ' ' . $this->sendCurrency->cur_sym]]];
        }

        if ($request->sending_amount > $this->sendCurrency->maximum_limit_for_buy) {
            return ['status' => 'error', 'notify' => [['error', 'Maximum sending amount ' . number_format($this->sendCurrency->maximum_limit_for_buy, $this->sendCurrency->show_number_after_decimal) . ' ' . $this->sendCurrency->cur_sym]]];
        }

        if ($this->receiveAmount < $this->receiveCurrency->minimum_limit_for_sell) {
            return ['status' => 'error', 'notify' => [['error', 'Minimum received amount ' . number_format($this->receiveCurrency->minimum_limit_for_sell, $this->receiveCurrency->show_number_after_decimal) . ' ' . $this->receiveCurrency->cur_sym]]];
        }

        if ($this->receiveAmount > $this->receiveCurrency->maximum_limit_for_sell) {
            return ['status' => 'error', 'notify' => [['error', 'Maximum received amount ' . number_format($this->receiveCurrency->maximum_limit_for_sell, $this->receiveCurrency->show_number_after_decimal) . ' ' . $this->receiveCurrency->cur_sym]]];
        }
        if (($totalReceivedAmount > $this->receiveCurrency->reserve) && ((int)$this->receiveCurrency->neg_bal_allowed !== 1) && ($this->receiveCurrency->reserve - $totalReceivedAmount) < 0){
            return ['status' => 'error', 'notify' => [['error', 'Sorry, our reserve limit exceeded']]];
        }

        if ($totalReceivedAmount <= 0) {
            return ['status' => 'error', 'notify' => [['error', 'Negative amount is not acceptable']]];
        }

        // Charges Summary
        $this->charge = [
            'sending_charge' => [
                'fixed_charge' => $sendingFixedCharge,
                'percent_charge' => $this->sendCurrency->percent_charge_for_buy,
                'percent_amount' => $sendingPercentCharge,
                'total_charge' => $totalSendingCharge,
            ],
            'receiving_charge' => [
                'fixed_charge' => $receivingFixedCharge,
                'percent_charge' => $this->receiveCurrency->percent_charge_for_sell,
                'percent_amount' => $receivingPercentCharge,
                'total_charge' => $totalReceivingCharge,
            ],
        ];

        return ['status' => 'success'];
    }

    public function createOrUpdateExchange($id = null, $cacheCharges = false)
    {
        if($id){
            $this->exchange = Exchange::findOrFail($id);
            $this->sendCurrency = Currency::findOrFail($this->exchange->send_currency_id);
            $this->receiveCurrency = Currency::findOrFail($this->exchange->receive_currency_id);
        } else {
            $this->exchange = new Exchange();
            $this->exchange->user_id = auth()->id();
            $this->exchange->send_currency_id = $this->sendCurrency->id;
            $this->exchange->receive_currency_id = $this->receiveCurrency->id;
            $this->exchange->exchange_id = getTrx();
            $this->exchange->custom_rate = $this->buyRateInput;
    
            $this->exchange->sending_charge = 0;
            $this->exchange->receiving_charge = 0;
        }


        // Start with base values
        if($id){
            $finalSendingAmount = $this->exchange->sending_amount;
            $finalReceivingAmount = $this->exchange->receiving_amount;
        } else {
            $finalSendingAmount = $this->sendAmount;
            $finalReceivingAmount = $this->receiveAmount;
        }

        // ✅ SELL Discount/Charge Logic — Apply All Matching Rules
        $sellCharges = GpayCurrencyDiscountChargeModel::where('currency_id', $this->sendCurrency->id)
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

        $this->exchange->sell_charge_percent = $sell_charge_percent;
        $this->exchange->sell_charge_fixed = $sell_charge_fixed;

        // ✅ BUY Discount/Charge Logic — Apply All Matching Rules
        $buyCharges = GpayCurrencyDiscountChargeModel::where('currency_id', $this->receiveCurrency->id)
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

        $this->exchange->buy_charge_percent = $buy_charge_percent;
        $this->exchange->buy_charge_fixed = $buy_charge_fixed;

        $this->exchange->sending_amount = $finalSendingAmount;
        $this->exchange->receiving_amount = $finalReceivingAmount;
        $this->exchange->sending_charge = $sendingCharge;
        $this->exchange->receiving_charge = $receivingCharge;

        $this->exchange->sell_rate = $this->receiveCurrency->sell_at;
        $this->exchange->buy_rate = $this->sendCurrency->buy_at;

        if(!$id){
            $this->exchange->customer_selling_rate = request()->selling_rate;
            $this->exchange->customer_buying_rate = request()->buying_rate;
        }

        $this->exchange->charge = json_encode($charges);


        if (gs('exchange_auto_cancel') && !$this->exchange->expired_at) {
            $this->exchange->expired_at = now()->addMinutes((float)gs('exchange_auto_cancel_time'));
        }
        
        $this->exchange->save();
        session()->put('EXCHANGE_TRACK', $this->exchange->exchange_id);
        return $this->exchange;
    }

    protected function validation($request)
    {
        $request->validate([
            'sending_amount' => 'required|numeric|gt:0',
            'receiving_amount' => 'required|numeric|gt:0',
            'sending_currency' => 'required|integer',
            'receiving_currency' => 'required|integer|different:sending_currency',
        ]);
    }
}
