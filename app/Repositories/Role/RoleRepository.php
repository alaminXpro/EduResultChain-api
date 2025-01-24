<?php

namespace Vanguard\Repositories\Role;

use Illuminate\Database\Eloquent\Collection;
use Vanguard\Role;

interface RoleRepository
{
    /**
     * Get all system roles.
     *
     * @return Collection<Role>
     */
    public function all(): Collection;

    /**
     * Lists all system roles into $key => $column value pairs.
     */
    public function lists(string $column = 'display_name', string $key = 'id'): \Illuminate\Support\Collection;

    /**
     * Get all system roles with number of users for each role.
     *
     * @return Collection<Role>
     */
    public function getAllWithUsersCount(): Collection;

    /**
     * Find system role by id.
     */
    public function find(int $id): ?Role;

    /**
     * Find role by name:
     */
    public function findByName(string $name): ?Role;

    /**
     * Create new system role.
     */
    public function create(array $data): Role;

    /**
     * Update specified role.
     */
    public function update(int $id, array $data): Role;

    /**
     * Remove role from repository.
     */
    public function delete(int $id): void;

    /**
     * Update the permissions for given role.
     */
    public function updatePermissions($roleId, array $permissions): void;
}
