<?php

namespace Vanguard\Http\Requests\User;

use Vanguard\Http\Requests\Request;

class UpdateProfileDetailsRequest extends Request
{
    public function rules(): array
    {
        return [
            'birthday' => 'nullable|date',
        ];
    }
}
