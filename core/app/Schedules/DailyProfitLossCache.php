<?php

namespace App\Schedules;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\FinalProfit;
use Illuminate\Support\Facades\File;
use App\Models\DailyProfitLossDailyCache;
use App\Models\FinalProfitLossDailyCache;

class DailyProfitLossCache
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    private function finalProfitCache()
    {
        $currencies = Currency::whereNotIn('currency_id', ['account_balance'])->orderBy('created_at')->get();
        $exchangesQuery = Exchange::where('status', 1)->with(['sendCurrency', 'receivedCurrency']);
        // Fetch once (no N+1 queries)
        $exchanges = $exchangesQuery->get();

        // Prepare calculations
        $transactions = [];
        $currencyProfit = 0;
        $searchingDay = Carbon::now()->format('Ymd');
        $lastDay = Carbon::now()->format('Ymd');

        foreach ($currencies as $currency) {
            $reserved = $currency->reserve;
            $reserved = getAmount($reserved);

            // Start last day final profit data to calculate
            // snd avg rate
            $finalProfitData = FinalProfit::where([
                'currency_id' => $currency->id,
                'business_day' => $lastDay
            ])->first();
            
            $lastDayReserved = $finalProfitData->currency_reserved ?? 0;
            $lastDayCurrencyTotal = $finalProfitData->currency_total ?? 0;

            // Exchanges where this currency is the sending currency
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

        // transactions final profit data saved into db
        foreach ($transactions as $currency => $transaction) {
            $currencyProfitData = FinalProfit::where([
                'currency_id' => $transaction['currency_id'],
                'business_day' => Carbon::now()->format('Ymd')
            ])->first();

            if (!$currencyProfitData) {
                $currencyProfitData = new FinalProfit();
            }

            // now saved data in to db table
            $currencyProfitData->currency_id = $transaction['currency_id'];
            $currencyProfitData->currency = $currency;
            $currencyProfitData->business_day = Carbon::now()->format('Ymd');
            $currencyProfitData->cs_sent_avg_rate = $transaction['customer_avg_sent_rate'];
            $currencyProfitData->currency_reserved = $transaction['currency_reserved'];
            $currencyProfitData->currency_total = $transaction['currency_total'];
            $currencyProfitData->all_currency_total = $currencyProfit;
            $currencyProfitData->all_active_users_balance = $totalUserBalance;
            $currencyProfitData->total_profit = $totalProfit;
            $currencyProfitData->save();
        }
        $payload = ['transactions' => $transactions, 'totalUserBalance' => $totalUserBalance, 'currencyProfit' => $currencyProfit, 'totalProfit' => $totalProfit, 'currencies' => $currencies];
        FinalProfitLossDailyCache::create(['json_data' => json_encode($payload)]);
    }
    private function dailyProfitCache()
    {
        $currencies = Currency::whereNotIn('currency_id', ['account_balance'])->orderBy('created_at')->get();
        $exchangesQuery = Exchange::where('status', 1)->with(['sendCurrency', 'receivedCurrency']);
        // Fetch once (no N+1 queries)
        $exchanges = $exchangesQuery->get();

        // Prepare calculations
        $transactions = [];
        $currencyProfit = 0;
        $searchingDay = Carbon::now()->format('Ymd');
        $lastDay = Carbon::now()->format('Ymd');

        foreach ($currencies as $currency) {
            $reserved = $currency->reserve;
            $reserved = getAmount($reserved);

            // Start last day final profit data to calculate
            // snd avg rate
            $finalProfitData = FinalProfit::where([
                'currency_id' => $currency->id,
                'business_day' => $lastDay
            ])->first();
            
            $lastDayReserved = $finalProfitData->currency_reserved ?? 0;
            $lastDayCurrencyTotal = $finalProfitData->currency_total ?? 0;

            // Exchanges where this currency is the sending currency
            $sentExchanges = $exchanges->where('send_currency_id', $currency->id);
            $sentAmount = getAmount($sentExchanges->sum('sending_amount'));
            $receivedAny = getAmount($sentExchanges->sum('receiving_amount'));
            


            $sentAmount += getAmount($lastDayReserved);
            $receivedAny += getAmount($lastDayCurrencyTotal);
            $buy_at = $currency->buy_at;
            $currency_total = $reserved * getAmount($buy_at);
            $currencyProfit += $currency_total;
            $transactions[$currency->name] = [
                'currency_id' => $currency->id,
                'currency_reserved' => $reserved,
                'customer_avg_sent_rate' => getAmount($buy_at),
                'currency_total' => getAmount($currency_total),
            ];
        }

        $totalUserBalance = User::where('status', 1)->sum('balance');
        $totalUserBalance = getAmount($totalUserBalance);
        $currencyProfit = getAmount($currencyProfit);
        $totalProfit = getAmount($currencyProfit - $totalUserBalance);

        $payload = ['transactions' => $transactions, 'totalUserBalance' => $totalUserBalance, 'currencyProfit' => $currencyProfit, 'totalProfit' => $totalProfit, 'currencies' => $currencies];
        DailyProfitLossDailyCache::create(['json_data' => json_encode($payload)]);
    }
    function writeScheduleLog(string $message = "Schedule Run At..."): void
    {
        $filePath = storage_path('logs/schdule.log');
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;

        File::append($filePath, $logEntry);
    }
    public function __invoke()
    {
        $this->finalProfitCache();
        $this->dailyProfitCache();
        $this->writeScheduleLog();
    }
}
