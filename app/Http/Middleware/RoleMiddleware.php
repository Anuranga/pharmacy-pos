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

        // If route strictly requires super_admin
        if ($allowedRoles === ['super_admin']) {
            if ($user->isSuperAdmin()) {
                return $next($request);
            }
            abort(403, 'Unauthorized. Super Administrator access required.');
        }

        // 'admin' and 'super_admin' have full shop access
        if ($user->isAdmin()) {
            return $next($request);
        }

        if (in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        // Check dynamic Role-Wise Permissions configured by the Pharmacy Admin
        if (in_array('admin', $allowedRoles)) {
            $routeName = $request->route()?->getName() ?? '';
            $permissionMap = [
                'master.adjust-stock' => 'adjust_stock_price',
                'master.' => 'manage_master_data',
                'grn.' => 'manage_grn',
                'suppliers.' => 'manage_suppliers',
                'returns.supplier.' => 'manage_supplier_returns',
                'reports.' => 'view_management_reports',
                'pos.void' => 'pos_void_sale',
            ];

            foreach ($permissionMap as $prefix => $permKey) {
                if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                    if ($user->hasPermission($permKey)) {
                        return $next($request);
                    }
                    break;
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized. Your role does not have permission to access this module.'
            ], 403);
        }

        abort(403, 'Unauthorized access. Your role has not been granted permission for this module.');
    }
}
