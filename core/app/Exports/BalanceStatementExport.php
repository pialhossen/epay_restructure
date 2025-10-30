<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\BalanceStatement;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BalanceStatementExport implements FromView
{
    public $statements;
    public function __construct($statements){
        $this->statements = $statements;
    }
    public function view(): View
    {
        return view('exports.user_balance_statement', [
            'statements' => $this->statements
        ]);
    }
}
