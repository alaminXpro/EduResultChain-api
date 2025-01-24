<?php

namespace Vanguard\Services\Auth\TwoFactor;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'authy';
    }
}
