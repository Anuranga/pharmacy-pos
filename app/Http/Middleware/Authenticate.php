<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        $user = Auth::user();
        if ($user) {
            if ($user->status !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your staff account has been disabled by your Pharmacy Administrator.',
                ]);
            }

            if (!$user->isSuperAdmin() && $user->tenant && !$user->tenant->is_active) {
                $shopName = $user->tenant->name;
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => "Access Denied: Pharmacy tenant '{$shopName}' is currently disabled by the Super Administrator.",
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
