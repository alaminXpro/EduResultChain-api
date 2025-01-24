<?php

namespace Vanguard\Http\Requests\TwoFactor;

class VerifyTwoFactorTokenRequest extends TwoFactorRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required',
        ];
    }
}
