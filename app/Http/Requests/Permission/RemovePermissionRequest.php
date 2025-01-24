<?php

namespace Vanguard\Http\Requests\Permission;

use Vanguard\Http\Requests\Request;

class RemovePermissionRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('permission')->removable;
    }

    public function rules(): array
    {
        return [];
    }
}
