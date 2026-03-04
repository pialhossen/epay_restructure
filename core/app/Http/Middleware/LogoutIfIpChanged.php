<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutIfIpChanged
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $currentIp = $request->ip();
            $sessionIp = session('user_ip');

            // If no IP stored yet, save it
            if (!$sessionIp) {
                session(['user_ip' => $currentIp]);
            }

            // If IP changed, logout
            elseif ($sessionIp !== $currentIp) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                $notify[] = ['error', 'You have been logged out because your IP address changed.'];

                return redirect()
                    ->route('user.login')
                    ->withNotify($notify);
            }
        }

        return $next($request);
    }
}
