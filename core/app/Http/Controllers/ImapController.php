<?php

namespace App\Http\Controllers;

use App\Models\ForwardEmail;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;


class ImapController extends Controller
{
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