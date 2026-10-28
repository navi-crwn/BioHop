<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            // Not signed in: through the hub, like hop.auth.
            return $request->expectsJson() ? abort(401) : redirect()->to(config('hop-auth.routes.start', '/auth/sso').'?next='.rawurlencode($request->getRequestUri()));
        }
        if (! $user->isAdmin()) {
            abort(403);
        }
        if ($user->isSuspended()) {
            auth()->logout();
            abort(403, 'Account suspended.');
        }

        return $next($request);
    }
}
