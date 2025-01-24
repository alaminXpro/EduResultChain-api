<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'banned' => 'Your account is banned by administrator.',
    'max_sessions_reached' => 'You reached the maximum number of active sessions allowed. Please log out on other devices and try again.',

    '2fa' => [
        'enabled_successfully' => 'Two-Factor Authentication enabled successfully.',
        'disabled_successfully' => 'Two-Factor Authentication disabled successfully.',
        'already_enabled' => '2FA is already enabled for this user.',
        'not_enabled' => '2FA is not enabled for this user.',
        'phone_in_use' => 'There is already an user with provided phone number and country code.',
        'invalid_token' => 'Invalid 2FA token.',
        'token_sent' => 'Verification token sent.',
    ],
];
