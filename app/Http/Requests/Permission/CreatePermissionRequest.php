<?php

namespace Vanguard\Http\Requests\Permission;

use Illuminate\Validation\Rule;
use Vanguard\Rules\ValidPermissionName;

class CreatePermissionRequest extends BasePermissionRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                new ValidPermissionName,
                Rule::unique('permissions', 'name'),
            ],
        ];
    }
}
