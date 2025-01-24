<?php

namespace Vanguard\Http\Requests\Auth\Social;

use Illuminate\Validation\Rule;
use Vanguard\Http\Requests\Request;

class ApiAuthenticateRequest extends Request
{
    public function rules(): array
    {
        return [
            'network' => [
                'required',
                Rule::in(config('auth.social.providers')),
            ],
            'social_token' => 'required',
            'device_name' => 'required',
        ];
    }
}
