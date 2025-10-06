<?php

namespace App\Exports;

use App\Models\Currency;
use App\Models\Exchange;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class UserTransactionExport implements FromView
{
    protected $scope;

    public function __construct($scope)
    {
        $this->scope = $scope;
    }

    public function view(): View
    {
        $transactions = Exchange::{$this->scope}()
                    ->where('user_id', auth()->id())
                    ->with(['sendCurrency', 'receivedCurrency'])->get();

        return view('exports.user_transactions', [
            'transactions' => $transactions
        ]);
    }
}
