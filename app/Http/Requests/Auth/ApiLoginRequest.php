<?php

namespace Vanguard\Http\Requests\Auth;

class ApiLoginRequest extends LoginRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'device_name' => 'required',
        ]);
    }

    public function getCredentials(): array
    {
        $credentials = parent::getCredentials();

        unset($credentials['password']);

        return $credentials;
    }
}
