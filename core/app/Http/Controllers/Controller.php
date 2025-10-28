<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Events\ExchangeNotification;
use App\Models\DailyProfitLossDailyCache;
use App\Models\FinalProfitLossDailyCache;

abstract class Controller
{
    public function __construct()
    {
        $className = get_called_class();
        // dump(Carbon::now()->format('d/m/y h:i:s A'));
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
        try {
            broadcast(new ExchangeNotification($exchange));
        } catch (\Throwable $e) {
            // Ignore the Pusher failure silently
            Log::warning('Pusher failed: ' . $e->getMessage());
        }
        return ['status' => 'success', 'message' => 'Alert Stop Event Broadcasted'];
    }
    public function check_permission($ability){
        if(auth()->guard('admin')->user()->cannot($ability) && auth()->guard('admin')->user()->id != 1){
            abort(403);
        }
        return 0;
    }
}
