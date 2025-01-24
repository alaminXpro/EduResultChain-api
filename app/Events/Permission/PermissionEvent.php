<?php

namespace Vanguard\Events\Permission;

use Vanguard\Permission;

abstract class PermissionEvent
{
    public function __construct(protected Permission $permission)
    {
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }
}
