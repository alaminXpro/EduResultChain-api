<?php

namespace Vanguard\Http\Requests\Role;

use Vanguard\Http\Requests\Request;

class RemoveRoleRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('role')->removable;
    }

    public function rules(): array
    {
        return [];
    }
}
