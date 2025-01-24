<?php

namespace Vanguard\Repositories\Permission;

use Illuminate\Database\Eloquent\Collection;
use Vanguard\Permission;

interface PermissionRepository
{
    /**
     * Get all system permissions.
     *
     * @return Collection<Permission>
     */
    public function all(): Collection;

    /**
     * Finds the permission by given id.
     */
    public function find(int $id): Permission;

    /**
     * Creates new permission from provided data.
     */
    public function create(array $data): Permission;

    /**
     * Updates specified permission.
     */
    public function update(int $id, array $data): Permission;

    /**
     * Remove specified permission from repository.
     */
    public function delete($id): bool;
}
