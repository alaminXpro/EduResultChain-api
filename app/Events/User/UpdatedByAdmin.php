<?php

namespace Vanguard\Events\User;

use Vanguard\User;

class UpdatedByAdmin
{
    public function __construct(protected User $updatedUser)
    {
    }

    public function getUpdatedUser(): User
    {
        return $this->updatedUser;
    }
}
