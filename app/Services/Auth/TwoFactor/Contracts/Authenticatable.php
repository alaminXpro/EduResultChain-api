<?php

namespace Vanguard\Services\Auth\TwoFactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;

interface Authenticatable extends BaseAuthenticatable
{
    /**
     * Get the e-mail address used for two-factor authentication.
     */
    public function getEmailForTwoFactorAuth(): string;

    /**
     * Get the country code used for two-factor authentication.
     */
    public function getAuthCountryCode(): string;

    /**
     * Get the phone number used for two-factor authentication.
     */
    public function getAuthPhoneNumber(): string;

    /**
     * Set the country code and phone number used for two-factor authentication.
     */
    public function setAuthPhoneInformation(string $countryCode, string $phoneNumber);

    /**
     * Get the two-factor provider options in array format.
     */
    public function getTwoFactorAuthProviderOptions(): array;

    /**
     * Set the two-factor provider options in array format.
     */
    public function setTwoFactorAuthProviderOptions(array $options): void;
}
