<?php

namespace Vanguard\Support;

trait CanImpersonateUsers
{
    /**
     * Check if a user can impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->hasPermission('users.manage');
    }

    /**
     * Check if a target user can be impersonated.
     * By default, all users can be impersonated if a currently logged
     * user is not already impersonating another user.
     */
    public function canBeImpersonated(): bool
    {
        return ! app('impersonate')->isImpersonating();
    }
}
