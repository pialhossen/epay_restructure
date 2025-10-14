<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Form;
use App\Models\User;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\Referral;
use App\Constants\Status;
use App\Models\BlockLine;
use App\Models\UsersModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\CommissionLog;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\UserBlockListModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\GpayExchangeLogModel;
use Illuminate\Support\Facades\Auth;

class ExchangeController extends Controller
{
    private $user;
    public function __construct()
    {
        Auth::shouldUse('admin');
        $this->user = auth()->user();
        $this->check_permission("View - Exchange Menu");
    }
    public function check_update_permission(){
        if($this->user->id == 1 || $this->user->can('Update - Exchange')){
            return 0;
        }
        abort(403);
    }
    public function check_exchnage_updated_at($exchange){
        if(auth()->guard('admin')->user()->id == 1){
            return false;
        }
        if($exchange->status_at && $exchange->status_at->diffInMinutes(now()) >= (60 * gs('exchange_lock_time'))){
            return true;
        }
        return false;
    }
    public static function checkPermission($user, $scope){
        if(($scope == 'pending' || $scope == 'Pending Exchange') && $user->can("View - Pending Exchange")){
            return true;
        }
        if(($scope == 'hold' || $scope == 'Hold Exchange') && $user->can("View - Hold Exchange")){
            return true;
        }
        if(($scope == 'processing' || $scope == 'Processing Exchange') && $user->can("View - Processing Exchnage")){
            return true;
        }
        if(($scope == 'approved' || $scope == 'Approved Exchange') && $user->can("View - Approved Exchange")){
            return true;
        }
        if(($scope == 'canceled' || $scope == 'Canceled Exchange') && $user->can("View - Canceled Exchnage")){
            return true;
        }
        if(($scope == 'refunded' || $scope == 'Refunded Exchange') && $user->can("View - Refunded Exchange")){
            return true;
        }
        if(($scope == 'list' || $scope == 'All Exchange') && $user->can("View - All Exchange")){
            return true;
        }
        return false;
    }
    public function index(Request $request, $scope)
    {
        if($this->user->id != 1){
           if(!$this->checkPermission($this->user, $scope)){
                abort(403);
           }
        }
        try {
            $exchanges = Exchange::$scope()->with('user', 'sendCurrency', 'receivedCurrency');

            if($request->exchange_id){
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
            if($request->transaction_type){
                $exchanges = $exchanges->where('transaction_type', $request->transaction_type);
            }
            if($request->send_currency_id){
                $exchanges = $exchanges->whereIn('send_currency_id', $request->send_currency_id);
            }
            if($request->receive_currency_id){
                $exchanges = $exchanges->whereIn('receive_currency_id', $request->receive_currency_id);
            }
            if ($request->created_from && $request->created_to) {
                $exchanges = $exchanges->whereBetween('created_at', [
                    date('Y-m-d 00:00:00', strtotime($request->created_from)),
                    date('Y-m-d 23:59:59', strtotime($request->created_to))
                ]);
            }
            if(request()->query('sort')){
                [$column, $direction] = explode(':', request()->query('sort'));
                $exchanges = $exchanges->orderBy($column, $direction); 
            } else {
                $exchanges = $exchanges->orderBy('created_at', 'desc'); 
            }
            $exchanges = $exchanges->paginate(getPaginate($request->itemsPerPage? $request->itemsPerPage: null ));
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

    public function details($id)
    {
        $this->check_permission('View - Exchange');
        $exchange = Exchange::where('id', $id)->firstOrFail();
        $pageTitle = 'Exchange Details: ' . $exchange->exchange_id;
        $exchangeLog = GpayExchangeLogModel::where('exchange_id', $id)
            ->with(['adminUser:id,name'])
            ->orderBy('id', 'desc')
            ->get();

        $kyc_form = Form::where('act', 'kyc')->first();
        $user = $exchange->user;
        $user_kyc_data = [];
        foreach($user->kyc_data as $kyc_data){
            $user_kyc_data[$kyc_data->name] = $kyc_data;
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
        $this->check_update_permission();
        $exchange = Exchange::where('id', $id)->firstOrFail();

        if($this->check_exchnage_updated_at($exchange)){
            $notify[] = ['error', 'This order has been locked as it was last updated more than 1 hours ago.'];
            return back()->withNotify($notify);
        }

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

        if($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)){
            $user->balance -= $exchange->sending_amount + $exchange->sending_charge;
            $user->save();
        }
        if($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)){
            $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
            $user->save();
        }

        $newExchangeLog = new GpayExchangeLogModel;

        $newExchangeLog->exchange_id = $id;
        $newExchangeLog->exchange_status = 'Pending';
        $newExchangeLog->updated_by = auth()->user()->id;
        $newExchangeLog->updated_date = Carbon::now();
        $newExchangeLog->save();

        notify($exchange->user, 'PENDING_EXCHANGE', [
            'exchange' => $exchange->exchange_id,
            'reason' => $exchange->admin_feedback,
        ]);

        $notify[] = ['success', 'Exchange pending successfully'];

        return back()->withNotify($notify);
    }
    public function cancel(Request $request, $id)
    {
        $this->check_update_permission();
        $request->validate([
            'cancel_reason' => 'required',
        ]);

        $exchange = Exchange::where('id', $id)->firstOrFail();
        if($this->check_exchnage_updated_at($exchange)){
            $notify[] = ['error', 'This order has been locked as it was last updated more than 1 hours ago.'];
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

        if($exchange->transaction_type == 'WITHDRAW' && $previous_status != Status::EXCHANGE_REFUND){
            $user->balance += $exchange->refund_amount;
            $user->save();
        }
        if($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)){
            $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
            $user->save();
        }

        $newExchangeLog = new GpayExchangeLogModel;

        $newExchangeLog->exchange_id = $id;
        $newExchangeLog->exchange_status = 'Cancel';
        $newExchangeLog->updated_by = auth()->user()->id;
        $newExchangeLog->updated_date = Carbon::now();
        $newExchangeLog->save();

        notify($exchange->user, 'CANCEL_EXCHANGE', [
            'exchange' => $exchange->exchange_id,
            'reason' => $exchange->admin_feedback,
        ]);

        $notify[] = ['success', 'Exchange canceled successfully'];

        return back()->withNotify($notify);
    }

    public function refund(Request $request, $id)
    {
        $this->check_update_permission();
        $request->validate([
            'refund_reason' => 'required',
        ]);

        $exchange = Exchange::where('id', $id)->firstOrFail();
        if($this->check_exchnage_updated_at($exchange)){
            $notify[] = ['error', 'This order has been locked as it was last updated more than 1 hours ago.'];
            return back()->withNotify($notify);
        }
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

        if($exchange->transaction_type == 'WITHDRAW' && $previous_status != Status::EXCHANGE_CANCEL){
            $user->balance += $exchange->refund_amount;
            $user->save();
        }

        if($exchange->transaction_type == 'DEPOSIT' && $previous_status == Status::EXCHANGE_APPROVED){
            $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
            $user->save();
        }


        $newExchangeLog = new GpayExchangeLogModel;

        $newExchangeLog->exchange_id = $id;
        $newExchangeLog->exchange_status = 'Refund';
        $newExchangeLog->updated_by = auth()->user()->id;
        $newExchangeLog->updated_date = Carbon::now();
        $newExchangeLog->save();

        notify($exchange->user, 'EXCHANGE_REFUND', [
            'exchange' => $exchange->exchange_id,
            'currency' => $exchange->sendCurrency->cur_sym,
            'amount' => showAmount($exchange->sending_amount, currencyFormat: false),
            'method' => $exchange->sendCurrency->name,
            'reason' => $exchange->admin_feedback,
        ]);

        $notify[] = ['success', 'Exchange refunded successfully'];

        return back()->withNotify($notify);
    }

    public function hold($id)
    {
        $this->check_update_permission();
        $exchange = Exchange::findOrFail($id);
        if($this->check_exchnage_updated_at($exchange)){
            $notify[] = ['error', 'This order has been locked as it was last updated more than 1 hours ago.'];
            return back()->withNotify($notify);
        }
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


        if($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)){
            $user->balance -= $exchange->refund_amount;
            $user->save();
        }
        if($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)){
            $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
            $user->save();
        }

        $newExchangeLog = new GpayExchangeLogModel;

        $newExchangeLog->exchange_id = $id;
        $newExchangeLog->exchange_status = 'Hold';
        $newExchangeLog->updated_by = auth()->user()->id;
        $newExchangeLog->updated_date = Carbon::now();
        $newExchangeLog->save();

        $notify[] = ['success', 'Exchange marked as hold'];

        return back()->withNotify($notify);
    }

    public function processing($id)
    {
        $this->check_update_permission();
        $exchange = Exchange::findOrFail($id);
        if($this->check_exchnage_updated_at($exchange)){
            $notify[] = ['error', 'This order has been locked as it was last updated more than 1 hours ago.'];
            return back()->withNotify($notify);
        }
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

        if($exchange->transaction_type == 'WITHDRAW' && ($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND)){
            $user = $exchange->user;
            $user->balance -= $exchange->refund_amount;
            $user->save();
        }
        if($exchange->transaction_type == 'DEPOSIT' && ($previous_status == Status::EXCHANGE_APPROVED)){
            $user = $exchange->user;
            $user->balance -= $exchange->receiving_amount - $exchange->receiving_charge;
            $user->save();
        }

        $newExchangeLog = new GpayExchangeLogModel;

        $newExchangeLog->exchange_id = $id;
        $newExchangeLog->exchange_status = 'Processing';
        $newExchangeLog->updated_by = auth()->user()->id;
        $newExchangeLog->updated_date = Carbon::now();
        $newExchangeLog->save();

        $notify[] = ['success', 'Exchange marked as processing'];

        return back()->withNotify($notify);
    }

    public function approve(Request $request, $id)
    {
        $this->check_update_permission();
        // try {
            DB::beginTransaction();
            $request->validate([
                'transaction' => 'required',
            ]);

            $exchange = Exchange::with("sendCurrency","receivedCurrency")->where('id', $id)->first();
            if($this->check_exchnage_updated_at($exchange)){
                $notify[] = ['error', 'This order has been locked as it was last updated more than 1 hours ago.'];
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

            if (!$this->canBeModifiedByCurrentUser($exchange)) {
                $notify[] = ['error', 'Only admin can modify after 30 minutes.'];
                return back()->withNotify($notify);
            }

            if (!$exchange) {
                return back()->withErrors(['error' => 'Exchange not found or not in pending status.']);
            }
            $totalReceivedAmount = $appliedHiddenCharge + $finalReceivingAmount + (-1 * $exchange->receiving_charge);
            if (
                $totalReceivedAmount > $exchange->receivedCurrency->reserve && 
                (int)$exchange->receivedCurrency->neg_bal_allowed !== 1 && 
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

                $user->balance += $exchange->receiving_amount - $exchange->receiving_charge;
                $user->save();

            } elseif ($exchange->transaction_type == 'WITHDRAW'){
                $sendCurrency = $exchange->sendCurrency;
                $oldSendReserve = $sendCurrency->reserve;
                $sendCurrency->reserve -= ($exchange->sending_amount + $exchange->sending_charge);
                $sendCurrency->save();

                $receivedCurrency = $exchange->receivedCurrency;
                $oldReceivedReserve = $receivedCurrency->reserve;
                $receivedCurrency->reserve -= ($exchange->receiving_amount - $exchange->receiving_charge) + $appliedHiddenCharge;
                $receivedCurrency->save();

                
                if($previous_status == Status::EXCHANGE_CANCEL || $previous_status == Status::EXCHANGE_REFUND){
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
                $this->levelCommission($user->id, $amount,$exchange->id, 'exchange_commission');
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

            // $user = [
            //     'username' => $request->email,
            //     'email' => $request->email,
            //     'fullname' => $receiverName,
            // ];
            // notify($user, 'APPROVED_EXCHANGE', [
            //     'subject' => $subject,
            //     'message' => $message,
            // ], ['email'], false);

            // ========== LOG ==========
            $newExchangeLog = new \App\Models\GpayExchangeLogModel;
            $newExchangeLog->exchange_id = $id;
            $newExchangeLog->exchange_status = 'Approved';
            $newExchangeLog->updated_by = auth()->user()->id;
            $newExchangeLog->updated_date = now();
            $newExchangeLog->save();

            DB::commit();

            $notify[] = ['success', 'Exchange approved successfully'];
            return back()->withNotify($notify);

        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     \Log::error('Exchange approval failed', [
        //         'exchange_id' => $id,
        //         'error_message' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString(),
        //     ]);

        //     return back()->withErrors(['error' => 'An error occurred while approving the exchange.']);
        // }
    }

    public function levelCommission($id, $amount, $exchange_id, $commissionType = '')
    {
        $usr = $id;

        $me = User::find($usr);
        $refer = User::find($me->ref_by);
        if ($refer == '') {
            return 1;
        }

        $commission = Referral::where('level', $refer->commission_level)->first();
        if(!$commission){
            return 1;
        }
        $i = $commission->level;
        if ($commission == null) {
            return 1;
        }

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
            'user_admin' => $user->inRoles(['administrator']),
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
            $this->levelCommissionRevert($user->id, $amount,$exchange->id, 'exchange_commission');
        }

        if ($exchange->transaction_type == 'DEPOSIT') {
            $sendCurrency = $exchange->sendCurrency;
            $sendCurrency->reserve -= ($exchange->sending_amount + $exchange->sending_charge);
            $sendCurrency->save();

            $receivedCurrency = $exchange->receivedCurrency;
            $receivedCurrency->reserve -= ($exchange->receiving_amount - $exchange->receiving_charge);
            $receivedCurrency->save();
        } elseif($exchange->transaction_type == 'WITHDRAW'){
            $sendCurrency = $exchange->sendCurrency;
            $sendCurrency->reserve += ($exchange->sending_amount + $exchange->sending_charge);
            $sendCurrency->save();
            
            $receivedCurrency = $exchange->receivedCurrency;
            $receivedCurrency->reserve += ($exchange->receiving_amount + (-1 * $exchange->receiving_charge) + $appliedHiddenCharge);
            $receivedCurrency->save();

        }else{
            $sendCurrency = $exchange->sendCurrency;
            $sendCurrency->reserve -= ($exchange->sending_amount + $exchange->sending_charge);

            
            
            $receivedCurrency = $exchange->receivedCurrency;
            $receivedCurrency->reserve += ($exchange->receiving_amount + (-1 * $exchange->receiving_charge) + $appliedHiddenCharge);

            $sendCurrency->save();
            $receivedCurrency->save();
        }
    }

}
