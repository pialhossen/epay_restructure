<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Adminlockout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->guard('admin')->check()){
            $user = auth()->guard('admin')->user();
            // dump($user->is_active);
            // dd( $user->id == 1);
            if($user->is_active || $user->id == 1){
                return $next($request);
            }
            auth()->guard('admin')->logout();
            $request->session()->invalidate();
            $notify[] = ['error', 'Your account has been deactivated by the Super Admin.<br> Please contact the Super Admin for further assistance or reactivation.'];
            return redirect()->route('admin.login')->withNotify($notify);
        }
        return $next($request);
    }
}
