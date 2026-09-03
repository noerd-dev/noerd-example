<?php

namespace App\Http\Middleware;

use App\Http\Controllers\DemoLoginController;
use Closure;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Noerd\Helpers\NoerdAuth;
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
        if (! $request->isMethod('GET') || ! $this->onALoginScreen($request)) {
            return $next($request);
        }

        // Laravel's `guest` middleware watches the default guard, which is not
        // noerd's unless NOERD_AUTH_DEFAULT is set, so a logged-in visitor can
        // reach the login screen. Send them where they belong instead.
        if (NoerdAuth::user() !== null) {
            return redirect()->route('noerd.apps');
        }

        // Reaching a login screen means noerd sees a guest, so a session another
        // guard still holds is stale — which guard depends on NOERD_AUTH_DEFAULT.
        // Laravel's `guest` middleware reads the default guard and would bounce
        // the visitor off the demo login over it, so drop them all.
        $this->logOutStaleGuards();

        if ($this->needsDemoCredentials($request)) {
            return redirect('/');
        }

        return $next($request);
    }

    private function onALoginScreen(Request $request): bool
    {
        return $request->routeIs('login') || $request->routeIs('noerd.login');
    }

    private function logOutStaleGuards(): void
    {
        foreach (array_keys(config('auth.guards', [])) as $name) {
            $guard = Auth::guard($name);

            if ($guard instanceof StatefulGuard && $guard->check()) {
                $guard->logout();
            }
        }
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
