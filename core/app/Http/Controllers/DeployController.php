<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    public function deploy(Request $request, $token)
    {
        if ($token !== env('DEPLOY_TOKEN')) {
            abort(403, 'Unauthorized');
        }

        // $path = '/var/www/html/gpay-update-11'; // or hardcode: /var/www/html/gpay-update-11
        $cmd = 'export HOME=/var/www && cd '.base_path().' && git pull origin main 2>&1';
        exec($cmd, $output, $status);

        Log::info('Deploy Output 1:', $output);
        Log::info('Deploy Output 2 :'.base_path());
        Log::info('Deploy Output 3:'.$cmd);

        // Optional: Clear caches
        Artisan::call('optimize:clear');
        // Artisan::call('route:cache');

        if ($status !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Git pull failed',
                'output' => $output,
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Deployment successful',
            'output' => $output,
        ]);
    }
}
