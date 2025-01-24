<?php

namespace Vanguard\Support\Authorization;

use Cache;
use Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Vanguard\Permission;

trait AuthorizationRoleTrait
{
    /**
     * Get cached permissions for this role.
     *
     * @return Collection<Permission>
     */
    public function cachedPermissions(): Collection
    {
        return Cache::remember($this->getCacheKey(), Config::get('cache.ttl'), function () {
            return $this->permissions()->get();
        });
    }

    /**
     * Override "save" role method to clear role cache.
     */
    public function save(array $options = []): void
    {
        $this->flushCache();
        parent::save($options);
    }

    /**
     * Override "delete" role method to clear role cache.
     *
     * @throws \Exception
     */
    public function delete(array $options = []): void
    {
        $this->flushCache();
        parent::delete($options);
    }

    public function restore(): void
    {
        $this->flushCache();
        parent::restore();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role', 'role_id');
    }

    /**
     * Checks if the role has a permission by its name.
     */
    public function hasPermission($name): bool
    {
        $perms = $this->cachedPermissions()->pluck('name')->toArray();

        return in_array($name, $perms, true);
    }

    /**
     * Save the inputted permissions.
     */
    public function savePermissions(array $inputPermissions): void
    {
        if (! empty($inputPermissions)) {
            $this->permissions()->sync($inputPermissions);
        } else {
            $this->permissions()->detach();
        }

        $this->flushCache();
    }

    /**
     * Attach permission to current role.
     */
    public function attachPermission(object|array $permission): void
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            $permission = $permission['id'];
        }

        $this->permissions()->attach($permission);

        $this->flushCache();
    }

    /**
     * Detach permission from current role.
     */
    public function detachPermission(object|array $permission): void
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            $permission = $permission['id'];
        }

        $this->permissions()->detach($permission);

        $this->flushCache();
    }

    /**
     * Attach multiple permissions to current role.
     */
    public function attachPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->attachPermission($permission);
        }
    }

    /**
     * Detach multiple permissions from current role
     */
    public function detachPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->detachPermission($permission);
        }
    }

    /**
     * Sync role permissions.
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->sync($permissions);

        $this->flushCache();
    }

    /**
     * Get permissions cache key.
     */
    private function getCacheKey(): string
    {
        return 'permissions_for_role_'.$this->{$this->primaryKey};
    }

    /**
     * Flush cached permissions for this role.
     */
    private function flushCache(): void
    {
        Cache::forget($this->getCacheKey());
    }
}
