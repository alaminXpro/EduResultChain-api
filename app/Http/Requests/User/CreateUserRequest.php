<?php

namespace Vanguard\Http\Requests\User;

use Vanguard\Http\Requests\Request;

class CreateUserRequest extends Request
{
    public function rules(): array
    {
        $rules = [
            'email' => 'required|email|unique:users,email',
            'username' => 'nullable|unique:users,username',
            'password' => 'required|min:8|confirmed',
            'birthday' => 'nullable|date',
            'role_id' => 'required|exists:roles,id',
            'verified' => 'boolean',
        ];

        if ($this->get('country_id')) {
            $rules += ['country_id' => 'exists:countries,id'];
        }

        return $rules;
    }
}
