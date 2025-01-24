<?php

namespace Vanguard\Http\Controllers\Web\Authorization;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Vanguard\Events\Role\PermissionsUpdated;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Repositories\Role\RoleRepository;

class RolePermissionsController extends Controller
{
    public function __construct(private readonly RoleRepository $roles)
    {
    }

    public function update(Request $request): RedirectResponse
    {
        $roles = $request->get('roles');

        $allRoles = $this->roles->lists('id');

        foreach ($allRoles as $roleId) {
            $permissions = Arr::get($roles, $roleId, []);
            $this->roles->updatePermissions($roleId, $permissions);
        }

        event(new PermissionsUpdated);

        return redirect()->route('permissions.index')
            ->withSuccess(__('Permissions saved successfully.'));
    }
}
