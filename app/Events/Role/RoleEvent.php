<?php

namespace Vanguard\Events\Role;

use Vanguard\Role;

abstract class RoleEvent
{
    public function __construct(protected Role $role)
    {
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
