<?php

namespace Vanguard\Http\Requests\Role;

use Vanguard\Http\Requests\Request;

class CreateRoleRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required|regex:/^[a-zA-Z0-9\-_\.]+$/|unique:roles,name',
        ];
    }
}
