<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Staffs: staff accounts, roles (permission sets) and the permission list. */
class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', ['staffs' => User::query()->where('type', User::TYPE_STAFF)->with('roles')->latest('id')->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.staff.form', ['staff' => new User, 'roles' => Role::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $staff = User::query()->create([...$data, 'type' => User::TYPE_STAFF, 'password' => Hash::make($data['password'])]);
        $staff->syncRoles([$data['role']]);

        return redirect()->route('admin.staffs.index')->with('success', __('Staff has been inserted successfully'));
    }

    public function edit(User $staff): View
    {
        abort_unless($staff->type === User::TYPE_STAFF, 404);

        return view('admin.staff.form', ['staff' => $staff, 'roles' => Role::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($staff->type === User::TYPE_STAFF, 404);
        $data = $this->validated($request, $staff);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $staff->update($data);
        $staff->syncRoles([$data['role']]);

        return redirect()->route('admin.staffs.index')->with('success', __('Staff has been updated successfully'));
    }

    public function destroy(User $staff): JsonResponse
    {
        abort_unless($staff->type === User::TYPE_STAFF, 404);
        $staff->delete();

        return response()->json(['success' => true, 'message' => __('Staff has been deleted successfully')]);
    }

    private function validated(Request $request, ?User $staff = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($staff?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$staff ? 'nullable' : 'required', Password::min(8)],
            'role' => ['required', Rule::exists('roles', 'name')],
        ]);
    }

    // ---------------- Roles ----------------

    public function roles(): View
    {
        return view('admin.staff.roles', ['roles' => Role::query()->withCount(['permissions', 'users'])->orderBy('name')->get()]);
    }

    public function createRole(): View
    {
        return view('admin.staff.role-form', ['role' => new Role, 'sections' => config('admin_permissions'), 'granted' => []]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $this->validatedRole($request);
        Role::create(['name' => $data['name'], 'guard_name' => 'web'])->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', __('Role has been inserted successfully'));
    }

    public function editRole(Role $role): View
    {
        return view('admin.staff.role-form', ['role' => $role, 'sections' => config('admin_permissions'), 'granted' => $role->permissions->pluck('name')->all()]);
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validatedRole($request, $role);
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('success', __('Role has been updated successfully'));
    }

    public function destroyRole(Role $role): JsonResponse
    {
        abort_if($role->users()->exists(), 422, __('This role is assigned to staff. Change their role first.'));
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['success' => true, 'message' => __('Role has been deleted successfully')]);
    }

    private function validatedRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);
    }

    /** Read only: permissions come from config/admin_permissions.php through PermissionSeeder. */
    public function permissions(): View
    {
        return view('admin.staff.permissions', ['permissions' => Permission::query()->orderBy('section')->orderBy('id')->get()]);
    }
}
