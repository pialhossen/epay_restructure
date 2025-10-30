<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\BalanceStatement;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserTransactionExport;
use App\Exports\BalanceStatementExport;
use Illuminate\Pagination\LengthAwarePaginator;

class BalanceController extends Controller
{
    public function balanceStatement()
    {
        $pageTitle = "Balance Statement";
        $page = request()->get('page', 1);
        $months = BalanceStatement::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
        if ($months->isEmpty()) {
            return collect();
        }
        $currentMonth = $months->get($page - 1);
        $statements = auth()->user()->balanceStatement()->whereYear('created_at', $currentMonth->year)
            ->with('admin')
            ->whereMonth('created_at', $currentMonth->month)
            ->get();
        $paginator = new LengthAwarePaginator(
            $statements,
            $months->count(),
            1,
            $page,
            ['path' => url()->current()]
        );
        return view('Template::user.balance.list', compact('statements', 'paginator', 'pageTitle'));
    }
    public function balanceDownload()
    {
        $page = request()->get('page', 1);
        $months = auth()->user()->balanceStatement()->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
        if ($months->isEmpty()) {
            return view('exports.user_balance_statement', [
                'transactions' => []
            ]);
        }
        $currentMonth = $months->get($page - 1);
        $selectedMonthName = Carbon::create()->month($currentMonth->month)->format('F');
        $statements = auth()->user()->balanceStatement()->whereYear('created_at', $currentMonth->year)
            ->with('admin')
            ->whereMonth('created_at', $currentMonth->month)
            ->get();

        $selectedYear = isset($transactions[0]) ? Carbon::parse($statements[0]->created_at)->year : now()->year;

        $title = Carbon::now()->format('Ymd') . "_{$selectedMonthName}_{$selectedYear}_balance_statement_.xlsx";
        return Excel::download(new BalanceStatementExport($statements), $title);
    }
    public function setup_balance_statement(){
        $all_users = User::all();
        foreach($all_users as $user){
            $user->balanceStatement()->create([
                "amount" => $user->balance,
                "via" => "Inital Balance",
                "admin_id" => null,
            ]);
        }
        return ["status" => "success", "message" => "Inital balance statement setup complete"];
    }
}
