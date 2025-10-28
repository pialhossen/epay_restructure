<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OneDeviceLogin
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $currentSessionId = session()->getId();
            
            if ($user->session_id && $user->session_id !== $currentSessionId) {
                Auth::guard('web')->logout();
                session()->invalidate();
                session()->regenerateToken();

                $notify[] = ['error', 'You have been logged out because your account was accessed from another device.'];

                return redirect()
                    ->route('user.login')
                    ->withNotify($notify);
            }
        }

        return $next($request);
    }
}