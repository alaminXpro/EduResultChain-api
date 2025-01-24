<?php

namespace Vanguard\Support\Authorization;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vanguard\Role;

trait AuthorizationUserTrait
{
    /**
     * @return mixed
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if user has specified role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    /**
     * Check if user has a provided permission.
     */
    public function hasPermission(string|array $permission, $allRequired = true): bool
    {
        $permission = (array) $permission;

        return $allRequired
            ? $this->hasAllPermissions($permission)
            : $this->hasAtLeastOnePermission($permission);
    }

    /**
     * Check if user has all provided permissions (translates to AND logic between permissions).
     */
    private function hasAllPermissions(array $permissions): bool
    {
        $availablePermissions = $this->role->cachedPermissions()->pluck('name')->toArray();

        foreach ($permissions as $perm) {
            if (! in_array($perm, $availablePermissions, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has at least one of provided permissions (translates to OR logic between permissions).
     */
    private function hasAtLeastOnePermission(array $permissions): bool
    {
        $availablePermissions = $this->role->cachedPermissions()->pluck('name')->toArray();

        foreach ($permissions as $perm) {
            if (in_array($perm, $availablePermissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set user's role.
     */
    public function setRole(Role|int $role): bool
    {
        return $this->forceFill([
            'role_id' => $role instanceof Role ? $role->id : $role,
        ])->save();
    }
}
