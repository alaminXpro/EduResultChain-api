<?php

namespace Vanguard\Http\Requests\Role;

use Illuminate\Validation\Rule;
use Vanguard\Http\Requests\Request;
use Vanguard\Permission;

class UpdateRolePermissionsRequest extends Request
{
    public function rules(): array
    {
        $permissions = Permission::pluck('id')->toArray();

        return [
            'permissions' => 'required|array',
            'permissions.*' => Rule::in($permissions),
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.*' => 'Provided permission does not exist.',
        ];
    }
}
