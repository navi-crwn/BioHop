<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** A suspended HOP ID (set at the hub) is signed out of this app on its next request. */
class CheckSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->isSuspended()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'This account is suspended. Contact the HOP administrator at '.config('hop.hub_url').'.');
        }

        return $next($request);
    }
}
