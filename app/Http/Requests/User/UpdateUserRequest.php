<?php

namespace Vanguard\Http\Requests\User;

use Illuminate\Validation\Rule;
use Vanguard\Http\Requests\Request;
use Vanguard\Support\Enum\UserStatus;

class UpdateUserRequest extends Request
{
    public function rules(): array
    {
        $user = $this->user();

        return [
            'email' => 'email|unique:users,email,'.$user->id,
            'username' => 'nullable|unique:users,username,'.$user->id,
            'password' => 'min:8|confirmed',
            'birthday' => 'nullable|date',
            'role_id' => 'exists:roles,id',
            'country_id' => 'exists:countries,id',
            'status' => Rule::in(array_keys(UserStatus::lists())),
        ];
    }
}
