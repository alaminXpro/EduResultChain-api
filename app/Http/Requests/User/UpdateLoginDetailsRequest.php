<?php

namespace Vanguard\Http\Requests\User;

use Vanguard\Http\Requests\Request;
use Vanguard\User;

class UpdateLoginDetailsRequest extends Request
{
    public function rules(): array
    {
        $user = $this->getUserForUpdate();

        return [
            'email' => 'required|email|unique:users,email,'.$user->id,
            'username' => 'nullable|unique:users,username,'.$user->id,
            'password' => 'nullable|min:8|confirmed',
        ];
    }

    protected function getUserForUpdate(): User
    {
        return $this->route('user');
    }
}
