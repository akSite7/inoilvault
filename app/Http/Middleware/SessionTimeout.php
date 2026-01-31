<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timeout = (int) $request->session()->get('session_timeout_seconds', 10);
            $lastActivity = (int) $request->session()->get('last_activity_ts', now()->timestamp);

            if (now()->timestamp - $lastActivity > $timeout) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            $request->session()->put('last_activity_ts', now()->timestamp);
        }

        return $next($request);
    }
}
