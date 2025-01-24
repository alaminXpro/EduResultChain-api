<?php

namespace Vanguard\Http\Requests\Auth\Social;

use Vanguard\Http\Requests\Request;

class SaveEmailRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
        ];
    }
}
