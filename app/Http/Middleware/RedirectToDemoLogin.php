<?php

namespace App\Http\Middleware;

use App\Http\Controllers\DemoLoginController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps every login entry point of the demo prefilled. Guests are sent through
 * the demo controller first, which provisions a demo user and puts its
 * credentials into the session, so the login screen never renders empty fields.
 */
class RedirectToDemoLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && Auth::guest() && $this->needsDemoCredentials($request)) {
            return redirect('/');
        }

        return $next($request);
    }

    /**
     * noerd's own login screen never carries demo credentials, and the demo login
     * screen only carries them while the session holds a demo user that still
     * exists — `demo:cleanup` and `demo:reset` delete them behind live sessions.
     */
    private function needsDemoCredentials(Request $request): bool
    {
        if ($request->routeIs('noerd.login')) {
            return true;
        }

        return $request->routeIs('login') && ! DemoLoginController::sessionHasDemoUser();
    }
}
