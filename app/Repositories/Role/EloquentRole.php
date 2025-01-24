<?php

namespace Vanguard\Repositories\Role;

use Illuminate\Database\Eloquent\Collection;
use Vanguard\Events\Role\Created;
use Vanguard\Events\Role\Deleted;
use Vanguard\Events\Role\Updated;
use Vanguard\Role;

class EloquentRole implements RoleRepository
{
    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return Role::all();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithUsersCount(): Collection
    {
        return Role::withCount('users')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id): ?Role
    {
        return Role::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Role
    {
        $role = Role::create($data);

        event(new Created($role));

        return $role;
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data): Role
    {
        $role = $this->find($id);

        $role->update($data);

        event(new Updated($role));

        return $role;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id): void
    {
        $role = $this->find($id);

        event(new Deleted($role));

        $role->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function updatePermissions($roleId, array $permissions): void
    {
        $role = $this->find($roleId);

        $role->syncPermissions($permissions);
    }

    /**
     * {@inheritdoc}
     */
    public function lists(string $column = 'display_name', string $key = 'id'): \Illuminate\Support\Collection
    {
        return Role::pluck($column, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function findByName($name): ?Role
    {
        return Role::where('name', $name)->first();
    }
}
