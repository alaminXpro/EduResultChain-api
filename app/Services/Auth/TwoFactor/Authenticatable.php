<?php

namespace Vanguard\Services\Auth\TwoFactor;

trait Authenticatable
{
    /**
     * Get the e-mail address used for two-factor authentication.
     */
    public function getEmailForTwoFactorAuth(): string
    {
        return $this->email;
    }

    /**
     * Get the country code used for two-factor authentication.
     */
    public function getAuthCountryCode(): string
    {
        return $this->two_factor_country_code;
    }

    /**
     * Get the phone number used for two-factor authentication.
     */
    public function getAuthPhoneNumber(): string
    {
        return $this->two_factor_phone;
    }

    /**
     * Set the country code and phone number used for two-factor authentication.
     */
    public function setAuthPhoneInformation(string $countryCode, string $phoneNumber)
    {
        $this->two_factor_country_code = $countryCode;
        $this->two_factor_phone = $phoneNumber;
    }

    /**
     * Get the two-factor provider options in array format.
     */
    public function getTwoFactorAuthProviderOptions(): array
    {
        return json_decode($this->two_factor_options, true) ?: [];
    }

    /**
     * Set the two-factor provider options in array format.
     */
    public function setTwoFactorAuthProviderOptions(array $options): void
    {
        $this->two_factor_options = json_encode($options);
    }

    /**
     * Determine if the user is using two-factor authentication.
     */
    public function getUsingTwoFactorAuthAttribute(): bool
    {
        $options = $this->getTwoFactorAuthProviderOptions();

        return isset($options['id']);
    }
}
