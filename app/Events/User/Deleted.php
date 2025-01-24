<?php

namespace Vanguard\Events\User;

use Vanguard\User;

class Deleted
{
    public function __construct(protected User $deletedUser)
    {
    }

    public function getDeletedUser(): User
    {
        return $this->deletedUser;
    }
}
