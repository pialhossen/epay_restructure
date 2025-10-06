<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionLog;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\NotificationLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function transaction(Request $request, $userId = null)
    {
        $pageTitle = 'Transaction Logs';

        $remarks = Transaction::distinct('remark')->orderBy('remark')->get('remark');

        $transactions = Transaction::searchable(['trx', 'user:username'])->filter(['trx_type', 'remark'])->dateFilter()->orderBy('id', 'desc')->with('user');
        if ($userId) {
            $transactions = $transactions->where('user_id', $userId);
        }
        $transactions = $transactions->paginate(getPaginate());

        return view('admin.reports.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    public function exchangeDashboard(Request $request)
    {
        $pageTitle = 'Top 10 User';

        $currencies = Currency::all();

        $currencyId = $request->currency_id ?? 1; // default to ID 1

        $exchanges = Exchange::with(['user', 'sendCurrency'])
            ->where('status', 1)
            ->when($request->date, function ($query) use ($request) {
                $dateRange = explode(' - ', $request->date);
                $start = Carbon::parse($dateRange[0])->startOfDay();
                $end = Carbon::parse($dateRange[1])->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->where('send_currency_id', $currencyId) // filter by currency
            ->orderByDesc('sending_amount')
            ->paginate(10);

        return view('admin.reports.exchange_dashboard', compact('pageTitle', 'exchanges', 'currencies', 'currencyId'));
    }

    public function exchangeAllReportDashboard(Request $request)
    {
        $pageTitle = 'User Exchange Report';

        $exchanges = User::select([
            DB::raw("CONCAT(COALESCE(users.firstname, ''), ' ', COALESCE(users.lastname, '')) as full_name"),
            'users.firstname',
            'users.lastname',
            'users.username',
            'users.email',
            'users.mobile',
            DB::raw("COUNT(exchanges.id) as success_order"),
        ])
            ->leftJoin('exchanges', function ($join) {
                $join->on('users.id', '=', 'exchanges.user_id')
                    ->where('exchanges.status', 1); // Only count successful exchanges
            })
            ->when($request->filled('name'), function ($q) use ($request) {
                $q->whereRaw("CONCAT(COALESCE(users.firstname, ''), ' ', COALESCE(users.lastname, '')) LIKE ?", ["%{$request->name}%"]);
            })
            ->when($request->filled('username'), function ($q) use ($request) {
                $q->where('users.username', 'like', "%{$request->username}%");
            })
            ->when($request->filled('email'), function ($q) use ($request) {
                $q->where('users.email', 'like', "%{$request->email}%");
            })
            ->when($request->filled('mobile'), function ($q) use ($request) {
                $q->where('users.mobile', 'like', "%{$request->mobile}%");
            })
            ->groupBy('users.id', 'users.firstname', 'users.lastname', 'users.username', 'users.email', 'users.mobile')
            ->when($request->filled('success_order'), function ($q) use ($request) {
                $q->having('success_order', '>=', (int) $request->success_order);
            })
            ->orderByDesc('success_order')
            ->get();

        return view('admin.reports.exchange_report_dashboard', compact(
            'pageTitle',
            'exchanges'
        ));
    }

    public function loginHistory(Request $request)
    {
        $pageTitle = 'User Login History';
        $loginLogs = UserLogin::orderBy('id', 'desc')->searchable(['user:username'])->dateFilter()->with('user')->paginate(getPaginate());

        return view('admin.reports.logins', compact('pageTitle', 'loginLogs'));
    }

    public function loginIpHistory($ip)
    {
        $pageTitle = 'Login by - ' . $ip;
        $loginLogs = UserLogin::where('user_ip', $ip)->orderBy('id', 'desc')->with('user')->paginate(getPaginate());

        return view('admin.reports.logins', compact('pageTitle', 'loginLogs', 'ip'));
    }

    public function notificationHistory(Request $request)
    {
        $pageTitle = 'Notification History';
        $logs = NotificationLog::orderBy('id', 'desc')->searchable(['user:username'])->dateFilter()->with('user')->paginate(getPaginate());

        return view('admin.reports.notification_history', compact('pageTitle', 'logs'));
    }

    public function emailDetails($id)
    {
        $pageTitle = 'Email Details';
        $email = NotificationLog::findOrFail($id);

        return view('admin.reports.email_details', compact('pageTitle', 'email'));
    }

    public function referralCommission()
    {
        $pageTitle = 'Referral Commissions';
        $commissions = CommissionLog::orderBy('id', 'desc')->with('userTo', 'userFrom')->searchable(['userTo:username'])->paginate(getPaginate());

        return view('admin.reports.referral_commission', compact('pageTitle', 'commissions'));
    }
}
