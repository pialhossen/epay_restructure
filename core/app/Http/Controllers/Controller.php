<?php

namespace App\Http\Controllers;

use App\Events\ExchangeNotification;
use App\Models\DailyProfitLossDailyCache;
use App\Models\FinalProfitLossDailyCache;

abstract class Controller
{
    public function __construct()
    {
        $className = get_called_class();

    }

    public static function middleware()
    {
        return [];
    }
    public function getPreviousFinalProfitLossDailyCache($date){
        $data = FinalProfitLossDailyCache::whereDate('created_at', $date)->first();
        if($data){
            return json_decode($data->json_data);
        }
        return false;
    }
    public function getPreviousDailyProfitLossDailyCache($date){
        $data = DailyProfitLossDailyCache::whereDate('created_at', $date)->first();
        if($data){
            return json_decode($data->json_data);
        }
        return false;
    }
    public function sendNotificationToTheAdmin($exchange){
        broadcast(new ExchangeNotification($exchange));
    }
}
