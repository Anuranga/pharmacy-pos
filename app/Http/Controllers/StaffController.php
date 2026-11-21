<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\TenantRolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * Display Staff Management (Managers, Cashiers, Staff) and Role-Wise Permissions Matrix.
     */
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        if (!$tenantId) {
            return redirect()->route('super-admin.tenants.index');
        }

        TenantRolePermission::seedDefaultsForTenant($tenantId);

        $staffMembers = User::with('role')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get();

        $assignableRoles = Role::whereIn('name', ['manager', 'cashier', 'staff'])->get();

        $permissionGroups = TenantRolePermission::permissionGroups();

        $rolePermissions = [];
        foreach (['manager', 'cashier', 'staff'] as $rName) {
            $row = TenantRolePermission::where('tenant_id', $tenantId)
                ->where('role_name', $rName)
                ->first();
            $rolePermissions[$rName] = $row ? (array) ($row->permissions ?? []) : TenantRolePermission::defaultPermissionsForRole($rName);
        }

        $uiSettingDefinitions = TenantRolePermission::uiSettingDefinitions();
        $uiSettings = TenantRolePermission::getUiSettingsForTenant($tenantId);

        return view('staff.index', compact(
            'staffMembers',
            'assignableRoles',
            'permissionGroups',
            'rolePermissions',
            'uiSettingDefinitions',
            'uiSettings'
        ));
    }

    /**
     * Add a new Manager, Cashier, or Staff user to the current Pharmacy Tenant.
     */
    public function storeStaff(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:40',
            'role_name' => 'required|in:manager,cashier,staff',
            'password' => 'required|string|min:6',
        ]);

        $role = Role::where('name', $validated['role_name'])->firstOrFail();

        User::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role_id' => $role->id,
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        return back()->with('success', "Staff member '{$validated['name']}' ({$role->label}) added successfully.");
    }

    /**
     * Update a Manager, Cashier, or Staff user's details, role, or status.
     */
    public function updateStaff(Request $request, User $user)
    {
        if ($user->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Unauthorized access to another tenant staff account.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:40',
            'role_name' => 'nullable|in:manager,cashier,staff',
            'status' => 'required|in:active,disabled',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if (!$user->isAdmin() && !empty($validated['role_name'])) {
            $role = Role::where('name', $validated['role_name'])->first();
            if ($role) {
                $user->role_id = $role->id;
            }
            $user->status = $validated['status'];
        }

        $user->save();

        return back()->with('success', "Staff account '{$user->name}' updated successfully.");
    }

    /**
     * Change password for a Manager, Cashier, or Staff user.
     */
    public function changePassword(Request $request, User $user)
    {
        if ($user->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Unauthorized access to another tenant staff account.');
        }

        $validated = $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return back()->with('success', "Password updated successfully for '{$user->name}' ({$user->role->label}).");
    }

    /**
     * Save logical Role-Wise Permission checkboxes for Manager, Cashier, and Staff.
     */
    public function updateRolePermissions(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $submitted = (array) $request->input('permissions', []);

        foreach (['manager', 'cashier', 'staff'] as $roleName) {
            $perms = isset($submitted[$roleName]) && is_array($submitted[$roleName])
                ? array_values(array_unique($submitted[$roleName]))
                : [];

            TenantRolePermission::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'role_name' => $roleName,
                ],
                [
                    'permissions' => $perms,
                ]
            );
        }

        return back()->with('success', 'Role-wise permissions updated and applied immediately for Managers, Cashiers, and Staff.');
    }

    /**
     * Save Admin UI & Dashboard Content Visibility Preferences (Sub-headings & Dashboard Sections).
     */
    public function updateUiSettings(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        if (!$tenantId) {
            return back()->with('error', 'No active pharmacy tenant found.');
        }

        $enabledKeys = (array) $request->input('ui_settings', []);
        TenantRolePermission::saveUiSettingsForTenant($tenantId, $enabledKeys);

        return back()->with('success', 'Interface & Dashboard display preferences updated successfully.');
    }
}
