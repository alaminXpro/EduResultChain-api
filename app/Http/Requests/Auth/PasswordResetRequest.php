<?php

namespace Vanguard\Http\Requests\Auth;

use Vanguard\Http\Requests\Request;

class PasswordResetRequest extends Request
{
    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    /**
     * Get the password reset fields.
     */
    public function credentials(): array
    {
        return $this->only('email', 'password', 'password_confirmation', 'token');
    }
}
