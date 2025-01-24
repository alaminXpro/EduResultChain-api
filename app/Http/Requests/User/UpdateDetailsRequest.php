<?php

namespace Vanguard\Http\Requests\User;

use Vanguard\Http\Requests\Request;

class UpdateDetailsRequest extends Request
{
    public function rules(): array
    {
        return [
            'birthday' => 'nullable|date',
            'role_id' => 'required|exists:roles,id',
        ];
    }
}
