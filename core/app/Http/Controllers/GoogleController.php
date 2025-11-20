<?php

namespace App\Http\Controllers;

use App\Models\ForwardEmail;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;


class GoogleController extends Controller
{
    public function fetchEmails(Request $request)
    {
        // quick env check
        if (!env('IMAP_USERNAME') || !env('IMAP_PASSWORD')) {
            $msg = 'Set IMAP_USERNAME and IMAP_PASSWORD in .env (use a Gmail app password).';
            Log::error($msg);
            return response()->json(['status' => 'error', 'message' => $msg], 500);
        }

        $client = Client::account('default');

        // connect, retry once with cert validation disabled if configured
        try {
            $client->connect();
        } catch (\Throwable $e) {
            if (env('IMAP_VALIDATE_CERT', true)) {
                config(['imap.accounts.' . config('imap.default') . '.validate_cert' => false]);
                $client = Client::account('default');
                $client->connect();
            } else {
                Log::error('IMAP connect failed: ' . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'IMAP connect failed'], 500);
            }
        }

        $inbox = $client->getFolder('INBOX');
        $fromFilter = env('IMAP_FILTER_FROM');

        // fetch all unseen, then filter by sender if specified
        $messages = $inbox->query()->unseen()->get();
        
        // apply sender filter on results (Webklex from() may not work reliably)
        if ($fromFilter) {
            $messages = $messages->filter(function ($m) use ($fromFilter) {
                $f = $m->getFrom();
                if (is_iterable($f)) {
                    foreach ($f as $addr) {
                        if (isset($addr->mail) && stripos($addr->mail, $fromFilter) !== false) {
                            return true;
                        }
                    }
                    return false;
                }
                return stripos((string)$f, $fromFilter) !== false;
            });
        }

        $summaries = [];
        foreach ($messages as $m) {
            // from: extract only the email address, no name or unicode
            $f = $m->getFrom();
            $fromStr = '(unknown)';
            if (is_iterable($f)) {
                foreach ($f as $addr) {
                    if (isset($addr->mail)) {
                        $fromStr = $addr->mail;
                        break;
                    }
                }
            } else {
                $fromStr = (string)$f ?: '(unknown)';
            }
            // extract only email from "Name <email>" format
            if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $fromStr, $matches)) {
                $fromStr = $matches[1];
            }

            // subject: returns object, cast to string
            $subj = $m->getSubject();
            $subject = (string)$subj ?: '(no subject)';

            // body: text or HTML stripped, cleaned of junk chars
            $text = trim((string)$m->getTextBody()) ?: trim(strip_tags((string)$m->getHTMLBody()));
            // remove \r\n\t and invisible Unicode chars, collapse whitespace
            $text = preg_replace('/[\r\n\t\x{200B}-\x{200D}\x{FEFF}]/u', ' ', $text);
            $text = preg_replace('/\s+/', ' ', trim($text));

            // date: may return DateTime or string
            $dateStr = null;
            if (method_exists($m, 'getDate')) {
                try {
                    $d = $m->getDate();
                    if ($d instanceof \DateTime) {
                        $dateStr = $d->format('c');
                    } elseif ($d) {
                        $dateStr = (string)$d;
                    }
                } catch (\Throwable $e) {
                    $dateStr = null;
                }
            }

            $summaries[] = [
                'id' => method_exists($m, 'getMessageId') ? $m->getMessageId() : null,
                'from' => $fromStr,
                'subject' => $subject,
                'date' => $dateStr,
                'body_preview' => mb_substr($text, 0, 1000),
            ];
        }

        $client->disconnect();
        return response()->json(['status' => 'ok', 'data' => $summaries]);
    }
    public function imap_config(){
        $settings = GeneralSetting::find(1);
        $payload = json_decode($settings->imap_config,true);
        $payload['pageTitle'] = "Imap Config";
        return view('admin.imap', $payload);
    }
    public function save_imap_config(Request $request){
        $request->validate([
            'imap_account' => 'required',
            'imap_host' => 'required',
            'imap_port' => 'required',
            'imap_encryption' => 'required',
            'imap_validate_cert' => 'required',
            'imap_username' => 'required',
            'imap_password' => 'required',
            'imap_protocol' => 'required',
            'imap_filter_from' => 'required',
        ]);
        $imap_config = [];
        $imap_config['imap_account'] = $request->imap_account;
        $imap_config['imap_host'] = $request->imap_host;
        $imap_config['imap_port'] = $request->imap_port;
        $imap_config['imap_encryption'] = $request->imap_encryption;
        $imap_config['imap_validate_cert'] = $request->imap_validate_cert;
        $imap_config['imap_username'] = $request->imap_username;
        $imap_config['imap_password'] = $request->imap_password;
        $imap_config['imap_protocol'] = $request->imap_protocol;
        $imap_config['imap_filter_from'] = $request->imap_filter_from;
        $settings = GeneralSetting::find(1);
        $settings->imap_config = json_encode($imap_config);
        $settings->save();
        $notify[] = ['success', 'IMAP Configuration Update Success'];
        return redirect()->back()->withNotify($notify);
    }
}