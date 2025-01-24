<?php

namespace Vanguard\Events\User;

use Vanguard\User;

class Created
{
    public function __construct(protected User $createdUser)
    {
    }

    public function getCreatedUser(): User
    {
        return $this->createdUser;
    }
}
