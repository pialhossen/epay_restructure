<?php

namespace App\Http\Controllers\Gateway\Paymentapi;

use App\Models\Deposit;
use App\Models\Gateway;
use App\Lib\CurlRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PayOn\PaymentController;

class ProcessController extends Controller
{
    public static function process($deposit)
    {
        
        $gatewayAcc = json_decode($deposit->gatewayCurrency()->gateway_parameter);

        $request = [
            "merchant_id" => $gatewayAcc->merchant_id,
            "amount"      => $deposit->final_amount,
            "currency"    => $deposit->method_currency,
            "description" => "Deposit to " . gs('site_name'),
            "customer"    => [
                "name"  => auth()->user()->username ?? "Customer",
                "email" => auth()->user()->email ?? "customer@example.com",
                "phone" => auth()->user()->mobile ?? ""
            ],
            "metadata"    => [
                "order_id"     => $deposit->trx,
                "custom_field" => "deposit"
            ],
            "return_url"  => route('ipn.payment.success'),
            "cancel_url"  => route('ipn.payment.cancel'),
            "webhook_url" => route('ipn.payment.webhook'),
            "token"       => $gatewayAcc->public_key
        ];

        $ch = curl_init("https://payonbank.com/bank/api/payment/request");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            dd("CURL ERROR: " . curl_error($ch));
        }
        curl_close($ch);

        $result = json_decode($response, true);
        if($result == null){
            $url = null;
            if (preg_match('/<meta\s+http-equiv=["\']?refresh["\']?[^>]*content=["\']?[^>]*url\s*=\s*\'?\"?([^\'"\s>]+)\'?\"?/i', $response, $m)) {
                $url = html_entity_decode($m[1]);
            }
            // 3) fallback: anchor href
            elseif (preg_match('/<a\s+[^>]*href=["\']([^"\']+)["\']/i', $response, $m2)) {
                $url = html_entity_decode($m2[1]);
            } else {
                $url = null;
            }
            return ["redirect" => true, "view" => true, "redirect_url" => $url];
        }

        if (!empty($result['payment_link'])) {
            return [
                'redirect' => true,
                'redirect_url' => $result['payment_link']
            ];
        } else {
            return [
                'error' => true,
                'message' => $result['message'] ?? 'Something went wrong'
            ];
        }
    }




    public function webhook(Request $request)
    {
        $data = $request->all();

        // Example payload from PayOnBank:
        // {
        //   "status": "paid",
        //   "amount": 1.00,
        //   "currency": "USD",
        //   "metadata": { "order_id": "123456" }
        // }

        $deposit = Deposit::where('trx', $data['metadata']['order_id'] ?? null)->first();

        if ($deposit
            && strtolower($data['status']) === 'paid'
            && (float)$data['amount'] == (float)$deposit->final_amount
        ) {
            PaymentController::userDataUpdate($deposit);
        }

        return response()->json(['status' => 'ok']);
    }

    public function success(Request $request)
    {
        // Customer was redirected back after completing payment
        // You can show a success page or double-check payment status if you want
        
        $pageTitle = "Payment Success";
        return view('payment.success', [
            'message' => 'Your payment was successful! Thank you.',
            'pageTitle' => $pageTitle,
        ]);
    }

    public function cancel(Request $request)
    {
        $pageTitle = "Payment Cancel";
        return view('payment.cancel', [
            'message' => 'Your payment was canceled. Please try again.',
            'pageTitle' => $pageTitle,
        ]);
    }

    public function ipn()
    {
        $binance = Gateway::where('alias', 'Binance')->first();
        $binanceAcc = json_decode($binance->gateway_parameters);
        $deposits = Deposit::initiated()->where('method_code', $binance->code)->where('created_at', '>=', now()->subHours(24))->orderBy('last_cron')->limit(10)->get();
        $apiKey = $binanceAcc->api_key->value;
        $secretKey = $binanceAcc->secret_key->value;
        $url = 'https://bpay.binanceapi.com/binancepay/openapi/v2/order/query';

        foreach ($deposits as $deposit) {
            $deposit->last_cron = time();
            $deposit->save();
            $nonce = Str::random(32);
            $timestamp = round(microtime(true) * 1000);

            $request = [
                'merchantTradeNo' => $deposit->trx,
            ];

            $jsonRequest = json_encode($request);
            $payload = $timestamp."\n".$nonce."\n".$jsonRequest."\n";
            $signature = strtoupper(hash_hmac('SHA512', $payload, $secretKey));
            $headers = [];
            $headers[] = 'Content-Type: application/json';
            $headers[] = "BinancePay-Timestamp: $timestamp";
            $headers[] = "BinancePay-Nonce: $nonce";
            $headers[] = "BinancePay-Certificate-SN: $apiKey";
            $headers[] = "BinancePay-Signature: $signature";

            $result = CurlRequest::curlPostContent($url, $request, $headers);
            $result = json_decode($result);
            if (@$result->data && @$result->data->status == 'PAID' && @$result->data->orderAmount == $deposit->final_amount) {
                PaymentController::userDataUpdate($deposit);
            }

        }
    }
}
