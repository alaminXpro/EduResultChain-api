<?php

namespace Vanguard\Http\Controllers\Api\Authorization;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Vanguard\Events\Role\PermissionsUpdated;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Role\UpdateRolePermissionsRequest;
use Vanguard\Http\Resources\PermissionResource;
use Vanguard\Repositories\Role\RoleRepository;
use Vanguard\Role;

class RolePermissionsController extends ApiController
{
    public function __construct(private RoleRepository $roles)
    {
        $this->middleware('permission:permissions.manage');
    }

    public function show(Role $role): AnonymousResourceCollection
    {
        return PermissionResource::collection($role->cachedPermissions());
    }

    public function update(Role $role, UpdateRolePermissionsRequest $request): AnonymousResourceCollection
    {
        $this->roles->updatePermissions(
            roleId: $role->id,
            permissions: $request->permissions
        );

        event(new PermissionsUpdated);

        return PermissionResource::collection($role->cachedPermissions());
    }
}
