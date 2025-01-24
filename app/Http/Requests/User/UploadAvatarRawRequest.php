<?php

namespace Vanguard\Http\Requests\User;

use Vanguard\Http\Requests\Request;

class UploadAvatarRawRequest extends Request
{
    public function rules(): array
    {
        return [
            'file' => 'required|image',
        ];
    }
}
