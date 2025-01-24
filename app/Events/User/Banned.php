<?php

namespace Vanguard\Events\User;

use Vanguard\User;

class Banned
{
    public function __construct(protected User $bannedUser)
    {
    }

    public function getBannedUser(): User
    {
        return $this->bannedUser;
    }
}
