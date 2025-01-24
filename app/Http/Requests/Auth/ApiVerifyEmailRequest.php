<?php

namespace Vanguard\Http\Requests\Auth;

class ApiVerifyEmailRequest extends LoginRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required',
            'hash' => 'required',
            'expires' => 'required',
            'signature' => 'required',
        ];
    }
}
