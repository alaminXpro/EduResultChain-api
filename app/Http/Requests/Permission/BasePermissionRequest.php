<?php

namespace Vanguard\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

class BasePermissionRequest extends FormRequest
{
    public function messages(): array
    {
        return [
            'name.unique' => __('Permission with this name already exists.'),
        ];
    }
}
