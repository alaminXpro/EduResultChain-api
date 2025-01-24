<?php

namespace Vanguard\Http\Controllers\Api\Authorization;

use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Permission\CreatePermissionRequest;
use Vanguard\Http\Requests\Permission\RemovePermissionRequest;
use Vanguard\Http\Requests\Permission\UpdatePermissionRequest;
use Vanguard\Http\Resources\PermissionResource;
use Vanguard\Permission;
use Vanguard\Repositories\Permission\PermissionRepository;

class PermissionsController extends ApiController
{
    public function __construct(private readonly PermissionRepository $permissions)
    {
        $this->middleware('permission:permissions.manage');
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $permissions = QueryBuilder::for(Permission::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('display_name'),
                AllowedFilter::exact('role', 'role_id'),
            ])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('created_at')
            ->paginate();

        return PermissionResource::collection($permissions);
    }

    public function store(CreatePermissionRequest $request): PermissionResource
    {
        $permission = $this->permissions->create(
            $request->only(['name', 'display_name', 'description'])
        );

        return new PermissionResource($permission);
    }

    public function show(Permission $permission): PermissionResource
    {
        return new PermissionResource($permission);
    }

    public function update(Permission $permission, UpdatePermissionRequest $request): PermissionResource
    {
        $input = collect($request->all());

        $permission = $this->permissions->update(
            $permission->id,
            $input->only(['name', 'display_name', 'description'])->toArray()
        );

        return new PermissionResource($permission);
    }

    public function destroy(Permission $permission, RemovePermissionRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->permissions->delete($permission->id);

        return $this->respondWithSuccess();
    }
}
