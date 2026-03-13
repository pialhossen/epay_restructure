<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BlockLine;
use App\Models\CommissionLog;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\Form;
use App\Models\GpayCurrencyDiscountChargeModel;
use App\Models\GpayExchangeLogModel;
use App\Models\GpayHiddenChargeModel;
use App\Models\Referral;
use App\Models\User;
use App\Models\UsersModel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExchangeController extends Controller
{
    private $user;
    public function __construct()
    {
        Auth::shouldUse('admin');
        $this->user = auth()->user();
        $this->check_permission("View - Exchange Menu");
    }
    public function check_update_permission()
    {
        if ($this->user->id == 1 || $this->user->can('Update - Exchange')) {
            return 0;
        }
        abort(403);
    }
    public function check_exchnage_updated_at($exchange)
    {
        if (auth()->guard('admin')->user()->id == 1) {
            return false;
        }
        if (
            $exchange->status_at &&
            (
                $exchange->status == Status::EXCHANGE_APPROVED ||
                $exchange->status == Status::EXCHANGE_REFUND ||
                $exchange->status == Status::EXCHANGE_CANCEL
            ) &&
            $exchange->status_at->diffInMinutes(now()) >= (60 * gs('exchange_lock_time'))
        ) {
            return true;
        }
        return false;
    }
    public static function checkPermission($user, $scope)
    {
        if (($scope == 'pending' || $scope == 'Pending Exchange') && $user->can("View - Pending Exchange")) {
            return true;
        }
        if (($scope == 'hold' || $scope == 'Hold Exchange') && $user->can("View - Hold Exchange")) {
            return true;
        }
        if (($scope == 'processing' || $scope == 'Processing Exchange') && $user->can("View - Processing Exchnage")) {
            return true;
        }
        if (($scope == 'approved' || $scope == 'Approved Exchange') && $user->can("View - Approved Exchange")) {
            return true;
        }
        if (($scope == 'canceled' || $scope == 'Canceled Exchange') && $user->can("View - Canceled Exchnage")) {
            return true;
        }
        if (($scope == 'refunded' || $scope == 'Refunded Exchange') && $user->can("View - Refunded Exchange")) {
            return true;
        }
        if (($scope == 'list' || $scope == 'All Exchange') && $user->can("View - All Exchange")) {
            return true;
        }
        return false;
    }
    public function index(Request $request, $scope)
    {
        if ($this->user->id != 1) {
            if (!$this->checkPermission($this->user, $scope)) {
                abort(403);
            }
        }
        try {
            $exchanges = Exchange::$scope()->with('user', 'sendCurrency', 'receivedCurrency');

            if ($request->exchange_id) {
                $exchanges = $exchanges->where('exchange_id', $request->exchange_id);
            }
            if ($request->email) {
                $exchanges = $exchanges->whereHas('user', function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('email', $request->email)
                            ->orWhere('username', $request->email);
                    });
                });
            }
            if ($request->transaction_type) {
                $exchanges = $exchanges->whereIn('transaction_type', $request->transaction_type);
            }
            if ($request->note) {
                $exchanges = $exchanges->where('transaction_proof_data','like' ,"%$request->note%");
            }
            if ($request->send_currency_id) {
                $exchanges = $exchanges->whereIn('send_currency_id', $request->send_currency_id);
            }
            if ($request->receive_currency_id) {
                $exchanges = $exchanges->whereIn('receive_currency_id', $request->receive_currency_id);
            }
            if ($request->created_from && $request->created_to) {
                $exchanges = $exchanges->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($request->created_from)),
                    date('Y-m-d 23:59:59', strtotime($request->created_to))
                ]);
            }
            if (request()->query('sort')) {
                [$column, $direction] = explode(':', request()->query('sort'));
                $exchanges = $exchanges->orderBy($column, $direction);
            } else {
                $exchanges = $exchanges->orderBy('created_at', 'desc');
            }
            $exchanges = $exchanges->paginate(getPaginate($request->itemsPerPage ? $request->itemsPerPage : null));
            $pageTitle = formateScope($scope) . ' Exchange';
        } catch (Exception $ex) {
            $notify[] = ['error', $ex];

            return to_route('admin.exchange.list', 'list')->withNotify($notify);
        }
        $columns = ['exchange_id', 'user_id', 'receive_currency_id', 'receiving_amount', 'send_currency_id', 'sending_amount', 'status'];

        $currencies = Currency::all();
        return view('admin.exchange.list', compact('pageTitle', 'exchanges', 'columns', 'scope', 'currencies', 'request'));
    }

    public function exportExchanges(Request $request)
    {
        $exportColumns = $request->columns;
        $query = Exchange::with(['user', 'sendCurrency', 'receivedCurrency', 'updatedBy', 'orderPlaceAdmin']);
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

        if ($request->exchange_id) {
            $query = $query->where('exchange_id', $request->exchange_id);
        }
        if ($request->email) {
            $query = $query->whereHas('user', function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('email', $request->email)
                        ->orWhere('username', $request->email);
                });
            });
        }
        if ($request->transaction_type) {
            $query = $query->whereIn('transaction_type', $request->transaction_type);
        }
        if ($request->send_currency_id) {
            $query = $query->whereIn('send_currency_id', $request->send_currency_id);
        }
        if ($request->receive_currency_id) {
            $query = $query->whereIn('receive_currency_id', $request->receive_currency_id);
        }
        if ($request->created_from && $request->created_to) {
            $query = $query->whereBetween('created_at', [
                date('Y-m-d 00:00:00', strtotime($request->created_from)),
                date('Y-m-d 23:59:59', strtotime($request->created_to))
            ]);
        }
        if (request()->query('sort')) {
            [$column, $direction] = explode(':', request()->query('sort'));
            $query = $query->orderBy($column, $direction);
        } else {
            $orderBy = $request->order_by ?? 'desc';
            $query = $query->orderBy('created_at', $orderBy);
        }


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
                    case 'updated_by':
                        $row['updated_by'] = optional($exchange->updatedBy)->name;
                        break;
                    case 'placed_at':
                        $row['placed_at'] = Carbon::create($exchange->created_at)->format('d/m/y h:i:s A');
                        break;
                    case 'placed_by':
                        $row['placed_by'] = isset($exchange->orderPlaceAdmin->name) ? $exchange->orderPlaceAdmin->name : 'User';
                        break;
                    case 'aditional_field_payment_prove':
                        $text = '';
                        if ($exchange->transaction_proof_data) {
                            foreach ($exchange->transaction_proof_data as $transaction_proof) {
                                $text .= "$transaction_proof->name: $transaction_proof->value \n";
                            }
                        }

                        if ($exchange->user_data) {
                            foreach ($exchange->user_data as $user_data) {
                                $text .= "$user_data->name: $user_data->value \n";
                            }
                        }

                        $row['aditional_field_payment_prove'] = $text;
                        break;
                    case 'updated_at':
                        $row['updated_at'] = Carbon::create($exchange->updated_at)->format('d/m/y h:i:s A');
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

    public function details($id)
    {
        $this->check_permission('View - Exchange');
        $exchange = Exchange::where('id', $id)->firstOrFail();
        $exchange->is_locked = $this->check_exchnage_updated_at($exchange);
        $pageTitle = 'Exchange Details: ' . $exchange->exchange_id;
        $exchangeLog = GpayExchangeLogModel::where('exchange_id', $id)
            ->with(['adminUser:id,name'])
            ->orderBy('id', 'desc')
            ->get();

        $kyc_form = Form::where('act', 'kyc')->first();
        $user = $exchange->user;
        $user_kyc_data = [];
        if ($user->kyc_data) {
            foreach ($user->kyc_data as $kyc_data) {
                $user_kyc_data[$kyc_data->name] = $kyc_data;
            }
        }
        $userDetails = UsersModel::find($exchange->user_id);
        $charges = json_decode($exchange->charge, true);

        $userBlocked = $this->checkBlockMatch($exchange, $userDetails);


        return view('admin.exchange.details', compact('pageTitle', 'exchange', 'exchangeLog', 'userBlocked', 'charges', 'user_kyc_data'));
    }

    // User Block List
    private function checkBlockMatch(Exchange $exchange, UsersModel $user)
    {
        if (!$user && !$exchange) {
            return false;
        }

        // Normalize and filter out empty/blank user fields
        $userFields = collect([
            $user->firstname,
            $user->lastname,
            $user->username,
            $user->email,
            $user->mobile,
            $user->fb_link,
            $user->address,
        ])->filter(fn($val) => !empty(trim($val)))->map(fn($val) => Str::lower($val));

        // Normalize and filter out empty/blank exchange fields
        $exchangeFields = collect([
            $exchange->wallet_id,
            $exchange->admin_trx_no,
            $exchange->admin_feedback,
            (string) $exchange->user_proof,
        ])->filter(fn($val) => !empty(trim($val)))->map(fn($val) => Str::lower($val));

        // Handle JSON fields transaction_proof_data and user_data
        $jsonFields = collect();

        foreach (['transaction_proof_data', 'user_data'] as $jsonKey) {
            $jsonData = $exchange->{$jsonKey} ?? null;

            if (is_string($jsonData)) {
                $jsonData = json_decode($jsonData);
            }

            if ($jsonData instanceof \stdClass || is_array($jsonData)) {
                // Convert to array
                $arrayData = json_decode(json_encode($jsonData), true);

                // Flatten dot notation, keep only scalar or null values
                $flattened = collect(Arr::dot($arrayData))
                    ->filter(fn($val) => is_scalar($val) || is_null($val))
                    ->map(fn($val) => Str::lower(trim((string) $val)))
                    ->filter(fn($val) => !empty($val)); // filter empty after trim

                $jsonFields = $jsonFields->merge($flattened);

                // Additionally extract 'value' keys from list of arrays
                if (array_is_list($arrayData)) {
                    foreach ($arrayData as $entry) {
                        if (is_array($entry) && isset($entry['value']) && !empty(trim($entry['value']))) {
                            $jsonFields->push(Str::lower(trim((string) $entry['value'])));
                        }
                    }
                }
            }
        }

        // Merge JSON fields into exchangeFields
        $exchangeFields = $exchangeFields->merge($jsonFields);

        $found = false;

        // Chunk through block lines
        BlockLine::chunk(1000, function ($lines) use ($userFields, $exchangeFields, &$found) {
            foreach ($lines as $line) {
                $lineVal = Str::lower($line->data);

                // Check user fields
                foreach ($userFields as $userField) {
                    if ($userField !== '' && Str::contains($lineVal, $userField)) {
                        \Log::debug('Match found in users', ['line' => $line->data, 'matched_value' => $userField]);
                        $found = [
                            'matched_in' => 'users',
                            'matched_value' => $line->data,
                            'user_fields' => $userFields,
                        ];
                        return false; // stop chunking
                    }
                }

                // Check exchange fields
                foreach ($exchangeFields as $exchangeField) {
                    if ($exchangeField !== '' && Str::contains($lineVal, $exchangeField)) {
                        \Log::debug('Match found in exchanges', ['line' => $line->data, 'matched_value' => $exchangeField]);
                        $found = [
                            'matched_in' => 'exchanges',
                            'matched_value' => $line->data,
                            'exchange_fields' => $exchangeFields,
                        ];
                        return false; // stop chunking
                    }
                }
            }

            return !$found; // continue chunking if not found
        });

        return $found ?: false;
    }

    public function pending(Request $request, $id)
    {
        $lockKey = "exchange_update_lock_{$id}"; // unique per exchange
        $lock = Cache::lock($lockKey, 5); // 5 seconds lock timeout

        try {
            if (!$lock->get()) {
                // Another request is still running
                $notify[] = ['warning', 'Another update is already in progress. Try again shortly.'];
                return back()->withNotify($notify);
            }

            return DB::transaction(function () use ($request, $id) {

                // Lock row to prevent simultaneous DB updates
                $exchange = Exchange::with('updatedBy')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->check_update_permission();

                $user = $exchange->user;

                if ($exchange->status == Status::EXCHANGE_PENDING) {
                    $notify[] = ['warning', 'This order is already pending!'];
                    return back()->withNotify($notify);
                }

                if ($exchange->status == Status::EXCHANGE_APPROVED) {
                    $this->reverseApprovedExchangeReserve($exchange, $user);
                }

                $previous_status = $exchange->status;

                $exchange->admin_feedback = $request->cancel_reason;
                $exchange->status = Status::EXCHANGE_PENDING;
                $exchange->updated_by = auth()->user()->id;
                $exchange->status_at = now();
                $exchange->save();

                if (
                    $exchange->transaction_type == 'WITHDRAW'
                    && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)
                ) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - ($exchange->sending_amount + $exchange->sending_charge),
                        "via" => "Withdraw Pending",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->sending_amount + $exchange->sending_charge;
                    $user->save();
                }

                if (
                    $exchange->transaction_type == 'DEPOSIT'
                    && ($previous_status == Status::EXCHANGE_APPROVED)
                ) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                        "via" => "Deposit Pending",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                    $user->save();
                }

                $newExchangeLog = new GpayExchangeLogModel;
                $newExchangeLog->exchange_id = $id;
                $newExchangeLog->exchange_status = 'Pending';
                $newExchangeLog->updated_by = auth()->user()->id;
                $newExchangeLog->updated_date = now();

                try {$newExchangeLog->save();} catch (\Throwable $th) {}

                notify($exchange->user, 'PENDING_EXCHANGE', [
                    'exchange' => $exchange->exchange_id,
                    'reason' => $exchange->admin_feedback,
                ]);

                $notify[] = ['success', 'Exchange pending successfully'];

                return back()->withNotify($notify);
            });

        } finally {
            // Ensure lock is always released
            optional($lock)->release();
        }
    }

    public function cancel(Request $request, $id)
    {
        $lockKey = "exchange_update_lock_{$id}";
        $lock = Cache::lock($lockKey, 5); // 5 seconds lock

        try {
            if (!$lock->get()) {
                $notify[] = ['warning', 'Another update is already in progress. Try again shortly.'];
                return back()->withNotify($notify);
            }

            return DB::transaction(function () use ($request, $id) {

                $this->check_update_permission();

                $request->validate([
                    'cancel_reason' => 'required',
                ]);

                // Lock row inside DB transaction
                $exchange = Exchange::with('updatedBy')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($this->check_exchnage_updated_at($exchange)) {
                    $notify[] = ['error', 'This order has been locked.'];
                    return back()->withNotify($notify);
                }

                $user = $exchange->user;

                if (!$this->canBeModifiedByCurrentUser($exchange)) {
                    $notify[] = ['error', 'Only admin can modify after 30 minutes.'];
                    return back()->withNotify($notify);
                }

                if ($exchange->status == Status::EXCHANGE_CANCEL) {
                    $notify[] = ['warning', 'This order already canceled!'];
                    return back()->withNotify($notify);
                }

                if ($exchange->status == Status::EXCHANGE_APPROVED) {
                    $this->reverseApprovedExchangeReserve($exchange, $user);
                }

                $previous_status = $exchange->status;

                $exchange->admin_feedback = $request->cancel_reason;
                $exchange->status = Status::EXCHANGE_CANCEL;
                $exchange->updated_by = auth()->user()->id;
                $exchange->status_at = now();
                $exchange->save();

                if (
                    $exchange->transaction_type == 'WITHDRAW'
                    && $previous_status != Status::EXCHANGE_REFUND
                ) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance + $exchange->refund_amount,
                        "via" => "Withdraw Cancel",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance += $exchange->refund_amount;
                    $user->save();
                }

                if (
                    $exchange->transaction_type == 'DEPOSIT'
                    && ($previous_status == Status::EXCHANGE_APPROVED)
                ) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                        "via" => "Deposit Cancel",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                    $user->save();
                }

                $newExchangeLog = new GpayExchangeLogModel;

                $newExchangeLog->exchange_id = $id;
                $newExchangeLog->exchange_status = 'Cancel';
                $newExchangeLog->updated_by = auth()->user()->id;
                $newExchangeLog->updated_date = now();
                try {$newExchangeLog->save();} catch (\Throwable $th) {}

                notify($exchange->user, 'CANCEL_EXCHANGE', [
                    'exchange' => $exchange->exchange_id,
                    'reason' => $exchange->admin_feedback,
                ]);

                $notify[] = ['success', 'Exchange canceled successfully'];

                return back()->withNotify($notify);
            });

        } finally {
            optional($lock)->release();
        }
    }


    public function refund(Request $request, $id)
    {
        $lockKey = "exchange_refund_lock_{$id}";
        $lock = Cache::lock($lockKey, 5);

        try {
            if (!$lock->get()) {
                $notify[] = ['warning', 'Another update is already in progress. Try again shortly.'];
                return back()->withNotify($notify);
            }

            return DB::transaction(function () use ($request, $id) {

                $this->check_update_permission();
                $request->validate([
                    'refund_reason' => 'required',
                ]);

                $exchange = Exchange::with('updatedBy')->where('id', $id)->firstOrFail();
                $user = $exchange->user;
                if (!$this->canBeModifiedByCurrentUser($exchange)) {
                    $notify[] = ['error', 'Only admin can modify after 30 minutes.'];
                    return back()->withNotify($notify);
                }

                if ($exchange->status == Status::EXCHANGE_REFUND) {
                    $notify[] = ['warning', 'This order already refunded!'];
                    return back()->withNotify($notify);
                }

                if ($exchange->status == Status::EXCHANGE_APPROVED) {
                    $this->reverseApprovedExchangeReserve($exchange, $user);
                }

                $previous_status = $exchange->status;

                $exchange->admin_feedback = $request->refund_reason;
                $exchange->status = Status::EXCHANGE_REFUND;
                $exchange->updated_by = auth()->user()->id;
                $exchange->status_at = now();
                $exchange->save();

                if ($exchange->transaction_type == 'WITHDRAW' && $previous_status != Status::EXCHANGE_CANCEL) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance + $exchange->refund_amount,
                        "via" => "Withdaw Refund",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance += $exchange->refund_amount;
                    $user->save();
                }

                if ($exchange->transaction_type == 'DEPOSIT' && $previous_status == Status::EXCHANGE_APPROVED) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                        "via" => "Deposit Refund",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                    $user->save();
                }


                $newExchangeLog = new GpayExchangeLogModel;

                $newExchangeLog->exchange_id = $id;
                $newExchangeLog->exchange_status = 'Refund';
                $newExchangeLog->updated_by = auth()->user()->id;
                $newExchangeLog->updated_date = Carbon::now();
                try {$newExchangeLog->save();} catch (\Throwable $th) {}

                notify($exchange->user, 'EXCHANGE_REFUND', [
                    'exchange' => $exchange->exchange_id,
                    'currency' => $exchange->sendCurrency->cur_sym,
                    'amount' => showAmount($exchange->sending_amount, currencyFormat: false),
                    'method' => $exchange->sendCurrency->name,
                    'reason' => $exchange->admin_feedback,
                ]);

                $notify[] = ['success', 'Exchange refunded successfully'];

                return back()->withNotify($notify);
            });

        } finally {
            optional($lock)->release();
        }
    }

    public function hold($id)
    {
        $lockKey = "exchange_hold_lock_{$id}";
        $lock = Cache::lock($lockKey, 5); // 5 seconds lock

        try {
            if (!$lock->get()) {
                $notify[] = ['warning', 'Another update is already in progress. Try again shortly.'];
                return back()->withNotify($notify);
            }

            return DB::transaction(function () use ($id) {
                $this->check_update_permission();
                $exchange = Exchange::with('updatedBy')->findOrFail($id);
                $user = $exchange->user;

                if ($exchange->status == Status::EXCHANGE_HOLD) {
                    $notify[] = ['warning', 'This order already hold!'];

                    return back()->withNotify($notify);
                }

                if ($exchange->status == Status::EXCHANGE_APPROVED) {
                    $this->reverseApprovedExchangeReserve($exchange, $user);
                }

                $previous_status = $exchange->status;

                $exchange->status = Status::EXCHANGE_HOLD;
                $exchange->admin_feedback = 'Marked as Hold by Admin';
                $exchange->updated_by = auth()->user()->id;
                $exchange->status_at = now();
                $exchange->save();


                if ($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - $exchange->refund_amount,
                        "via" => "Withdraw Hold",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->refund_amount;
                    $user->save();
                }
                if ($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)) {
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                        "via" => "Deposit Hold",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                    $user->save();
                }

                $newExchangeLog = new GpayExchangeLogModel;

                $newExchangeLog->exchange_id = $id;
                $newExchangeLog->exchange_status = 'Hold';
                $newExchangeLog->updated_by = auth()->user()->id;
                $newExchangeLog->updated_date = Carbon::now();
                try {$newExchangeLog->save();} catch (\Throwable $th) {}

                $notify[] = ['success', 'Exchange marked as hold'];

                return back()->withNotify($notify);
            });

        } finally {
            optional($lock)->release();
        }
    }

    public function processing($id)
    {
        $lockKey = "exchange_processing_lock_{$id}";
        $lock = Cache::lock($lockKey, 5); // 5 seconds lock

        try {
            if (!$lock->get()) {
                $notify[] = ['warning', 'Another update is already in progress. Try again shortly.'];
                return back()->withNotify($notify);
            }

            return DB::transaction(function () use ($id) {
                $this->check_update_permission();
                $exchange = Exchange::with('updatedBy')->findOrFail($id);
                $user = $exchange->user;

                if ($exchange->status == Status::EXCHANGE_PROCESSING) {
                    $notify[] = ['warning', 'This order already processing!'];

                    return back()->withNotify($notify);
                }

                if ($exchange->status == Status::EXCHANGE_APPROVED) {
                    $this->reverseApprovedExchangeReserve($exchange, $user);
                }

                $previous_status = $exchange->status;

                $exchange->status = Status::EXCHANGE_PROCESSING;
                $exchange->admin_feedback = 'Marked as Processing by Admin';
                $exchange->updated_by = auth()->user()->id;
                $exchange->status_at = now();
                $exchange->save();

                if ($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)) {
                    $user = $exchange->user;
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - $exchange->refund_amount,
                        "via" => "Withdraw Processing",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->refund_amount;
                    $user->save();
                }
                if ($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)) {
                    $user = $exchange->user;
                    $user->balanceStatement()->create([
                        "before" => $user->balance,
                        "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                        "via" => "Deposit Processing",
                        "admin_id" => auth("admin")->id(),
                        "exchange_id" => $exchange->id,
                    ]);
                    $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                    $user->save();
                }

                $newExchangeLog = new GpayExchangeLogModel;

                $newExchangeLog->exchange_id = $id;
                $newExchangeLog->exchange_status = 'Processing';
                $newExchangeLog->updated_by = auth()->user()->id;
                $newExchangeLog->updated_date = Carbon::now();
                try {$newExchangeLog->save();} catch (\Throwable $th) {}

                $notify[] = ['success', 'Exchange marked as processing'];

                return back()->withNotify($notify);
            });

        } finally {
            optional($lock)->release();
        }
    }

    public function approve(Request $request, $id)
    {

        $lockKey = "exchange_update_approve_{$id}";
        $lock = Cache::lock($lockKey, 5); // 5 seconds lock

        try {
            if (!$lock->get()) {
                $notify[] = ['warning', 'Another update is already in progress. Try again shortly.'];
                return back()->withNotify($notify);
            }

            return DB::transaction(function () use ($request, $id) {
                $this->check_update_permission();
                try {
                    DB::beginTransaction();
                    $request->validate([
                        'transaction' => 'required',
                    ]);

                    $exchange = Exchange::with("sendCurrency", "receivedCurrency", "updatedBy")->where('id', $id)->first();

                    if ($this->check_exchnage_updated_at($exchange)) {
                        $notify[] = ['error', 'This order has been locked.'];
                        return back()->withNotify($notify);
                    }

                    $finalReceivingAmount = $exchange->receiving_amount;
                    $appliedHiddenCharge = 0;
                    $hiddenCharges = \App\Models\GpayHiddenChargeModel::where('currency_id', $exchange->receive_currency_id)->get();

                    foreach ($hiddenCharges as $hidden) {
                        if ($hidden->charge_percent && $hidden->charge_percent > 0) {
                            $appliedHiddenCharge += $finalReceivingAmount * ($hidden->charge_percent / 100);
                            $exchange->hidden_charge_percent = $hidden->charge_percent;
                        }
                        if ($hidden->charge_fixed && $hidden->charge_fixed > 0) {
                            $appliedHiddenCharge += $hidden->charge_fixed;
                            $exchange->hidden_charge_fixed = $hidden->charge_fixed;
                        }
                    }

                    if (!$exchange) {
                        return back()->withErrors(['error' => 'Exchange not found or not in pending status.']);
                    }
                    $totalReceivedAmount = $appliedHiddenCharge + $finalReceivingAmount + (-1 * $exchange->receiving_charge);
                    if (
                        $totalReceivedAmount > $exchange->receivedCurrency->reserve &&
                        (int) $exchange->receivedCurrency->neg_bal_allowed !== 1 &&
                        ($exchange->receivedCurrency->reserve - $totalReceivedAmount) < 0
                    ) {
                        $notify[] = ['error', 'Sorry, reserve limit exceeded'];
                        return back()->withNotify($notify);
                    }

                    $previous_status = $exchange->status;

                    if ($exchange->status == Status::EXCHANGE_APPROVED) {
                        $notify[] = ['warning', 'This order already approved!'];
                        return back()->withNotify($notify);
                    }

                    // Approve exchange
                    $exchange->status = Status::EXCHANGE_APPROVED;
                    $exchange->admin_trx_no = $request->transaction;
                    $exchange->updated_by = auth()->user()->id;
                    $exchange->status_at = now();

                    $exchange->save();

                    $user = User::find($exchange->user_id);

                    // ========== CURRENCY RESERVE UPDATE ==========
                    // 1. Deduct receive amount + hidden charge from receive currency
                    if ($exchange->transaction_type == 'DEPOSIT') {
                        $sendCurrency = $exchange->sendCurrency;
                        $oldSendReserve = $sendCurrency->reserve;
                        $sendCurrency->reserve += ($exchange->sending_amount + $exchange->sending_charge);
                        $sendCurrency->save();

                        // 2. Add send amount + send charge to sending currency
                        $receivedCurrency = $exchange->receivedCurrency;
                        $oldReceivedReserve = $receivedCurrency->reserve;
                        $receivedCurrency->reserve += ($exchange->receiving_amount - $exchange->receiving_charge);
                        $receivedCurrency->save();
                        $user->balanceStatement()->create([
                            "before" => $user->balance,
                            "after" => $user->balance + ($exchange->receiving_amount - $exchange->receiving_charge),
                            "via" => "Deposit Approve",
                            "admin_id" => auth("admin")->id(),
                            "exchange_id" => $exchange->id,
                        ]);
                        $user->balance += $exchange->receiving_amount - $exchange->receiving_charge;
                        $user->save();

                    } elseif ($exchange->transaction_type == 'WITHDRAW') {
                        $sendCurrency = $exchange->sendCurrency;
                        $oldSendReserve = $sendCurrency->reserve;
                        $sendCurrency->reserve -= ($exchange->sending_amount + $exchange->sending_charge);
                        $sendCurrency->save();

                        $receivedCurrency = $exchange->receivedCurrency;
                        $oldReceivedReserve = $receivedCurrency->reserve;
                        $receivedCurrency->reserve -= ($exchange->receiving_amount - $exchange->receiving_charge) + $appliedHiddenCharge;
                        $receivedCurrency->save();


                        if ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND) {
                            $user->balanceStatement()->create([
                                "before" => $user->balance,
                                "after" => $user->balance - ($exchange->sending_amount + $exchange->sending_charge),
                                "via" => "Withdraw Approve",
                                "admin_id" => auth("admin")->id(),
                                "exchange_id" => $exchange->id,
                            ]);
                            $user->balance -= $exchange->sending_amount + $exchange->sending_charge;
                            $user->save();
                        }

                    } else {
                        $sendCurrency = $exchange->receivedCurrency;
                        $oldSendReserve = $sendCurrency->reserve;
                        $sendCurrency->reserve -= ($exchange->receiving_amount - $exchange->receiving_charge) + $appliedHiddenCharge;
                        $sendCurrency->save();


                        $receivedCurrency = $exchange->sendCurrency;
                        $oldReceivedReserve = $receivedCurrency->reserve;
                        $receivedCurrency->reserve += ($exchange->sending_amount + $exchange->sending_charge);
                        $receivedCurrency->save();
                    }

                    // ========== COMMISSION ==========

                    if (!$user) {
                        return back()->withErrors(['error' => 'User not found.']);
                    }

                    if (gs('exchange_commission') == Status::YES && $exchange->transaction_type != 'WITHDRAW') {
                        $amount = $exchange->buy_rate * $exchange->sending_amount;
                        $this->levelCommission($user->id, $amount, $exchange->id, 'exchange_commission');
                    }

                    // ========== FIRST EXCHANGE BONUS ==========
                    if (gs('first_exchange_bonus')) {
                        $isFirstExchange = Exchange::where('user_id', $user->id)
                            ->where('status', Status::EXCHANGE_APPROVED)
                            ->count();

                        if ($isFirstExchange == 1 && $exchange->transaction_type == 'EXCHANGE') {
                            $bonusPercentage = gs('first_exchange_bonus_percentage');
                            $bonusAmount = $exchange->receiving_amount * ($bonusPercentage / 100);
                            $rate = getAmount($exchange->receivedCurrency->conversion_rate);
                            $convertedAmount = $bonusAmount * $rate;

                            $exchange->bonus_first_exchange = $convertedAmount;
                            $exchange->save();
                            $user->balanceStatement()->create([
                                "before" => $user->balance,
                                "after" => $user->balance + $convertedAmount,
                                "via" => "First Exchange Bonus",
                                "admin_id" => auth("admin")->id(),
                                "exchange_id" => $exchange->id,
                            ]);
                            $user->balance += $convertedAmount;
                            $user->save();

                            notify($user, 'BONUS_RECEIVED', [
                                'exchange' => $exchange->exchange_id,
                                'amount' => showAmount($convertedAmount, currencyFormat: false),
                                'currency' => gs('cur_text'),
                            ]);
                        }
                    }


                    // ========== USER NOTIFY ==========
                    notify($user, 'APPROVED_EXCHANGE', [
                        'exchange' => $exchange->exchange_id,
                        'currency' => $exchange->receivedCurrency ? $exchange->receivedCurrency->cur_sym : '',
                        'amount' => showAmount($exchange->receiving_amount - $exchange->receiving_charge, currencyFormat: false),
                        'method' => $exchange->receivedCurrency ? $exchange->receivedCurrency->name : '',
                        'admin_transaction_number' => $request->transaction,
                    ]);

                    // ========== LOG ==========
                    $newExchangeLog = new \App\Models\GpayExchangeLogModel;
                    $newExchangeLog->exchange_id = $id;
                    $newExchangeLog->exchange_status = 'Approved';
                    $newExchangeLog->updated_by = auth()->user()->id;
                    $newExchangeLog->updated_date = now();
                    try {$newExchangeLog->save();} catch (\Throwable $th) {}

                    DB::commit();

                    $notify[] = ['success', 'Exchange approved successfully'];
                    return back()->withNotify($notify);

                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('Exchange approval failed', [
                        'exchange_id' => $id,
                        'error_message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    return back()->withErrors(['error' => 'An error occurred while approving the exchange.']);
                }
            });

        } finally {
            optional($lock)->release();
        }
    }

    public function levelCommission($id, $amount, $exchange_id, $commissionType = '')
    {
        $usr = $id;

        $me = User::find($usr);
        $refer = User::find($me->ref_by);
        if (!$refer) {
            return 1;
        }

        $commission = Referral::where('level', $refer->commission_level)->first();
        if (!$commission) {
            return 1;
        }
        $i = $commission->level;

        $com = ($amount * $commission->percent) / 100;
        $newBal = getAmount($refer->balance + $com);
        $refer->balance = $newBal;
        $refer->save();

        $trx = getTrx();

        $commission_log = new CommissionLog;
        $commission_log->user_id = $refer->id;
        $commission_log->who = $id;
        $commission_log->exchange_id = $exchange_id;
        $commission_log->level = $i . ' level Referral Commission';
        $commission_log->amount = getAmount($com);
        $commission_log->main_amo = $newBal;
        $commission_log->title = $commissionType;
        $commission_log->trx = $trx;
        $commission_log->save();

        notify($refer, 'REFERRAL_COMMISSION', [
            'amount' => getAmount($com),
            'post_balance' => $newBal,
            'trx' => $trx,
            'level' => $i . ' level Referral Commission',
        ]);

        $usr = $refer->id;

        return 0;
    }
    public function levelCommissionRevert($id, $amount, $exchange_id, $commissionType = '')
    {
        $usr = $id;

        $me = User::find($usr);
        $refer = User::find($me->ref_by);
        if ($refer == '') {
            return 1;
        }

        $commission = Referral::where('level', $refer->commission_level)->first();
        if ($commission == null) {
            return 1;
        }
        $i = $commission->level;

        $com = ($amount * $commission->percent) / 100;
        $newBal = getAmount($refer->balance - $com);
        $refer->balance = $newBal;
        $refer->save();


        $commission = CommissionLog::where('exchange_id', $exchange_id)->first();

        $trx = $commission->trx;

        $commission->delete();

        notify($refer, 'REFERRAL_COMMISSION', [
            'amount' => getAmount($com),
            'post_balance' => $newBal,
            'trx' => $trx,
            'level' => $i . ' level Referral Commission Revert',
        ]);

        $usr = $refer->id;

        return 0;
    }

    public function download($exchangeId)
    {
        $pageTitle = 'Download Exchange';
        $exchange = Exchange::where('id', $exchangeId)->with('user')->firstOrFail();
        $user = $exchange->user;
        $pdf = PDF::loadView('partials.pdf', compact('pageTitle', 'user', 'exchange'));
        $fileName = $exchange->exchange_id . '_' . time();

        return $pdf->download($fileName . '.pdf');
    }

    private function canBeModifiedByCurrentUser(Exchange $exchange): bool
    {
        $user = auth()->user();

        // Sanity check
        if (!$user) {
            Log::warning('Unauthorized access: No user authenticated', [
                'exchange_id' => $exchange->id,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return false;
        }

        // Always allow if user has administrator role
        if ($user->id == 1) {
            Log::debug('Access granted: User is administrator', [
                'user_id' => $user->id,
                'roles' => json_encode($user->roles),
                'exchange_id' => $exchange->id,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return true;
        }

        // Allow within 30 minutes of last update
        $minutesSinceUpdate = $exchange->updated_at->diffInMinutes(now());

        if ($minutesSinceUpdate <= 30) {
            Log::debug('Access granted: User is within 30-minute window', [
                'user_id' => $user->id,
                'roles' => json_encode($user->roles),
                'exchange_id' => $exchange->id,
                'minutes_since_update' => $minutesSinceUpdate,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return true;
        }

        // Otherwise, block the change
        Log::warning('Access denied: User tried to modify after 30 minutes', [
            'user_id' => $user->id,
            'roles' => json_encode($user->roles),
            'exchange_id' => $exchange->id,
            'minutes_since_update' => $minutesSinceUpdate,
            'user_admin' => false,
            'timestamp' => now()->toDateTimeString(),
        ]);

        return false;
    }

    private function reverseApprovedExchangeReserve(Exchange $exchange, User $user)
    {
        $finalReceivingAmount = $exchange->receiving_amount;
        $appliedHiddenCharge = 0;
        $hiddenCharges = \App\Models\GpayHiddenChargeModel::where('currency_id', $exchange->receive_currency_id)->get();
        foreach ($hiddenCharges as $hidden) {
            if ($hidden->charge_percent && $hidden->charge_percent > 0) {
                $appliedHiddenCharge += $finalReceivingAmount * ($hidden->charge_percent / 100);
                $exchange->hidden_charge_percent = $hidden->charge_percent;
            }
            if ($hidden->charge_fixed && $hidden->charge_fixed > 0) {
                $appliedHiddenCharge += $hidden->charge_fixed;
                $exchange->hidden_charge_fixed = $hidden->charge_fixed;
            }
        }

        if (gs('exchange_commission') == Status::YES && $exchange->transaction_type != 'WITHDRAW') {
            $amount = $exchange->buy_rate * $exchange->sending_amount;
            $this->levelCommissionRevert($user->id, $amount, $exchange->id, 'exchange_commission');
        }

        if ($exchange->transaction_type == 'DEPOSIT') {
            $sendCurrency = $exchange->sendCurrency;
            $sendCurrency->reserve -= ($exchange->sending_amount + $exchange->sending_charge);
            $sendCurrency->save();

            $receivedCurrency = $exchange->receivedCurrency;
            $receivedCurrency->reserve -= ($exchange->receiving_amount - $exchange->receiving_charge);
            $receivedCurrency->save();
        } elseif ($exchange->transaction_type == 'WITHDRAW') {
            $sendCurrency = $exchange->sendCurrency;
            $sendCurrency->reserve += ($exchange->sending_amount + $exchange->sending_charge);
            $sendCurrency->save();

            $receivedCurrency = $exchange->receivedCurrency;
            $receivedCurrency->reserve += ($exchange->receiving_amount + (-1 * $exchange->receiving_charge) + $appliedHiddenCharge);
            $receivedCurrency->save();

        } else {
            $sendCurrency = $exchange->sendCurrency;
            $sendCurrency->reserve -= ($exchange->sending_amount + $exchange->sending_charge);



            $receivedCurrency = $exchange->receivedCurrency;
            $receivedCurrency->reserve += ($exchange->receiving_amount + (-1 * $exchange->receiving_charge) + $appliedHiddenCharge);

            $sendCurrency->save();
            $receivedCurrency->save();
        }
    }
    public function exchanges_bulk_update(Request $request)
    {
        $lockKey = "exchange_update_lock_".implode('_', $request->ids)  ;
        $lock = Cache::lock($lockKey, 5); // 5 seconds lock

        try {
            if (!$lock->get()) {
                $notify[] = ['warning', 'Another update is already in progress. Try again shortly.'];
                return back()->withNotify($notify);
            }

            return DB::transaction(function () use ($request) {
                DB::beginTransaction();
                try {
                    $request->validate([
                        "status" => "required",
                        "ids" => "required"
                    ]);
                    $exchange_status = $request->status;
                    $id_array = $request->ids;
                    $exchanges = Exchange::whereIn('id', $id_array)->get();
                    foreach ($exchanges as $exchange) {
                        $user = $exchange->user;
                        $previous_status = $exchange->status;

                        // if ($this->check_exchnage_updated_at($exchange)) {
                        //     continue;
                        // }
                        // if (!$this->canBeModifiedByCurrentUser($exchange)) {
                        //     continue;
                        // }
                        if ($exchange->status == Status::EXCHANGE_APPROVED) {
                            $this->reverseApprovedExchangeReserve($exchange, $user);
                        }

                        $newExchangeLog = new GpayExchangeLogModel;
                        $newExchangeLog->exchange_id = $exchange->id;

                        if ($exchange_status == Status::EXCHANGE_PENDING) {

                            if ($exchange->status == Status::EXCHANGE_PENDING) {
                                continue;
                            }

                            if ($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - ($exchange->sending_amount + $exchange->sending_charge),
                                    "via" => "Withdraw Bulk Pending",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->sending_amount + $exchange->sending_charge;
                                $user->save();
                            }
                            if ($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                                    "via" => "Deposit Bulk Pending",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                                $user->save();
                            }

                            $newExchangeLog->exchange_status = 'Pending';

                            notify($exchange->user, 'PENDING_EXCHANGE', [
                                'exchange' => $exchange->exchange_id,
                                'reason' => $exchange->admin_feedback,
                            ]);
                        }
                        if ($exchange_status == Status::EXCHANGE_CANCEL) {

                            if ($exchange->status == Status::EXCHANGE_CANCEL) {
                                continue;
                            }

                            if ($exchange->transaction_type == 'WITHDRAW' && $previous_status != Status::EXCHANGE_REFUND) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance + $exchange->refund_amount,
                                    "via" => "Withdraw Cancel",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance += $exchange->refund_amount;
                                $user->save();
                            }
                            if ($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                                    "via" => "Deposit Cancel",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                                $user->save();
                            }

                            $newExchangeLog->exchange_status = 'Cancel';

                            notify($exchange->user, 'CANCEL_EXCHANGE', [
                                'exchange' => $exchange->exchange_id,
                                'reason' => $exchange->admin_feedback,
                            ]);

                        }
                        if ($exchange_status == Status::EXCHANGE_REFUND) {

                            if ($exchange->status == Status::EXCHANGE_REFUND) {
                                continue;
                            }

                            if ($exchange->transaction_type == 'WITHDRAW' && $previous_status != Status::EXCHANGE_CANCEL) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance + $exchange->refund_amount,
                                    "via" => "Withdaw Refund",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance += $exchange->refund_amount;
                                $user->save();
                            }

                            if ($exchange->transaction_type == 'DEPOSIT' && $previous_status == Status::EXCHANGE_APPROVED) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                                    "via" => "Deposit Refund",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                                $user->save();
                            }

                            $newExchangeLog->exchange_status = 'Refund';

                            notify($exchange->user, 'EXCHANGE_REFUND', [
                                'exchange' => $exchange->exchange_id,
                                'currency' => $exchange->sendCurrency->cur_sym,
                                'amount' => showAmount($exchange->sending_amount, currencyFormat: false),
                                'method' => $exchange->sendCurrency->name,
                                'reason' => $exchange->admin_feedback,
                            ]);
                        }
                        if ($exchange_status == Status::EXCHANGE_HOLD) {

                            if ($exchange->status == Status::EXCHANGE_HOLD) {
                                continue;
                            }

                            if ($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - $exchange->refund_amount,
                                    "via" => "Withdraw Hold",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->refund_amount;
                                $user->save();
                            }
                            if ($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)) {
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                                    "via" => "Deposit Hold",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                                $user->save();
                            }

                            $newExchangeLog->exchange_status = 'Hold';

                        }
                        if ($exchange_status == Status::EXCHANGE_PROCESSING) {

                            if ($exchange->status == Status::EXCHANGE_PROCESSING) {
                                continue;
                            }

                            if ($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)) {
                                $user = $exchange->user;
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - $exchange->refund_amount,
                                    "via" => "Withdraw Processing",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->refund_amount;
                                $user->save();
                            }
                            if ($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)) {
                                $user = $exchange->user;
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance - ($exchange->receiving_amount - $exchange->receiving_charge),
                                    "via" => "Deposit Processing",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
                                $user->save();
                            }
                            $newExchangeLog->exchange_status = 'Processing';
                        }
                        if ($exchange_status == Status::EXCHANGE_APPROVED) {
                            DB::beginTransaction();
                            $finalReceivingAmount = $exchange->receiving_amount;
                            $appliedHiddenCharge = 0;
                            $hiddenCharges = \App\Models\GpayHiddenChargeModel::where('currency_id', $exchange->receive_currency_id)->get();

                            foreach ($hiddenCharges as $hidden) {
                                if ($hidden->charge_percent && $hidden->charge_percent > 0) {
                                    $appliedHiddenCharge += $finalReceivingAmount * ($hidden->charge_percent / 100);
                                    $exchange->hidden_charge_percent = $hidden->charge_percent;
                                }
                                if ($hidden->charge_fixed && $hidden->charge_fixed > 0) {
                                    $appliedHiddenCharge += $hidden->charge_fixed;
                                    $exchange->hidden_charge_fixed = $hidden->charge_fixed;
                                }
                            }

                            $totalReceivedAmount = $appliedHiddenCharge + $finalReceivingAmount + (-1 * $exchange->receiving_charge);
                            if (
                                $totalReceivedAmount > $exchange->receivedCurrency->reserve &&
                                (int) $exchange->receivedCurrency->neg_bal_allowed !== 1 &&
                                ($exchange->receivedCurrency->reserve - $totalReceivedAmount) < 0
                            ) {
                                DB::rollBack();
                                continue;
                            }

                            $previous_status = $exchange->status;

                            if ($exchange->status == Status::EXCHANGE_APPROVED) {
                                DB::rollBack();
                                continue;
                            }

                            // Approve exchange
                            $exchange->status = Status::EXCHANGE_APPROVED;
                            $exchange->admin_trx_no = $request->transaction;
                            $exchange->updated_by = auth()->user()->id;
                            $exchange->status_at = now();

                            $exchange->save();

                            $user = User::find($exchange->user_id);

                            // ========== CURRENCY RESERVE UPDATE ==========
                            // 1. Deduct receive amount + hidden charge from receive currency
                            if ($exchange->transaction_type == 'DEPOSIT') {
                                $sendCurrency = $exchange->sendCurrency;
                                $oldSendReserve = $sendCurrency->reserve;
                                $sendCurrency->reserve += ($exchange->sending_amount + $exchange->sending_charge);
                                $sendCurrency->save();

                                // 2. Add send amount + send charge to sending currency
                                $receivedCurrency = $exchange->receivedCurrency;
                                $oldReceivedReserve = $receivedCurrency->reserve;
                                $receivedCurrency->reserve += ($exchange->receiving_amount - $exchange->receiving_charge);
                                $receivedCurrency->save();
                                $user->balanceStatement()->create([
                                    "before" => $user->balance,
                                    "after" => $user->balance + ($exchange->receiving_amount - $exchange->receiving_charge),
                                    "via" => "Deposit Approve",
                                    "admin_id" => auth("admin")->id(),
                                    "exchange_id" => $exchange->id,
                                ]);
                                $user->balance += $exchange->receiving_amount - $exchange->receiving_charge;
                                $user->save();

                            } elseif ($exchange->transaction_type == 'WITHDRAW') {
                                $sendCurrency = $exchange->sendCurrency;
                                $oldSendReserve = $sendCurrency->reserve;
                                $sendCurrency->reserve -= ($exchange->sending_amount + $exchange->sending_charge);
                                $sendCurrency->save();

                                $receivedCurrency = $exchange->receivedCurrency;
                                $oldReceivedReserve = $receivedCurrency->reserve;
                                $receivedCurrency->reserve -= ($exchange->receiving_amount - $exchange->receiving_charge) + $appliedHiddenCharge;
                                $receivedCurrency->save();


                                if ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND) {
                                    $user->balanceStatement()->create([
                                        "before" => $user->balance,
                                        "after" => $user->balance - ($exchange->sending_amount + $exchange->sending_charge),
                                        "via" => "Withdraw Approve",
                                        "admin_id" => auth("admin")->id(),
                                        "exchange_id" => $exchange->id,
                                    ]);
                                    $user->balance -= $exchange->sending_amount + $exchange->sending_charge;
                                    $user->save();
                                }

                            } else {
                                $sendCurrency = $exchange->receivedCurrency;
                                $oldSendReserve = $sendCurrency->reserve;
                                $sendCurrency->reserve -= ($exchange->receiving_amount - $exchange->receiving_charge) + $appliedHiddenCharge;
                                $sendCurrency->save();


                                $receivedCurrency = $exchange->sendCurrency;
                                $oldReceivedReserve = $receivedCurrency->reserve;
                                $receivedCurrency->reserve += ($exchange->sending_amount + $exchange->sending_charge);
                                $receivedCurrency->save();
                            }

                            // ========== COMMISSION ==========

                            if (!$user) {
                                return back()->withErrors(['error' => 'User not found.']);
                            }

                            if (gs('exchange_commission') == Status::YES && $exchange->transaction_type != 'WITHDRAW') {
                                $amount = $exchange->buy_rate * $exchange->sending_amount;
                                $this->levelCommission($user->id, $amount, $exchange->id, 'exchange_commission');
                            }

                            // ========== FIRST EXCHANGE BONUS ==========
                            if (gs('first_exchange_bonus')) {
                                $isFirstExchange = Exchange::where('user_id', $user->id)
                                    ->where('status', Status::EXCHANGE_APPROVED)
                                    ->count();

                                if ($isFirstExchange == 1 && $exchange->transaction_type == 'EXCHANGE') {
                                    $bonusPercentage = gs('first_exchange_bonus_percentage');
                                    $bonusAmount = $exchange->receiving_amount * ($bonusPercentage / 100);
                                    $rate = getAmount($exchange->receivedCurrency->conversion_rate);
                                    $convertedAmount = $bonusAmount * $rate;

                                    $exchange->bonus_first_exchange = $convertedAmount;
                                    $exchange->save();

                                    $user->balanceStatement()->create([
                                        "before" => $user->balance,
                                        "after" => $user->balance + $convertedAmount,
                                        "via" => "First Exchange Bonus",
                                        "admin_id" => auth("admin")->id(),
                                        "exchange_id" => $exchange->id,
                                    ]);
                                    $user->balance += $convertedAmount;
                                    $user->save();

                                    notify($user, 'BONUS_RECEIVED', [
                                        'exchange' => $exchange->exchange_id,
                                        'amount' => showAmount($convertedAmount, currencyFormat: false),
                                        'currency' => gs('cur_text'),
                                    ]);
                                }
                            }
                            notify($user, 'APPROVED_EXCHANGE', [
                                'exchange' => $exchange->exchange_id,
                                'currency' => $exchange->receivedCurrency ? $exchange->receivedCurrency->cur_sym : '',
                                'amount' => showAmount($exchange->receiving_amount - $exchange->receiving_charge, currencyFormat: false),
                                'method' => $exchange->receivedCurrency ? $exchange->receivedCurrency->name : '',
                                'admin_transaction_number' => $request->transaction,
                            ]);

                            $newExchangeLog->exchange_status = 'Approved';
                            DB::commit();
                        }

                        $newExchangeLog->updated_by = auth()->user()->id;
                        $newExchangeLog->updated_date = Carbon::now();
                        try {$newExchangeLog->save();} catch (\Throwable $th) {}

                        $exchange->status = $exchange_status;
                        $exchange->save();

                        sleep(3);
                    }
                    DB::commit();
                } catch (\Throwable $th) {
                    DB::rollBack();
                    throw $th;
                }

                $notify[] = ['success', 'Bulk Exchange Status Update Successfully'];
                return back()->withNotify($notify);
            });

        } finally {
            optional($lock)->release();
        }
    }
    public function advance_search(Request $request)
    {
        $field = $request->field;
        $value = $request->value;
        $current_url = $request->current_url;
        $url_array = explode('/', $current_url);
        $last_segment = $url_array[count($url_array) - 1];

        $allowedFields = ['exchange_id', 'email'];
        if (!in_array($field, $allowedFields)) {
            abort(400, 'Invalid search field');
        }
        if ($field == "exchange_id") {
            $data_query = Exchange::where("status", "!=", 0)->where($field, "like", "%$value%");

            if ($last_segment == 'pending') {
                $data_query = $data_query->where('status', Status::EXCHANGE_PENDING);
            }
            if ($last_segment == 'hold') {
                $data_query = $data_query->where('status', Status::EXCHANGE_HOLD);
            }
            if ($last_segment == 'processing') {
                $data_query = $data_query->where('status', Status::EXCHANGE_PROCESSING);
            }
            if ($last_segment == 'approved') {
                $data_query = $data_query->where('status', Status::EXCHANGE_APPROVED);
            }
            if ($last_segment == 'refunded') {
                $data_query = $data_query->where('status', Status::EXCHANGE_REFUND);
            }
            if ($last_segment == 'canceled') {
                $data_query = $data_query->where('status', Status::EXCHANGE_CANCEL);
            }

            $data = $data_query->limit(100)->get()->pluck($field)->toArray();
        } else {
            $data_query = Exchange::whereHas('user', function ($query) use ($value) {
                $query->where(function ($q) use ($value) {
                    $q->where('email', 'like', "%$value%")
                        ->orWhere('username', 'like', "%$value%");
                });
            })
                ->with('user:id,email,username');
            if ($last_segment == 'pending') {
                $data_query = $data_query->where('status', Status::EXCHANGE_PENDING);
            }
            if ($last_segment == 'hold') {
                $data_query = $data_query->where('status', Status::EXCHANGE_HOLD);
            }
            if ($last_segment == 'processing') {
                $data_query = $data_query->where('status', Status::EXCHANGE_PROCESSING);
            }
            if ($last_segment == 'approved') {
                $data_query = $data_query->where('status', Status::EXCHANGE_APPROVED);
            }
            if ($last_segment == 'refunded') {
                $data_query = $data_query->where('status', Status::EXCHANGE_REFUND);
            }
            if ($last_segment == 'canceled') {
                $data_query = $data_query->where('status', Status::EXCHANGE_CANCEL);
            }
            $data = $data_query->get()
                ->unique(fn($exchange) => $exchange->user->email)
                ->flatMap(function ($exchange) {
                    return [$exchange->user->email, $exchange->user->username];
                })
                ->values()
                ->toArray();
        }
        return ["status" => "success", "data" => $data];
    }


    public function createExchange($data, $user_id, $cacheCharges = false)
    {
        // $data= [
        //     "sending_currency" => null,
        //     "receiving_currency" => null,
        //     "received_rate" => null,
        //     "sending_amount" => null,
        //     "receiving_amount" => null,
        //     "selling_rate" => null,
        //     "buying_rate" => null,
        // ];

        $randomDay = rand(4, 6);

        // Pick a random hour, minute, and second
        $randomHour = rand(0, 23);
        $randomMinute = rand(0, 59);
        $randomSecond = rand(0, 59);

        // Build the random datetime
        $randomDate = Carbon::create(2025, 11, $randomDay, $randomHour, $randomMinute, $randomSecond);


        $sendCurrency = Currency::enabled()->availableForSell()->find($data['sending_currency']);
        $receiveCurrency = Currency::enabled()->availableForBuy()->find($data['receiving_currency']);
        $buyRateInput = $data['received_rate'];
        $sendAmount = $data['sending_amount'];
        $receiveAmount = $data['receiving_amount'];
        if (!$buyRateInput || $buyRateInput <= 0) {
            $buyRateInput = $sendCurrency->buy_at;
        }

        $exchange = new Exchange();
        $exchange->user_id = $user_id;
        $exchange->send_currency_id = $sendCurrency->id;
        $exchange->receive_currency_id = $receiveCurrency->id;
        $exchange->exchange_id = $data['exchange_id'];
        $exchange->custom_rate = $buyRateInput;

        $exchange->sending_charge = 0;
        $exchange->receiving_charge = 0;
        $exchange->status = Status::WITHDRAW_PENDING;
        $exchange->created_at = $this->excelToDateTime($data['placed_at']);

        $exchange->transaction_proof_data = $data['aditional_field_payment_prove'];
        $exchange->order_place_admin_id = $data['placed_by'];
        $exchange->transaction_type = 'EXCHANGE';


        // Start with base values

        $finalSendingAmount = $sendAmount;
        $finalReceivingAmount = $receiveAmount;

        // ✅ SELL Discount/Charge Logic — Apply All Matching Rules
        $sellCharges = GpayCurrencyDiscountChargeModel::where('currency_id', $sendCurrency->id)
            ->where('rules_for', 'sell')
            ->get();

        $sell_charge_percent = 0;
        $sell_charge_fixed = 0;
        $charges = [];

        foreach ($sellCharges as $sellCharge) {
            $from   = (float) $sellCharge->from;
            $to     = (float) $sellCharge->to;
            $amount = (float) $finalSendingAmount;

            if ($amount < $from || $amount > $to) {
                continue;
            }

            if (!empty($sellCharge->charge_percent)) {
                $sell_charge_percent += (float) $sellCharge->charge_percent;
                if($cacheCharges){
                    $charges['sell']['percent'][] = $sellCharge;
                } else {
                    $charges['sell']['percent'][] = $sellCharge->id;
                }
            }

            if (!empty($sellCharge->charge_fixed)) {
                $sell_charge_fixed += (float) $sellCharge->charge_fixed;
                if($cacheCharges){
                    $charges['sell']['fixed'][] = $sellCharge;
                }else{
                    $charges['sell']['fixed'][] = $sellCharge->id;
                }
            }
        }

        $sell_charge_percent_amount = ((float) $finalSendingAmount * $sell_charge_percent) / 100;
        $sell_charge_fixed_amount   = (float) $sell_charge_fixed;
        $sendingCharge = $sell_charge_percent_amount + $sell_charge_fixed_amount;

        // dump($sell_charge_percent);
        // dd($sell_charge_fixed);

        $exchange->sell_charge_percent = $sell_charge_percent;
        $exchange->sell_charge_fixed = $sell_charge_fixed;

        // ✅ BUY Discount/Charge Logic — Apply All Matching Rules
        $buyCharges = GpayCurrencyDiscountChargeModel::where('currency_id', $receiveCurrency->id)
            ->where('rules_for', 'buy')
            ->get();

        $receivingCharge = 0;
        $buy_charge_percent = 0;
        $buy_charge_fixed = 0;
        foreach ($buyCharges as $buyCharge) {
            $from = (float) $buyCharge->from;
            $to = (float) $buyCharge->to;
            $amount = (float) $finalReceivingAmount;

            if ($amount < $from || $amount > $to) {
                continue;
            }

            if (!empty($buyCharge->charge_percent)){
                $buy_charge_percent += $buyCharge->charge_percent;
                if($cacheCharges){
                    $charges['buy']['percent'][] = $buyCharge;
                } else {
                    $charges['buy']['percent'][] = $buyCharge->id;
                }
            }
            if (!empty($buyCharge->charge_fixed)) {
                $buy_charge_fixed += $buyCharge->charge_fixed;
                if($cacheCharges){
                    $charges['buy']['fixed'][] = $buyCharge;
                } else {
                    $charges['buy']['fixed'][] = $buyCharge->id;
                }
            }

            
        }
        $buy_charge_percent_amount = ((float) $finalReceivingAmount * (float) $buy_charge_percent) / 100; 
        $buy_charge_fixed_amount = (float) $buy_charge_fixed;
        $receivingCharge = $buy_charge_percent_amount + $buy_charge_fixed_amount;

        $exchange->buy_charge_percent = $buy_charge_percent;
        $exchange->buy_charge_fixed = $buy_charge_fixed;

        $exchange->sending_amount = $finalSendingAmount;
        $exchange->receiving_amount = $finalReceivingAmount;
        $exchange->sending_charge = $sendingCharge;
        $exchange->receiving_charge = $receivingCharge;

        $exchange->sell_rate = $receiveCurrency->sell_at;
        $exchange->buy_rate = $sendCurrency->buy_at;

        $exchange->customer_selling_rate = $data['selling_rate'];
        $exchange->customer_buying_rate = $data['buying_rate'];

        $exchange->charge = json_encode($charges);
        
        $exchange->save();

        return $exchange;
    }
    public function createDeposit($data, $user_id){
        // $data = [
        //     "sending_currency" => null,
        //     "receiving_currency" => null,
        //     "received_rate" => null,
        //     "sending_amount" => null,
        //     "receiving_amount" => null,
        //     "selling_rate" => null,
        //     "buying_rate" => null,
        // ];

        $randomDay = rand(4, 6);

        // Pick a random hour, minute, and second
        $randomHour = rand(0, 23);
        $randomMinute = rand(0, 59);
        $randomSecond = rand(0, 59);

        // Build the random datetime
        $randomDate = Carbon::create(2025, 11, $randomDay, $randomHour, $randomMinute, $randomSecond);

        $requestedCurrencyRate = $data['buying_rate'];
        
        $currency = Currency::enabled()->availableForBuy()->where('id', $data['sending_currency'])->firstOrFail();
        $selling_amount = $data['sending_amount'];

        $recv_currency = Currency::enabled()->availableForBuy()->where('currency_id', 'account_balance')->first();

        $amount = $data['receiving_amount'];

        $totalSellChargeAmount = 0;
        $sell_charges = GpayCurrencyDiscountChargeModel::where('rules_for','sell')->where('currency_id',$currency->id)->whereJsonContains('apply_for','deposit')->get();

        foreach($sell_charges as $charge){
            if($amount >= $charge->from && $amount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $amount;
                $totalSellChargeAmount += $charge_fixed_amount + $charge_percent_amount;
            }
        }

        $totalBuyChargeAmount = 0;
        $buy_charges = GpayCurrencyDiscountChargeModel::where('rules_for','buy')->where('currency_id',$recv_currency->id)->whereJsonContains('apply_for','deposit')->get();

        $buying_amount = $amount;

        foreach($buy_charges as $charge){
            if($buying_amount >= $charge->from && $buying_amount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $buying_amount;
                $totalBuyChargeAmount += $charge_fixed_amount + $charge_percent_amount;
            }
        }

        $deposit = new Exchange();
        $deposit->exchange_id = $data['exchange_id'];

        $deposit->receive_currency_id = $recv_currency->id;
        $deposit->send_currency_id = $currency->id;
        $deposit->sending_amount = $selling_amount;
        $deposit->receiving_amount = $buying_amount;

        $deposit->sending_charge = $totalSellChargeAmount;
        $deposit->receiving_charge = $totalBuyChargeAmount;
        $deposit->user_id = $user_id;
        $deposit->charge = gs('cur_text');
        $deposit->status = Status::WITHDRAW_PENDING;
        $deposit->admin_trx_no = getTrx();
        $deposit->buy_rate = $requestedCurrencyRate ? $requestedCurrencyRate : $currency->buy_at;
        $deposit->custom_rate = $requestedCurrencyRate ? $requestedCurrencyRate: $currency->buy_at;
        $deposit->transaction_type = 'DEPOSIT';

        $deposit->created_at = $this->excelToDateTime($data['placed_at']);
        $deposit->updated_at = $this->excelToDateTime($data['updated_at']);
        $deposit->transaction_proof_data = $data['aditional_field_payment_prove'];
        $deposit->order_place_admin_id = $data['placed_by'];
        $deposit->save();

        return $deposit;
    }
    public function createWithdraw($data, $user_id)
    {
        $amount = $data['sending_amount'];

        $requestedCurrencyRate = $data['selling_rate'];
        $user = User::find($user_id);

        $recv_currency = Currency::enabled()->availableForSell()->where('id', $data['receiving_currency'])->firstOrFail();
        $currency = Currency::enabled()->availableForSell()->where('currency_id', 'account_balance')->firstOrFail();
        
        $acc_charge = 0;
        $sell_charges = GpayCurrencyDiscountChargeModel::where('rules_for','sell')->where('currency_id',$currency->id)->whereJsonContains('apply_for','withdraw')->get();

        foreach($sell_charges as $charge){
            if($amount >= $charge->from && $amount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $amount;
                $acc_charge += $charge_fixed_amount + $charge_percent_amount;
            }
        }

        
        $getAmount = $data['receiving_amount'];


        $buyingCharge = 0;
        $buy_charges = GpayCurrencyDiscountChargeModel::where('rules_for','buy')->where('currency_id',$recv_currency->id)->whereJsonContains('apply_for','withdraw')->get();
        

        foreach($buy_charges as $charge){
            if($getAmount >= $charge->from && $getAmount <= $charge->to){
                $charge_fixed = $charge->charge_fixed? $charge->charge_fixed: 0;
                $charge_percent = $charge->charge_percent? $charge->charge_percent: 0;
                $charge_fixed_amount = $charge_fixed;
                $charge_percent_amount = ($charge_percent / 100) * $getAmount;
                $buyingCharge += $charge_fixed_amount + $charge_percent_amount;
            }
        }


        $withdraw = new Exchange();
        $withdraw->exchange_id = $data['exchange_id'];
        $withdraw->send_currency_id =  $currency->id;
        $withdraw->receive_currency_id = $recv_currency->id;
        $withdraw->sending_amount = $amount;
        $withdraw->receiving_amount = $getAmount;
        $withdraw->receiving_charge = $buyingCharge;
        $withdraw->sending_charge = $acc_charge;
        $withdraw->charge = gs('cur_text');
        $withdraw->buy_rate = $requestedCurrencyRate ? $requestedCurrencyRate : $recv_currency->sell_at;
        $withdraw->sell_rate = $currency->buy_at;

        
        $hiddenCharges = GpayHiddenChargeModel::where('currency_id', $recv_currency->id)->get();
        foreach ($hiddenCharges as $hidden) {
            if ($hidden->charge_percent && $hidden->charge_percent > 0) {
                $withdraw->hidden_charge_percent += $hidden->charge_percent;
            } 
            if ($hidden->charge_fixed && $hidden->charge_fixed > 0) {
                $withdraw->hidden_charge_fixed += $hidden->charge_fixed;
            }
        }


        $withdraw->user_id = $user->id;
        $withdraw->refund_amount = $amount + $acc_charge;
        $withdraw->admin_trx_no = getTrx();
        $withdraw->status = Status::WITHDRAW_PENDING;
        $withdraw->transaction_type = 'WITHDRAW';

        $withdraw->created_at = $this->excelToDateTime($data['placed_at']);
        $withdraw->updated_at = $this->excelToDateTime($data['updated_at']);
        
        $withdraw->transaction_proof_data = $data['aditional_field_payment_prove'];
        $withdraw->order_place_admin_id = $data['placed_by'];

        $withdraw->save();
        
        $user->balance -= $withdraw->refund_amount;
        $user->save();
        $user->balanceStatement()->create([
            "via" => "Withdraw Placed",
            "admin_id" => null,
            "before" => $user->balance + $withdraw->refund_amount,
            "after" => $user->balance,
            "exchange_id" => $withdraw->id,
        ]);

        return $withdraw;
    }
    function excelToDateTime($excelSerial) {
        $unixTime = ($excelSerial - 25569) * 86400;
        return Carbon::createFromTimestamp($unixTime);
    }
    function excel_to_exchange(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = Excel::toArray([], public_path('Excel4th.xls'));
            $rows = $data[0];
    
            $keys = array_shift($rows); // First row = column headers
            $exchanges = [];
    
            foreach ($rows as $row) {
                $exchanges[] = array_combine($keys, $row);
            }
            foreach ($exchanges as $exchange) {
                if (Exchange::where('exchange_id', $exchange['Exchange Id'])->exists()) {
                    continue;
                }
                $lines = preg_split("/\r\n|\n|\r/", $exchange['aditional_field_payment_prove']);

                $result = [];

                foreach ($lines as $line) {
                    // Skip empty lines
                    if (trim($line) === '') continue;

                    // Split by the first colon
                    $parts = explode(":", $line, 2);

                    // Trim spaces
                    $key = isset($parts[0]) ? trim($parts[0]) : '';
                    $value = isset($parts[1]) ? trim($parts[1]) : '';

                    $result[] = [$key, $value];
                }

                $json = [];
                foreach($result as $index => $result_item){
                    $json[] = [
                        'name' => $result_item[0],
                        'type' => 'text',
                        'value' => $result_item[1],
                    ];
                };


                $sending_currency = Currency::where('name', $exchange['Send Currency'])->first();
                $receiving_currency = Currency::where('name', $exchange['Received Currency'])->first();

                $placed_by = $exchange['placed_by'] == 'User'? null: Admin::where('username', $exchange['placed_by'])->first()->id ?? null; 
                if(!$sending_currency){
                    continue;
                }
                $user = User::where('username',$exchange['User Username'])->first();
                if(!$user){
                    continue;
                }
                $data= [
                    "exchange_id" => $exchange['Exchange Id'],
                    "sending_currency" => $sending_currency?->id ?? 1,
                    "receiving_currency" => $receiving_currency?->id ?? 1,
                    "sending_amount" => $exchange['Sending Amount'],
                    "receiving_amount" => $exchange['Receiving Amount'],
                    "selling_rate" => $exchange['Sending Amount'] / $exchange['Receiving Amount'],
                    "buying_rate" => $exchange['Receiving Amount'] / $exchange['Sending Amount'],
                    "received_rate" => $exchange['Receiving Amount'] / $exchange['Sending Amount'],
                    "transaction_type" => $exchange['Transaction Type'],
                    "placed_by" => $placed_by,
                    "aditional_field_payment_prove" => $json,
                    "placed_at" => $exchange['placed_at'],
                    "updated_at" => $exchange['updated_at']
                ];
                if (str_starts_with($exchange['Exchange Id'], 'WD-')) {
                    $this->createWithdraw($data,$user->id);
                } elseif (str_starts_with($exchange['Exchange Id'], 'DP-')) {
                    $this->createDeposit($data, $user->id);
                } else {
                    $this->createExchange($data,$user->id);
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        $notify[] = ['success', 'Excel Data Uploaded To Database'];
        return back()->withNotify($notify);
    }
    function fix_currency(){
        $currencies = Currency::all();
        foreach($currencies as $currency){
            $currency->currency_id = Str::snake($currency->name);
            $currency->save();
        }
        return "Currency Fixed";
    }
}