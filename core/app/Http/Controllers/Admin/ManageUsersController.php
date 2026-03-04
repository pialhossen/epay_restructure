<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\UserNotificationSender;
use App\Models\Exchange;
use App\Models\NotificationLog;
use App\Models\Referral;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ManageUsersController extends Controller
{
    private $user;
    public function __construct()
    {
        $this->user = auth()->guard('admin')->user();
        $this->check_permission('View - Manage Users');
    }

    public static function checkPermission($user, $scope)
    {
        if ($user->id == 1) {
            return true;
        }
        if (($scope == 'index' || $scope == 'All Users') && $user->can('View - All Users')) {
            return true;
        }
        if (($scope == 'active' || $scope == 'Active Users') && $user->can('View - Active Users')) {
            return true;
        }
        if (($scope == 'banned' || $scope == 'Banned Users') && $user->can('View - Banned Users')) {
            return true;
        }
        if (($scope == 'emailUnverified' || $scope == 'Email Unverified') && $user->can('View - Email Unverified')) {
            return true;
        }
        if (($scope == 'mobileUnverified' || $scope == 'Mobile Unverified') && $user->can('View - Mobile Unverified')) {
            return true;
        }
        if (($scope == 'kycUnverified' || $scope == 'KYC Unverified') && $user->can('View - KYC Unverified')) {
            return true;
        }
        if (($scope == 'kycPending' || $scope == 'KYC Pending') && $user->can('View - KYC Pending')) {
            return true;
        }
        if (($scope == 'withBalance' || $scope == 'With Balance') && $user->can('View - With Balance')) {
            return true;
        }
        if (($scope == 'showNotification' || $scope == 'Send Notification') && $user->can('View - Send Notification')) {
            return true;
        }
        if ($scope == 'Block Data Alert' && $user->can('View - Block Data Alert')) {
            return true;
        }

        return false;
    }

    public function allUsers()
    {
        if (!$this->checkPermission($this->user, 'index')) {
            abort(403);
        }
        $pageTitle = 'All Users';
        $users = $this->userData();

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function activeUsers()
    {
        if (!$this->checkPermission($this->user, 'active')) {
            abort(403);
        }
        $pageTitle = 'Active Users';
        $users = $this->userData('active');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function bannedUsers()
    {
        if (!$this->checkPermission($this->user, 'banned')) {
            abort(403);
        }
        $pageTitle = 'Banned Users';
        $users = $this->userData('banned');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function emailUnverifiedUsers()
    {
        if (!$this->checkPermission($this->user, 'emailUnverified')) {
            abort(403);
        }
        $pageTitle = 'Email Unverified Users';
        $users = $this->userData('emailUnverified');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function kycUnverifiedUsers()
    {
        if (!$this->checkPermission($this->user, 'kycUnverified')) {
            abort(403);
        }
        $pageTitle = 'KYC Unverified Users';
        $users = $this->userData('kycUnverified');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function kycPendingUsers()
    {
        if (!$this->checkPermission($this->user, 'kycPending')) {
            abort(403);
        }
        $pageTitle = 'KYC Pending Users';
        $users = $this->userData('kycPending');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function emailVerifiedUsers()
    {
        $pageTitle = 'Email Verified Users';
        $users = $this->userData('emailVerified');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function mobileUnverifiedUsers()
    {
        $pageTitle = 'Mobile Unverified Users';
        $users = $this->userData('mobileUnverified');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function mobileVerifiedUsers()
    {
        $pageTitle = 'Mobile Verified Users';
        $users = $this->userData('mobileVerified');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    public function usersWithBalance()
    {
        if (!$this->checkPermission($this->user, 'withBalance')) {
            abort(403);
        }
        $pageTitle = 'Users with Balance';
        $users = $this->userData('withBalance');

        return view('admin.users.list', compact('pageTitle', 'users'));
    }

    protected function userData($scope = null, $isPaginate = true, $query = false)
    {
        $request = request();
        // dd($request->query());
        if ($scope && method_exists(User::class, $scope)) {
            $users = User::$scope();
        } else {
            $users = User::withCount('approvedExchanges');
            if ($scope == 'active') {
                $users = $users->where('status', Status::USER_ACTIVE)->where('ev', Status::VERIFIED)->where('sv', Status::VERIFIED);
            } elseif ($scope == 'kycUnverified') {
                $users = $users->where('status', Status::USER_ACTIVE)->where('kv', 0);
            } elseif ($scope == 'banned') {
                $users = $users->where('status', 0);
            } elseif ($scope == 'emailUnverified') {
                $users = $users->where('status', Status::USER_ACTIVE)->where('ev', 0);
            } elseif ($scope == 'mobileUnverified') {
                $users = $users->where('status', Status::USER_ACTIVE)->where('sv', 0);
            } elseif ($scope == 'kycPending') {
                $users = $users->where('status', Status::USER_ACTIVE)->where('kv', 2);
            } elseif ($scope == 'withBalance') {
                $users = $users->where('status', Status::USER_ACTIVE)->where('balance', '>', 0);
            }


        }
        if (isset($request->mobile) && $request->mobile) {
            $users = $users->where('mobile', $request->mobile);
        }
        if (isset($request->email) && $request->email) {
            $users = $users->where('email', $request->email);
        }
        if (isset($request->username) && $request->username) {
            $users = $users->where('username', $request->username);
        }
        if (isset($request->firstname) && $request->firstname) {
            $users = $users->where('firstname', 'like', '%' . $request->firstname . '%');
        }
        if (isset($request->lastname) && $request->lastname) {
            $users = $users->where('lastname', 'like', '%' . $request->lastname . '%');
        }
        if (isset($request->address) && $request->address) {
            $users = $users->where('address', 'like', '%' . $request->address . '%');
        }
        if (request()->query('sort')) {
            [$column, $direction] = explode(':', request()->query('sort'));
            if (str_contains(request()->query("sort"), 'completed_orders')) {
                if ($direction == "asc") {
                    $users = $users->orderBy('approved_exchanges_count', 'asc');
                } else {
                    $users = $users->orderBy('approved_exchanges_count', 'desc');
                }
            } else {
                $users = $users->orderBy($column, $direction);
            }
        }
        if($query){
            return $users;
        }
        if($isPaginate){
            $users_data = $users->orderBy('id', 'desc')
                ->paginate(getPaginate($request->itemsPerPage ? $request->itemsPerPage : null));
        } else {
            $users_data = $users->orderBy('id', 'desc')->get();
        }

        return $users_data;
    }








    public function detail($id)
    {
        $this->check_permission('View - Users Details');
        $user = User::findOrFail($id);
        $pageTitle = 'User Detail - ' . $user->username;

        $totalWithdrawals = Withdrawal::where('user_id', $user->id)->approved()->sum('amount');
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $totalTicket = SupportTicket::where('user_id', $user->id)->count();
        $totalExchange = Exchange::list()->where('user_id', $user->id)->count();
        $commission_levels = Referral::all();

        return view('admin.users.detail', compact('pageTitle', 'user', 'totalWithdrawals', 'countries', 'totalTicket', 'totalExchange', 'commission_levels'));
    }

    public function kycDetails($id)
    {
        $pageTitle = 'KYC Details';
        $user = User::findOrFail($id);

        return view('admin.users.kyc_detail', compact('pageTitle', 'user'));
    }

    public function kycApprove($id)
    {
        $user = User::findOrFail($id);
        $user->kv = Status::KYC_VERIFIED;
        $user->save();

        notify($user, 'KYC_APPROVE', []);

        $notify[] = ['success', 'KYC approved successfully'];

        return to_route('admin.users.kyc.pending')->withNotify($notify);
    }

    public function kycReject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required',
        ]);
        $user = User::findOrFail($id);
        $user->kv = Status::KYC_UNVERIFIED;
        $user->kyc_rejection_reason = $request->reason;
        $user->save();

        notify($user, 'KYC_REJECT', [
            'reason' => $request->reason,
        ]);

        $notify[] = ['success', 'KYC rejected successfully'];

        return to_route('admin.users.kyc.pending')->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $this->check_permission('Update - Users');

        $user = User::findOrFail($id);
        $countryData = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryArray = (array) $countryData;
        $countries = implode(',', array_keys($countryArray));

        $countryCode = $request->country;
        $country = $countryData->$countryCode->country;
        $dialCode = $countryData->$countryCode->dial_code;

        $request->validate([
            'firstname' => 'required|string|max:40',
            'lastname' => 'required|string|max:40',
            'email' => 'required|email|string|max:40|unique:users,email,' . $user->id,
            'mobile' => 'required|string|max:40',
            'country' => 'required|in:' . $countries,
        ]);

        $exists = User::where('mobile', $request->mobile)->where('dial_code', $dialCode)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            $notify[] = ['error', 'The mobile number already exists.'];

            return back()->withNotify($notify);
        }

        $user->mobile = $request->mobile;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->commission_level = $request->commission_level;

        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country_name = @$country;
        $user->dial_code = $dialCode;
        $user->country_code = $countryCode;
        $user->is_exchange_rate_permission = $request->has('is_exchange_rate_permission') ? 1 : 0;
        $user->fb_link = $request->facebook_link;
        $user->is_fb_verify = $request->has('is_fb_verify') ? 1 : 0;

        $user->ev = $request->ev ? Status::VERIFIED : Status::UNVERIFIED;
        $user->sv = $request->sv ? Status::VERIFIED : Status::UNVERIFIED;
        $user->ts = $request->ts ? Status::ENABLE : Status::DISABLE;
        $user->neg_bal_allowed = $request->neg_bal_allowed ? Status::ENABLE : Status::DISABLE;
        if (!$request->kv) {
            $user->kv = Status::KYC_UNVERIFIED;
            if ($user->kyc_data) {
                foreach ($user->kyc_data as $kycData) {
                    if ($kycData->type == 'file') {
                        fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
                    }
                }
            }
            $user->kyc_data = null;
        } else {
            $user->kv = Status::KYC_VERIFIED;
        }
        $user->save();

        $notify[] = ['success', 'User details updated successfully'];

        return back()->withNotify($notify);
    }

    public function addSubBalance(Request $request, $id)
    {
        $this->check_permission('Update - addSubBalance');
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'act' => 'required|in:add,sub',
            'remark' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($id);
        $amount = $request->amount;
        $trx = getTrx();

        $transaction = new Transaction;

        if ($request->act == 'add') {
            $user->balanceStatement()->create([
                "amount" => $amount,
                "via" => "Balance Add",
                "admin_id" => auth("admin")->id(),
            ]);
            $user->balance += $amount;

            $transaction->trx_type = '+';
            $transaction->remark = 'balance_add';

            $notifyTemplate = 'BAL_ADD';

            $notify[] = ['success', 'Balance added successfully'];
        } else {
            if ($amount > $user->balance) {
                $notify[] = ['error', $user->username . ' doesn\'t have sufficient balance.'];

                return back()->withNotify($notify);
            }
            $user->balanceStatement()->create([
                "amount" => -$amount,
                "via" => "Balance Subtract",
                "admin_id" => auth("admin")->id(),
            ]);
            $user->balance -= $amount;

            $transaction->trx_type = '-';
            $transaction->remark = 'balance_subtract';

            $notifyTemplate = 'BAL_SUB';
            $notify[] = ['success', 'Balance subtracted successfully'];
        }

        $user->save();

        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->post_balance = $user->balance;
        $transaction->charge = 0;
        $transaction->trx = $trx;
        $transaction->details = $request->remark;
        $transaction->save();

        notify($user, $notifyTemplate, [
            'trx' => $trx,
            'amount' => showAmount($amount, currencyFormat: false),
            'remark' => $request->remark,
            'post_balance' => showAmount($user->balance, currencyFormat: false),
        ]);

        return back()->withNotify($notify);
    }

    public function login($id)
    {
        $this->check_permission('View - Login As User');
        $user = \App\Models\User::find($id);
        if ($user) {
            Auth::guard('web')->login($user);
            Log::info("Manual login as: " . json_encode(Auth::user()));
        } else {
            Log::error("User with ID {$id} not found.");
        }
        return to_route('user.home');
    }

    public function status(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->status == Status::USER_ACTIVE) {
            $request->validate([
                'reason' => 'required|string|max:255',
            ]);
            $user->status = Status::USER_BAN;
            $user->ban_reason = $request->reason;
            $notify[] = ['success', 'User banned successfully'];
        } else {
            $user->status = Status::USER_ACTIVE;
            $user->ban_reason = null;
            $notify[] = ['success', 'User unbanned successfully'];
        }
        $user->save();

        return back()->withNotify($notify);
    }

    public function showNotificationSingleForm($id)
    {

        $user = User::findOrFail($id);
        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];

            return to_route('admin.users.detail', $user->id)->withNotify($notify);
        }
        $pageTitle = 'Send Notification to ' . $user->username;

        return view('admin.users.notification_single', compact('pageTitle', 'user'));
    }

    public function sendNotificationSingle(Request $request, $id)
    {
        $request->validate([
            'message' => 'required',
            'via' => 'required|in:email,sms,push',
            'subject' => 'required_if:via,email,push',
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];

            return to_route('admin.dashboard')->withNotify($notify);
        }

        return (new UserNotificationSender)->notificationToSingle($request, $id);
    }

    public function showNotificationAllForm()
    {
        if (!$this->checkPermission($this->user, 'showNotification')) {
            abort(403);
        }
        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];

            return to_route('admin.dashboard')->withNotify($notify);
        }

        $notifyToUser = User::notifyToUser();
        $users = User::active()->count();
        $pageTitle = 'Notification to Verified Users';

        if (session()->has('SEND_NOTIFICATION') && !request()->email_sent) {
            session()->forget('SEND_NOTIFICATION');
        }

        return view('admin.users.notification_all', compact('pageTitle', 'users', 'notifyToUser'));
    }

    public function sendNotificationAll(Request $request)
    {
        $request->validate([
            'via' => 'required|in:email,sms,push',
            'message' => 'required',
            'subject' => 'required_if:via,email,push',
            'being_sent_to' => 'required',
            'number_of_top_deposited_user' => 'required_if:being_sent_to,topDepositedUsers|integer|gte:0',
            'number_of_days' => 'required_if:being_sent_to,notLoginUsers|integer|gte:0',
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ], [
            'number_of_days.required_if' => 'Number of days field is required',
            'number_of_top_deposited_user.required_if' => 'Number of top deposited user field is required',
        ]);

        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        return (new UserNotificationSender)->notificationToAll($request);
    }
    public function sendSingleNotification(Request $request){
        try {
            $user = User::find($request->user_id);
            notify($user, 'DEFAULT', [
                'subject' => $request->subject,
                'message' => $request->message,
            ], [$request->via], pushImage: $request->imageUrl);
            return ["status" => "success", "message" => "notification send success"];
        } catch (\Throwable $th) {
            logger()->error($th->getMessage());
            return ["status" => "error", "message" => "notification send failed"];
        }
    }

    public function countBySegment($methodName)
    {
        return User::active()->$methodName()->count();
    }

    public function list()
    {
        $query = User::active();
        if (request()->search) {
            $query->where(function ($q) {
                $search = strtolower(request()->search);
                $q->where('email', 'like', "%$search%")
                    ->orWhere('firstname', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%")
                    ->orWhere('mobile', 'like', "%$search%")
                    ->orWhere('username', 'like', "%$search%");

            });
        }
        $users = $query->orderBy('id', 'desc')->paginate(getPaginate());

        return response()->json([
            'success' => true,
            'users' => $users,
            'more' => $users->hasMorePages(),
        ]);
    }

    public function notificationLog($id)
    {
        $user = User::findOrFail($id);
        $pageTitle = 'Notifications Sent to ' . $user->username;
        $logs = NotificationLog::where('user_id', $id)->with('user')->orderBy('id', 'desc')->paginate(getPaginate());

        return view('admin.reports.notification_history', compact('pageTitle', 'logs', 'user'));
    }
    public function advance_search_users(Request $request){
        $field = $request->field;
        $value = $request->value;
        $current_url = $request->current_url;
        $url_array = explode('/', $current_url);
        $last_segment = $url_array[count($url_array) - 1];

        $users_query = $this->userData(scope: $last_segment,isPaginate: false, query: true);
        $users = $users_query->where($field,'like',"%$value%")->limit(100)->get();
        $data = $users->pluck($field)->toArray();
        return ["status" => "success", "data" => $data];
    }
}
