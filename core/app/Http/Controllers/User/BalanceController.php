<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\BalanceStatement;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserTransactionExport;
use App\Exports\BalanceStatementExport;
use Illuminate\Pagination\LengthAwarePaginator;

class BalanceController extends Controller
{
    public function get_statements()
    {
        $request = request();
        $statements_query = auth()->user()->balanceStatement()->with('exchange')
            ->with('admin')
            ->latest();
        if ($request->exchange_id) {
            $statements_query = $statements_query->whereHas('exchange', function ($query) use ($request) {
                $query->where('exchange_id', $request->exchange_id);
            });
        }
        if ($request->transaction_type) {
            $statements_query = $statements_query->whereHas('exchange', function ($query) use ($request) {
                $query->where('transaction_type', $request->transaction_type);
            });
        }
        if ($request->send_currency_id) {
            $statements_query = $statements_query->whereHas('exchange', function ($query) use ($request) {
                $query->whereIn('send_currency_id', $request->send_currency_id);
            });
        }
        if ($request->receive_currency_id) {
            $statements_query = $statements_query->whereHas('exchange', function ($query) use ($request) {
                $query->whereIn('receive_currency_id', $request->receive_currency_id);
            });
        }
        if ($request->created_from && $request->created_to) {
            $statements_query = $statements_query->whereHas('exchange', function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($request->created_from)),
                    date('Y-m-d 23:59:59', strtotime($request->created_to))
                ]);
            });
        }
        $statements = $statements_query->paginate(getPaginate(request()->itemsPerPage));
        return $statements;
    }
    public function balanceStatement(Request $request)
    {
        $pageTitle = "Balance Statement";
        $statements = $this->get_statements();

        $currencies = Currency::all();
        return view('Template::user.balance.list', compact('statements', 'request', 'currencies', 'pageTitle'));
    }
    public function balanceDownload()
    {
        $statements = $this->get_statements();
        $title = Carbon::now()->format('Ymd') . "_balance_statement_.xlsx";
        return Excel::download(new BalanceStatementExport($statements), $title);
    }
}
