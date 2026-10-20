<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userRole = strtolower($user->role ? $user->role->name : 'cashier');

        $allowedRoles = array_map('strtolower', $roles);

        // 'admin' has super-user access to everything
        if ($userRole === 'admin') {
            return $next($request);
        }

        if (in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized. You do not have permission to access this resource.'
            ], 403);
        }

        abort(403, 'Unauthorized access. This action requires administrative privileges.');
    }
}
