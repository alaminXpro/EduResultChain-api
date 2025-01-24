<?php

namespace Vanguard\Http\Requests\User;

use Vanguard\User;

class UpdateProfileLoginDetailsRequest extends UpdateLoginDetailsRequest
{
    protected function getUserForUpdate(): User
    {
        return \Auth::user();
    }
}
