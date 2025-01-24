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

    'failed' => 'Diese Zugangsdaten wurden nicht in unserer Datenbank gefunden.',
    'throttle' => 'Zu viele Login Versuche. Versuchen Sie es bitte in :seconds Sekunden.',
    'banned' => 'Ihr Benutzerkonto wurde von einem Administrator gesperrt.',
    'max_sessions_reached' => 'Sie haben die maximal zulässige Anzahl aktiver Sitzungen erreicht. Bitte melden Sie sich auf anderen Geräten ab und versuchen Sie es erneut.',

    '2fa' => [
        'enabled_successfully' => 'Zwei-Faktor-Authentifizierung erfolgreich aktiviert.',
        'disabled_successfully' => 'Zwei-Faktor-Authentifizierung erfolgreich deaktiviert.',
        'already_enabled' => '2FA ist für diesen Benutzer bereits aktiviert.',
        'not_enabled' => '2FA ist für diesen Benutzer nicht aktiviert.',
        'phone_in_use' => 'Es gibt bereits einen Benutzer mit der angegebenen Telefonnummer und Landesvorwahl.',
        'invalid_token' => 'Ungültiges 2FA-Token.',
        'token_sent' => 'Verifizierungstoken gesendet.',
    ],
];
