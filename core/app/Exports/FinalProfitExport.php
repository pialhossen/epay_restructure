<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\FinalProfit;
use App\Models\CurrencyReservedLog;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Models\FinalProfitLossDailyCache;

class FinalProfitExport implements FromView
{
    protected $request;

    public function getPreviousFinalProfitLossDailyCache($date){
        $data = FinalProfitLossDailyCache::whereDate('created_at', $date)->first();
        if($data){
            return json_decode($data->json_data);
        }
        return false;
    }
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        $pageTitle = 'POS FINAL PROFIT RESULT';
        $transactions = [];
        $totalUserBalance = 0;
        $totalProfit = 0;
        $currencyProfit = 0;
        $currencies_all = Currency::whereNotIn('currency_id', ['account_balance'])->orderBy('created_at')->get();
        $currencies_query = Currency::whereNotIn('currency_id', ['account_balance'])->orderBy('created_at');
        if($this->request->get('currency_id')){
            $currency_ids = $this->request->currency_id;
            $currencies_query = $currencies_query->whereIn('id', $currency_ids);
        }
        $currencies = $currencies_query->get();
        if ($this->request->created_from) {
            $createdFrom = Carbon::parse($this->request->created_from);
            $today       = Carbon::today();

            $isBefore = $createdFrom->isBefore($today);
            $isAfter = $createdFrom->isAfter($today);
            
            if($isBefore){
                $data = $this->getPreviousFinalProfitLossDailyCache($this->request->created_from);
                if($data){
                    $transactions = json_decode(json_encode($data->transactions), true);
                    $totalUserBalance = $data->totalUserBalance;
                    $currencyProfit = $data->currencyProfit;
                    $totalProfit = $data->totalProfit;
                    $currencies = $data->currencies;
                    $currencies_all = $currencies;
                    if($this->request->get("currency_id")){
                        $ids = $this->request->get("currency_id");

                        $transactions = array_filter($transactions, function ($transaction) use ($ids) {
                            return in_array($transaction['currency_id'], $ids);
                        });

                        $currencyProfit = 0;
                        foreach($transactions as $transaction){
                            $currencyProfit += $transaction['currency_total'];
                        } 
                        $totalProfit = getAmount($currencyProfit - $totalUserBalance);

                    }

                    return view('admin.pos.exports.final_profit', [
                        'transactions' => $transactions,
                        'currencyProfit' => $currencyProfit,
                        'totalUserBalance' => $totalUserBalance,
                        'totalProfit' => $totalProfit,
                        'currencies' => $currencies
                    ]);
                }
                $notify[] = ['error', 'Date not found'];
                return to_route('admin.pos.final_profit')->withNotify($notify);
            }
            if($isAfter){
                $notify[] = ['error', 'Invalid date range'];
                return to_route('admin.pos.final_profit')->withNotify($notify);
            }

            $exchangesQuery = Exchange::where('status', 1)->with(['sendCurrency', 'receivedCurrency']);

            if ($this->request->transaction_type) {
                $exchangesQuery->where('transaction_type', $this->request->transaction_type);
            }
            $exchangesQuery->whereBetween('updated_at', [
                date('Y-m-d 00:00:00', strtotime($this->request->created_from)),
                date('Y-m-d 23:59:59', strtotime($this->request->created_from))
            ]);

            $exchanges = $exchangesQuery->get();

            $transactions = [];
            $currencyProfit = 0;
            $searchingDay = Carbon::parse($this->request->created_from)->format('Ymd');
            $lastDay = Carbon::parse($this->request->created_from)->subDay()->format('Ymd');

            foreach ($currencies as $currency) {
                $reserved = CurrencyReservedLog::where('currency_id', $currency->id)
                    ->where('business_day', '<=', $searchingDay)
                    ->orderBy('business_day', 'desc')
                    ->pluck('reserved')->first();

                if (! $reserved) {
                    $reserved = $currency->reserve;
                }
                $reserved = getAmount($reserved);
                $finalProfitData = FinalProfit::where([
                    'currency_id' => $currency->id,
                    'business_day' => $lastDay
                ])->first();
                $lastDayReserved = $finalProfitData->currency_reserved ?? 0;
                $lastDayCurrencyTotal = $finalProfitData->currency_total ?? 0;

                $sentExchanges = $exchanges->where('send_currency_id', $currency->id);
                $sentAmount = getAmount($sentExchanges->sum('sending_amount'));
                $receivedAny = getAmount($sentExchanges->sum('receiving_amount'));


                $sentAmount += getAmount($lastDayReserved);
                $receivedAny += getAmount($lastDayCurrencyTotal);
                $avgSentRate = $sentAmount > 0 ? $receivedAny / $sentAmount : 0;
                $currency_total = $reserved * getAmount($avgSentRate);
                $currencyProfit += $currency_total;

                $transactions[$currency->name] = [
                    'currency_id' => $currency->id,
                    'currency_reserved' => $reserved,
                    'customer_avg_sent_rate' => getAmount($avgSentRate),
                    'currency_total' => getAmount($currency_total),
                ];
            }

            $totalUserBalance = User::where('status', 1)->sum('balance');
            $totalUserBalance = getAmount($totalUserBalance);
            $currencyProfit = getAmount($currencyProfit);
            $totalProfit = getAmount($currencyProfit - $totalUserBalance);

            foreach ($transactions as $currency => $transaction) {
                $currencyProfitData = FinalProfit::where([
                    'currency_id' => $transaction['currency_id'],
                    'business_day' => Carbon::parse($this->request->created_from)->format('Ymd')
                ])->first();

                if (! $currencyProfitData) {
                    $currencyProfitData = new FinalProfit();
                }

                $currencyProfitData->currency_id = $transaction['currency_id'];
                $currencyProfitData->currency = $currency;
                $currencyProfitData->business_day = Carbon::parse($this->request->created_from)->format('Ymd');
                $currencyProfitData->cs_sent_avg_rate = $transaction['customer_avg_sent_rate'];
                $currencyProfitData->currency_reserved = $transaction['currency_reserved'];
                $currencyProfitData->currency_total = $transaction['currency_total'];
                $currencyProfitData->all_currency_total = $currencyProfit;
                $currencyProfitData->all_active_users_balance = $totalUserBalance;
                $currencyProfitData->total_profit = $totalProfit;
                $currencyProfitData->save();
            }
        }

        return view('admin.pos.exports.final_profit', [
            'transactions' => $transactions,
            'currencyProfit' => $currencyProfit,
            'totalUserBalance' => $totalUserBalance,
            'totalProfit' => $totalProfit,
            'currencies' => $currencies
        ]);
    }
}
