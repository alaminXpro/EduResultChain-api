<?php

namespace Vanguard\Events\User;

use Vanguard\User;

class RequestedPasswordResetEmail
{
    public function __construct(protected User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
