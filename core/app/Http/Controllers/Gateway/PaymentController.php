<?php

namespace App\Http\Controllers\Gateway;

use App\Models\User;
use App\Models\Deposit;
use App\Constants\Status;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\Log;
use App\Events\ExchangeNotification;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    public function depositConfirm()
    {
        $track = session()->get('Track');

        $deposit = Deposit::where('trx', $track)
            ->where('status', Status::PAYMENT_INITIATE)
            ->orderBy('id', 'DESC')
            ->with('gateway')
            ->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm');
        }

        $dirName = $deposit->gateway->alias;
        $new = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);

        // ✅ Always convert to object safely
        if (is_array($data)) {
            $data = (object) $data;
        } elseif (is_string($data)) {
            $decoded = json_decode($data);
            $data = (json_last_error() === JSON_ERROR_NONE) ? $decoded : (object) [];
        } elseif (!is_object($data)) {
            $data = (object) $data;
        }

       

        // Debug check
        if (!isset($data->view)) {
            \Log::error('DepositConfirm: Missing view in $data', [$data]);
            abort(500, 'Payment gateway did not return a valid view.');
        }

        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return back()->withNotify($notify);
        }

        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        if (@$data->session) {
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Payment Confirm';

        return view("Template::" . $data->view, compact('data', 'pageTitle', 'deposit'));
    }



    public static function userDataUpdate($deposit, $isManual = null)
    {
        if ($deposit->status == Status::PAYMENT_INITIATE || $deposit->status == Status::PAYMENT_PENDING) {
            $deposit->status = Status::PAYMENT_SUCCESS;
            $deposit->save();

            $user = User::find($deposit->user_id);

            if ($deposit->exchange) {
                $exchange = $deposit->exchange;
                $exchange->automatic_payment_status = Status::YES;
                $exchange->status = Status::EXCHANGE_PENDING;
                $exchange->save();
            }

            $methodName = $deposit->methodName();

            if (! $isManual) {
                $adminNotification = new AdminNotification;
                $adminNotification->user_id = $user->id;
                $adminNotification->title = 'Payment successful via ' . $methodName;
                $adminNotification->click_url = urlPath('admin.exchange.details', $exchange->id);
                $adminNotification->save();
            }

            try {
                broadcast(new ExchangeNotification($exchange));
            } catch (\Throwable $e) {
                Log::warning('Pusher failed: ' . $e->getMessage());
            }

            notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
                'method_name' => $methodName,
                'method_currency' => $deposit->method_currency,
                'method_amount' => showAmount($deposit->final_amount, currencyFormat: false),
                'amount' => showAmount($deposit->amount, currencyFormat: false),
                'charge' => showAmount($deposit->charge, currencyFormat: false),
                'rate' => showAmount($deposit->rate, currencyFormat: false),
                'trx' => $deposit->trx,
                'post_balance' => showAmount($user->balance, currencyFormat: false),
            ]);
        }
    }

    public function appDepositConfirm($hash)
    {
        try {
            $id = decrypt($hash);
        } catch (\Exception $ex) {
            abort(404);
        }

        $data = Deposit::where('id', $id)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->firstOrFail();
        $user = User::findOrFail($data->user_id);

        auth()->login($user);

        session()->put('Track', $data->trx);

        return to_route('user.deposit.confirm');
    }
}
