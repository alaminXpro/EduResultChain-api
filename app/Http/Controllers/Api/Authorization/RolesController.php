<?php

namespace Vanguard\Http\Controllers\Api\Authorization;

use Cache;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Role\CreateRoleRequest;
use Vanguard\Http\Requests\Role\RemoveRoleRequest;
use Vanguard\Http\Requests\Role\UpdateRoleRequest;
use Vanguard\Http\Resources\RoleResource;
use Vanguard\Repositories\Role\RoleRepository;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Role;

class RolesController extends ApiController
{
    public function __construct(private RoleRepository $roles)
    {
        $this->middleware('permission:roles.manage');
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $roles = QueryBuilder::for(Role::class)
            ->allowedIncludes(RoleResource::allowedIncludes())
            ->allowedFilters(['name'])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('created_at')
            ->paginate();

        return RoleResource::collection($roles);
    }

    public function store(CreateRoleRequest $request): RoleResource
    {
        $role = $this->roles->create(
            $request->only(['name', 'display_name', 'description'])
        );

        return new RoleResource($role);
    }

    public function show($id): RoleResource
    {
        $role = QueryBuilder::for(Role::where('id', $id))
            ->allowedIncludes(RoleResource::allowedIncludes())
            ->first();

        return new RoleResource($role);
    }

    public function update(Role $role, UpdateRoleRequest $request): RoleResource
    {
        $input = collect($request->all());

        $role = $this->roles->update(
            $role->id,
            $input->only(['name', 'display_name', 'description'])->toArray()
        );

        return new RoleResource($role);
    }

    public function destroy(Role $role, UserRepository $users, RemoveRoleRequest $request): JsonResponse
    {
        $userRole = $this->roles->findByName(Role::DEFAULT_USER_ROLE);

        $users->switchRolesForUsers($role->id, $userRole->id);

        $this->roles->delete($role->id);

        Cache::flush();

        return $this->respondWithSuccess();
    }
}
