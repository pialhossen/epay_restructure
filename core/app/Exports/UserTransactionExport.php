<?php

namespace App\Exports;

use App\Models\Exchange;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class UserTransactionExport implements FromView
{
    protected $scope;
    protected $request;

    public function __construct($scope = 'list', $request = null)
    {
        $this->scope = $scope;
        $this->request = $request;
    }

    public function view(): View
    {
        $scope = $this->scope ?: 'list';
        $allowedScopes = ['list', 'initiated', 'pending', 'approved', 'canceled', 'refunded', 'hold', 'processing'];
        if (!in_array($scope, $allowedScopes)) {
            $scope = 'list';
        }

        $query = Exchange::$scope()->where('user_id', auth()->id());

        $request = $this->request ?? request();

        if ($request->exchange_id) {
            $query->where('exchange_id', $request->exchange_id);
        }

        if ($request->transaction_type) {
            $types = array_filter((array) $request->transaction_type);
            if (!empty($types)) {
                $query->whereIn('transaction_type', $types);
            }
        }

        if ($request->send_currency_id) {
            $sendIds = array_filter((array) $request->send_currency_id);
            if (!empty($sendIds)) {
                $query->whereIn('send_currency_id', $sendIds);
            }
        }

        if ($request->receive_currency_id) {
            $receiveIds = array_filter((array) $request->receive_currency_id);
            if (!empty($receiveIds)) {
                $query->whereIn('receive_currency_id', $receiveIds);
            }
        }

        if ($request->created_from && $request->created_to) {
            $query->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($request->created_from)),
                date('Y-m-d 23:59:59', strtotime($request->created_to))
            ]);
        } elseif ($request->created_from) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($request->created_from)));
        } elseif ($request->created_to) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($request->created_to)));
        }

        if ($request->query('sort')) {
            [$column, $direction] = explode(':', $request->query('sort'));
            $query->orderBy($column, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $transactions = $query->with(['sendCurrency', 'receivedCurrency'])->get();

        return view('exports.user_transactions', [
            'transactions' => $transactions
        ]);
    }
}
