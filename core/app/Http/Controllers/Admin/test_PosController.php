<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constants\Status;
use App\Models\Currency;
use App\Models\Exchange;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;

class PosController extends Controller
{
    public function index(Request $request)
    {
        try {
            $exchanges = Exchange::with('user', 'sendCurrency', 'receivedCurrency');

            if($request->exchange_id){
                $exchanges = $exchanges->where('exchange_id', $request->exchange_id);
            }
            if($request->status){
                $exchanges = $exchanges->where('status', $request->status);
            }
            if ($request->email) {
                $exchanges = $exchanges->whereHas('user', function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('email', $request->email)
                        ->orWhere('username', $request->email);
                    });
                });
            }
            if($request->transaction_type){
                $exchanges = $exchanges->where('transaction_type', $request->transaction_type);
            }
            if($request->send_currency_id){
                $exchanges = $exchanges->where('send_currency_id', $request->send_currency_id);
            }
            if($request->receive_currency_id){
                $exchanges = $exchanges->where('receive_currency_id', $request->receive_currency);
            }
            if ($request->created_from && $request->created_to) {
                $exchanges = $exchanges->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($request->created_from)),
                    date('Y-m-d 23:59:59', strtotime($request->created_to))
                ]);
            } else {
                // only get the last 7 day data


                // $exchanges = $exchanges->where('created_at', '>=', Carbon::now()->subDays(7)->format('Y-m-d H:i:s'));

                // $exchanges = $exchanges->whereBetween('created_at', [
                //     date('Y-m-d 00:00:00', strtotime('-7 day')),
                //     date('Y-m-d 23:59:59', strtotime('-7 day'))
                // ]);
            }

            $exchanges = $exchanges->get();

            // calculate pos
            $transactions = [];
$currencies = Currency::all();

foreach ($exchanges as $exchange) {
    $receiveCurrency = $exchange->receivedCurrency?->name ?? '';
    $sendCurrency    = $exchange->sendCurrency?->name ?? '';

    $hidden_charge = $exchange->hidden_charge_fixed ?? ($exchange->receiving_amount * ($exchange->hidden_charge_percent / 100));

    // Initialize receiveCurrency entry if not set
    if (!isset($transactions[$receiveCurrency])) {
        $transactions[$receiveCurrency] = [
            'received_amount' => 0,
            'received_count' => 0,
            'sent_amount' => 0,
            'sent_count' => 0,
        ];
    }

    // Initialize sendCurrency entry if not set
    if (!isset($transactions[$sendCurrency])) {
        $transactions[$sendCurrency] = [
            'received_amount' => 0,
            'received_count' => 0,
            'sent_amount' => 0,
            'sent_count' => 0,
        ];
    }

    // Accumulate receiving currency values
    $transactions[$receiveCurrency]['received_amount'] += $exchange->receiving_amount;
    $transactions[$receiveCurrency]['received_count'] += 1;

    // Accumulate sending currency values (including hidden charge)
    $transactions[$sendCurrency]['sent_amount'] += $exchange->sending_amount + $hidden_charge;
    $transactions[$sendCurrency]['sent_count'] += 1;
}

            $exchanges = $exchanges->get();

            $pageTitle = 'POS';
        } catch (Exception $ex) {
            $notify[] = ['error', $ex];
            return to_route('admin.pos.index', 'list')->withNotify($notify);
        }
        $columns = ['exchange_id', 'user_id', 'receive_currency_id', 'receiving_amount', 'send_currency_id', 'sending_amount', 'status'];


        return view('admin.pos.index', compact('pageTitle', 'exchanges', 'transactions', 'columns', 'currencies', 'request'));
    }

    public function exportExchanges(Request $request)
    {
        $exportColumns = $request->columns;
        $query = Exchange::with(['user', 'sendCurrency', 'receivedCurrency']);
        if ($request->has('scope')) {
            switch ($request->scope) {
                case 'pending':
                    $query->where('status', Status::EXCHANGE_PENDING);
                    break;
                case 'approved':
                    $query->where('status', Status::EXCHANGE_APPROVED);
                    break;
                case 'hold':
                    $query->where('status', Status::EXCHANGE_HOLD);
                    break;
                case 'processing':
                    $query->where('status', Status::EXCHANGE_PROCESSING);
                    break;
                case 'refunded':
                    $query->where('status', Status::EXCHANGE_REFUND);
                    break;
                case 'canceled':
                    $query->where('status', Status::EXCHANGE_CANCEL);
                    break;
                default:
                    break;
            }
        }
        $orderBy = $request->order_by ?? 'desc';
        $query->orderBy('created_at', $orderBy);

        $exchanges = $query->take($request->export_item)->get();

        $data = $exchanges->map(function ($exchange) use ($exportColumns) {
            $row = [];
            foreach ($exportColumns as $column) {
                switch ($column) {
                    case 'user_id':
                        $row['User Fullname'] = optional($exchange->user)->fullname;
                        $row['User Username'] = optional($exchange->user)->username;
                        break;
                    case 'send_currency_id':
                        $row['Send Currency'] = optional($exchange->sendCurrency)->name;
                        break;
                    case 'receive_currency_id':
                        $row['Received Currency'] = optional($exchange->receivedCurrency)->name;
                        break;
                    case 'sending_amount':
                        $row['Sending Amount'] = number_format($exchange->sending_amount, $exchange->sendCurrency->show_number_after_decimal);
                        break;
                    case 'receiving_amount':
                        $row['Receiving Amount'] = number_format($exchange->receiving_amount, $exchange->receivedCurrency ? $exchange->receivedCurrency->show_number_after_decimal : 2);
                        break;
                    case 'status':
                        if ($exchange->status == Status::EXCHANGE_INITIAL) {
                            $row['Status'] = 'Initiated';
                        } elseif ($exchange->status == Status::EXCHANGE_APPROVED) {
                            $row['Status'] = 'Approved';
                        } elseif ($exchange->status == Status::EXCHANGE_PENDING) {
                            $row['Status'] = 'Pending';
                        } elseif ($exchange->status == Status::EXCHANGE_HOLD) {
                            $row['Status'] = 'Hold';
                        } elseif ($exchange->status == Status::EXCHANGE_PROCESSING) {
                            $row['Status'] = 'Processing';
                        } elseif ($exchange->status == Status::EXCHANGE_REFUND) {
                            $row['Status'] = 'Refunded';
                        } else {
                            $row['Status'] = 'Cancelled';
                        }
                        break;
                    default:
                        $row[ucwords(str_replace('_', ' ', $column))] = $exchange->$column;
                        break;
                }
            }

            return $row;
        });

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($data->first() ?? []));
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'exchange_export.csv');
    }
}
