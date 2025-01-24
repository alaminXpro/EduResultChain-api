<?php

namespace Vanguard\Services\Auth\TwoFactor\Contracts;

use Vanguard\Services\Auth\TwoFactor\Contracts\Authenticatable as TwoFactorAuthenticatable;

interface Provider
{
    /**
     * Determine if the given user has two-factor authentication enabled.
     */
    public function isEnabled(TwoFactorAuthenticatable $user): bool;

    /**
     * Register the given user with the provider.
     */
    public function register(TwoFactorAuthenticatable $user): void;

    /**
     * Sends an SMS with a phone verification token.
     */
    public function sendTwoFactorVerificationToken(TwoFactorAuthenticatable $user): bool;

    /**
     * Determine if the given token is valid for the given user.
     */
    public function tokenIsValid(TwoFactorAuthenticatable $user, $token): bool;

    /**
     * Delete the given user from the provider.
     */
    public function delete(TwoFactorAuthenticatable $user): void;
}
