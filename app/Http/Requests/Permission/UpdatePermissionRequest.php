<?php

namespace Vanguard\Http\Requests\Permission;

use Illuminate\Validation\Rule;
use Vanguard\Rules\ValidPermissionName;

class UpdatePermissionRequest extends BasePermissionRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                new ValidPermissionName,
                Rule::unique('permissions', 'name')->ignore($this->route('permission')->id),
            ],
        ];
    }
}
