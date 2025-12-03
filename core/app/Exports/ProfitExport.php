<?php

namespace App\Exports;

use App\Models\Currency;
use App\Models\Exchange;
use App\Models\FinalProfit;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProfitExport implements FromView
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        // Find the last day
        $lastDay = Carbon::parse($this->request->created_from)->subDay()->format('Ymd');

        $currencyQuery = Currency::whereNotIn('currency_id', ['account_balance'])->orderBy('created_at');
        if ($this->request->currency_id) {
            $currencyQuery = $currencyQuery->whereIn('id', $this->request->currency_id);
        }
        $cs = $currencyQuery->get();

        // Build query with filters
        $exchangesQuery = Exchange::with(['user', 'sendCurrency', 'receivedCurrency']);

        if ($this->request->exchange_id) {
            $exchangesQuery->where('exchange_id', $this->request->exchange_id);
        }
        if ($this->request->status) {
            $exchangesQuery->where('status', $this->request->status);
        }
        if ($this->request->email) {
            $exchangesQuery->whereHas('user', function ($query) {
                $query->where(function ($q) {
                    $q->where('email', $this->request->email)
                        ->orWhere('username', $this->request->email);
                });
            });
        }
        if ($this->request->transaction_type) {
            $exchangesQuery->where('transaction_type', $this->request->transaction_type);
        }
        if ($this->request->created_from && $this->request->created_to) {
            $exchangesQuery->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($this->request->created_from)),
                date('Y-m-d 23:59:59', strtotime($this->request->created_to))
            ]);
        }

        // Fetch once (no N+1 queries)
        $exchanges = $exchangesQuery->get();

        // Prepare calculations
        $transactions = [];
        $totalProfitAll = 0;

        foreach ($cs as $currency) {
            // Find the last day final profit
            $finalProfitData = FinalProfit::where([
                    'currency_id' => $currency->id,
                    'business_day' => $lastDay
                ])->first();

            // Exchanges where this currency is the sending currency
            // $sentExchanges = $exchanges->where('send_currency_id', $currency->id);
            // $sentAmount = $sentExchanges->sum('sending_amount');
            // $receivedAny = $sentExchanges->sum('receiving_amount');
            // $avgSentRate = $sentAmount > 0 ? $receivedAny / $sentAmount : 0;

            $sentExchanges = $exchanges->where('send_currency_id', $currency->id);

            $sentAmount = $sentExchanges->sum('sending_amount');
            $lastDayReserved = $finalProfitData->currency_reserved ?? 0;
            $sentProfit = $sentAmount + $lastDayReserved;

            $receivedAny = $sentExchanges->sum('receiving_amount');
            $lastDayCurrencyTotal = $finalProfitData->currency_total ?? 0;
            $receivedProfit = $receivedAny + $lastDayCurrencyTotal;

            $avgSentRate = $sentProfit > 0 ? $receivedProfit / $sentProfit : 0;

            // Exchanges where this currency is the receiving currency
            $receivedExchanges = $exchanges->where('receive_currency_id', $currency->id);
            $hiddenChargeAmount = 0;
            foreach($receivedExchanges as $receivedExchange){
                $hidden_charge_percentage_amount = ($receivedExchange->hidden_charge_percent / 100) * $receivedExchange->receiving_amount;
                $hiddenChargeAmount += $hidden_charge_percentage_amount + $receivedExchange->hidden_charge_fixed;
            }
            $receivedThis = $receivedExchanges->sum('receiving_amount');
            $sentAny = $receivedExchanges->sum('sending_amount');
            $avgReceivedRate = $receivedThis > 0 ? $sentAny / ($receivedThis + $hiddenChargeAmount) : 0;

            // Profit calculations
            $avgProfitRate = $avgReceivedRate - $avgSentRate;
            $totalProfit = $receivedThis * $avgProfitRate;
            $totalProfitAll += $totalProfit;

            $transactions[$currency->name] = [
                'last_day_reserved' => getAmount($lastDayReserved),
                'sent_profit' => getAmount($sentProfit),
                'customer_sent_amount_by_this_currency' => getAmount($sentProfit),
                'customer_received_amount_by_any_currency' => getAmount($receivedProfit),
                'last_day_currency_total' => getAmount($lastDayCurrencyTotal),
                'received_profit' => getAmount($receivedProfit),
                'customer_avg_sent_rate' => getAmount($avgSentRate),
                'customer_received_amount_by_this_currency' => getAmount($receivedThis),
                'customer_sent_amount_by_any_currency' => getAmount($sentAny),
                'customer_avg_received_rate' => getAmount($avgReceivedRate),
                'hidden_charge_amount' => getAmount($hiddenChargeAmount),
                'avg_profit_rate' => getAmount($avgProfitRate),
                'total_profit' => getAmount($totalProfit)
            ];
        }
        $totalProfitAll = getAmount($totalProfitAll);

        return view('admin.pos.exports.profit', [
            'transactions' => $transactions,
            'totalProfitAll' => $totalProfitAll,
        ]);
    }
}
