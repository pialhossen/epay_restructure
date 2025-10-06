<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DailyProfitExport;
use App\Http\Controllers\Controller;
use App\Models\FinalProfitLossDailyCache;
use App\Schedules\DailyProfitLossCache;
use Illuminate\Http\Request;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\User;
use App\Models\dailyprofitlog;
use Carbon\Carbon;
use Exception;
use App\Exports\FinalProfitExport;
use App\Exports\ProfitExport;
use App\Models\CurrencyReservedLog;
use App\Models\FinalProfit;
use App\Models\GeneralSetting;
use Maatwebsite\Excel\Facades\Excel;

class PosController extends Controller
{
    public function index(Request $request)
    {

        try {
            $pageTitle = 'POS AVERAGE PROFIT RESULT';
            $currencies = Currency::whereNotIn('name', ['A/C BALANCE'])->get();
            $exchanges = [];
            $transactions = [];
            $totalProfitAll = 0;

            if ($request->created_from) {

                // Find the last day
                $lastDay = Carbon::parse($request->created_from)->subDay()->format('Ymd');

                if ($request->submit_button == 'DOWNLOAD') {
                    $title = Carbon::now()->format('Ymd') . '_profit_report.xlsx';
                    return Excel::download(new ProfitExport($request), $title);
                }

                $currencyQuery = Currency::orderBy('created_at');
                if ($request->currency_id) {
                    $currencyQuery = $currencyQuery->whereIn('id', $request->currency_id);
                }
                $cs = $currencyQuery->whereNotIn('name', ['A/C BALANCE'])->get();

                // Build query with filters
                $exchangesQuery = Exchange::with(['user', 'sendCurrency', 'receivedCurrency']);

                if ($request->exchange_id) {
                    $exchangesQuery->where('exchange_id', $request->exchange_id);
                }
                if ($request->status) {
                    $exchangesQuery->where('status', $request->status);
                }
                if ($request->email) {
                    $exchangesQuery->whereHas('user', function ($query) use ($request) {
                        $query->where(function ($q) use ($request) {
                            $q->where('email', $request->email)
                                ->orWhere('username', $request->email);
                        });
                    });
                }
                if ($request->transaction_type) {
                    $exchangesQuery->where('transaction_type', $request->transaction_type);
                }
                if ($request->created_from && $request->created_to) {
                    $exchangesQuery->whereBetween('updated_at', [
                        date('Y-m-d 00:00:00', strtotime($request->created_from)),
                        date('Y-m-d 23:59:59', strtotime($request->created_to))
                    ]);
                }

                // Fetch once (no N+1 queries)
                $exchanges = $exchangesQuery->get();

                // Prepare calculations
                $transactions = [];
                $totalProfitAll = 0;

                foreach ($cs as $currency) {
                    // Find the last day final profit data
                    $finalProfitData = FinalProfit::where([
                        'currency_id' => $currency->id,
                        'business_day' => $lastDay
                    ])->first();

                    // Exchanges where this currency is the sending currency
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
                        'customer_sent_amount_by_this_currency' => getAmount($sentAmount),
                        'last_day_reserved' => getAmount($lastDayReserved),
                        'sent_profit' => getAmount($sentProfit),
                        'customer_received_amount_by_any_currency' => getAmount($receivedAny),
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
            }
        } catch (\Exception $ex) {
            $notify[] = ['error', $ex->getMessage()];
            return to_route('admin.pos.index')->withNotify($notify);
        }

        return view('admin.pos.index', compact('pageTitle', 'exchanges', 'transactions', 'totalProfitAll', 'currencies', 'request'));
    }

    public function getFinalProfit(Request $request)
    {
        $pageTitle = 'POS FINAL PROFIT RESULT';
        $transactions = [];
        $totalUserBalance = 0;
        $totalProfit = 0;
        $currencyProfit = 0;
        $currencies_all = Currency::whereNotIn('name', ['A/C BALANCE'])->orderBy('created_at')->get();
        $currencies_query = Currency::whereNotIn('name', ['A/C BALANCE'])->orderBy('created_at');
        if($request->get('currency_id')){
            $currency_ids = $request->currency_id;
            $currencies_query = $currencies_query->whereIn('id', $currency_ids);
        }
        $currencies = $currencies_query->get();
        if ($request->created_from) {
            $createdFrom = Carbon::parse($request->created_from);
            $today       = Carbon::today();

            $isBefore = $createdFrom->isBefore($today);
            $isAfter = $createdFrom->isAfter($today);
            
            if($isBefore){
                $data = $this->getPreviousFinalProfitLossDailyCache($request->created_from);
                if($data){
                    $transactions = json_decode(json_encode($data->transactions), true);
                    $totalUserBalance = $data->totalUserBalance;
                    $currencyProfit = $data->currencyProfit;
                    $totalProfit = $data->totalProfit;
                    $currencies = $data->currencies;
                    $currencies_all = $currencies;
                    if($request->get("currency_id")){
                        $ids = $request->get("currency_id");

                        $transactions = array_filter($transactions, function ($transaction) use ($ids) {
                            return in_array($transaction['currency_id'], $ids);
                        });

                        $currencyProfit = 0;
                        foreach($transactions as $transaction){
                            $currencyProfit += $transaction['currency_total'];
                        } 
                        $totalProfit = getAmount($currencyProfit - $totalUserBalance);

                    }

                    return view('admin.pos.final_profit', compact('pageTitle', 'transactions', 'totalUserBalance', 'currencyProfit', 'totalProfit', 'currencies', 'request', 'currencies_all'));
                }
                $notify[] = ['error', 'Date not found'];
                return to_route('admin.pos.final_profit')->withNotify($notify);
            }
            if($isAfter){
                $notify[] = ['error', 'Invalid date range'];
                return to_route('admin.pos.final_profit')->withNotify($notify);
            }

            if ($request->submit_button == 'DOWNLOAD') {
                $title = Carbon::now()->format('Ymd') . '_final_profit_report.xlsx';
                return Excel::download(new FinalProfitExport($request), $title);
            }

            // Build query with filters
            // """ Only approved will come as its final profit report """
            $exchangesQuery = Exchange::where('status', 1)->with(['sendCurrency', 'receivedCurrency']);

            if ($request->transaction_type) {
                $exchangesQuery->where('transaction_type', $request->transaction_type);
            }
            $exchangesQuery->whereBetween('updated_at', [
                date('Y-m-d 00:00:00', strtotime($request->created_from)),
                date('Y-m-d 23:59:59', strtotime($request->created_from))
            ]);

            // Fetch once (no N+1 queries)
            $exchanges = $exchangesQuery->get();

            // Prepare calculations
            $transactions = [];
            $currencyProfit = 0;
            $searchingDay = Carbon::parse($request->created_from)->format('Ymd');
            $lastDay = Carbon::parse($request->created_from)->subDay()->format('Ymd');

            foreach ($currencies as $currency) {
                // where('business_day', '<=', $searchingDay) → look for logs on or before that day.
                // orderBy('business_day', 'desc') → sort from latest to oldest.
                // first() → return the closest one.
                $reserved = CurrencyReservedLog::where('currency_id', $currency->id)
                    ->where('business_day', '<=', $searchingDay)
                    ->orderBy('business_day', 'desc')
                    ->pluck('reserved')->first();

                // If found no data in currency_reserved_logs table for this currency
                // then reserved will be the at actual of currency reserved
                if (! $reserved) {
                    $reserved = $currency->reserve;
                }
                $reserved = getAmount($reserved);
                // End reserved calculation

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
                    'business_day' => Carbon::parse($request->created_from)->format('Ymd')
                ])->first();

                if (! $currencyProfitData) {
                    $currencyProfitData = new FinalProfit();
                }

                // now saved data in to db table
                $currencyProfitData->currency_id = $transaction['currency_id'];
                $currencyProfitData->currency = $currency;
                $currencyProfitData->business_day = Carbon::parse($request->created_from)->format('Ymd');
                $currencyProfitData->cs_sent_avg_rate = $transaction['customer_avg_sent_rate'];
                $currencyProfitData->currency_reserved = $transaction['currency_reserved'];
                $currencyProfitData->currency_total = $transaction['currency_total'];
                $currencyProfitData->all_currency_total = $currencyProfit;
                $currencyProfitData->all_active_users_balance = $totalUserBalance;
                $currencyProfitData->total_profit = $totalProfit;
                $currencyProfitData->save();
            }
            // dd($transactions, $totalUserBalance, $currencyProfit, $totalProfit, $currencies, $currencies_all);
        }

        return view('admin.pos.final_profit', compact('pageTitle', 'transactions', 'totalUserBalance', 'currencyProfit', 'totalProfit', 'currencies', 'request', 'currencies_all'));
    }
    public function getdailyProfit(Request $request)
    {
        $pageTitle = 'POS DAILY PROFIT RESULT';
        $transactions = [];
        $totalUserBalance = 0;
        $totalProfit = 0;
        $currencyProfit = 0;
        $currencies_all = Currency::whereNotIn('name', ['A/C BALANCE'])->orderBy('created_at')->get();
        $currencies_query = Currency::whereNotIn('name', ['A/C BALANCE'])->orderBy('created_at');
        if($request->get('currency_id')){
            $currency_ids = $request->currency_id;
            $currencies_query = $currencies_query->whereIn('id', $currency_ids);
        }
        $currencies = $currencies_query->get();
        if ($request->created_from) {
            $createdFrom = Carbon::parse($request->created_from);
            $today       = Carbon::today();

            $isBefore = $createdFrom->isBefore($today);
            $isAfter = $createdFrom->isAfter($today);
            
            if($isBefore){

                $data = $this->getPreviousDailyProfitLossDailyCache($request->created_from);
                if($data){
                    $transactions = json_decode(json_encode($data->transactions), true);
                    $totalUserBalance = $data->totalUserBalance;
                    $currencyProfit = $data->currencyProfit;
                    $totalProfit = $data->totalProfit;
                    $currencies = $data->currencies;
                    $currencies_all = $currencies;
                    if($request->get("currency_id")){
                        $ids = $request->get("currency_id");

                        $transactions = array_filter($transactions, function ($transaction) use ($ids) {
                            return in_array($transaction['currency_id'], $ids);
                        });

                        $currencyProfit = 0;
                        foreach($transactions as $transaction){
                            $currencyProfit += $transaction['currency_total'];
                        } 
                        $totalProfit = getAmount($currencyProfit - $totalUserBalance);

                    }

                    return view('admin.pos.daily_profit', compact('pageTitle', 'transactions', 'totalUserBalance', 'currencyProfit', 'totalProfit', 'currencies', 'request', 'currencies_all'));
                }
                $notify[] = ['error', 'Date not found'];
                return to_route('admin.pos.daily_profit')->withNotify($notify);
            }
            if($isAfter){
                $notify[] = ['error', 'Invalid date range'];
                return to_route('admin.pos.daily_profit')->withNotify($notify);
            }

            if ($request->submit_button == 'DOWNLOAD') {
                $title = Carbon::now()->format('Ymd') . '_final_profit_report.xlsx';
                return Excel::download(new DailyProfitExport($request), $title);
            }

            // Build query with filters
            // """ Only approved will come as its final profit report """
            $exchangesQuery = Exchange::where('status', 1)->with(['sendCurrency', 'receivedCurrency']);

            if ($request->transaction_type) {
                $exchangesQuery->where('transaction_type', $request->transaction_type);
            }
            $exchangesQuery->whereBetween('updated_at', [
                date('Y-m-d 00:00:00', strtotime($request->created_from)),
                date('Y-m-d 23:59:59', strtotime($request->created_from))
            ]);

            // Fetch once (no N+1 queries)
            $exchanges = $exchangesQuery->get();

            // Prepare calculations
            $transactions = [];
            $currencyProfit = 0;
            $searchingDay = Carbon::parse($request->created_from)->format('Ymd');
            $lastDay = Carbon::parse($request->created_from)->subDay()->format('Ymd');

            foreach ($currencies as $currency) {
                // where('business_day', '<=', $searchingDay) → look for logs on or before that day.
                // orderBy('business_day', 'desc') → sort from latest to oldest.
                // first() → return the closest one.
                $reserved = CurrencyReservedLog::where('currency_id', $currency->id)
                    ->where('business_day', '<=', $searchingDay)
                    ->orderBy('business_day', 'desc')
                    ->pluck('reserved')->first();

                // If found no data in currency_reserved_logs table for this currency
                // then reserved will be the at actual of currency reserved
                if (! $reserved) {
                    $reserved = $currency->reserve;
                }
                $reserved = getAmount($reserved);
                // End reserved calculation

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
                    'buy_at' => getAmount($buy_at),
                    'currency_total' => getAmount($currency_total),
                ];
            }

            $totalUserBalance = User::where('status', 1)->sum('balance');
            $totalUserBalance = getAmount($totalUserBalance);
            $currencyProfit = getAmount($currencyProfit);
            $totalProfit = getAmount($currencyProfit - $totalUserBalance);

            // dd($transactions, $totalUserBalance, $currencyProfit, $totalProfit, $currencies, $currencies_all);
        }

        return view('admin.pos.daily_profit', compact('pageTitle', 'transactions', 'totalUserBalance', 'currencyProfit', 'totalProfit', 'currencies', 'request', 'currencies_all'));

    }
}
