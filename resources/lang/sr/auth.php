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

    'failed' => 'Podaci ne odgovaraju ni jednom nalogu.',
    'throttle' => 'Previše neuspelih pokušaja. Pokušajte ponovo za :seconds sekundi.',
    'banned' => 'Vaš nalog je blokiran od strane administratora.',
    'max_sessions_reached' => 'Dostigli ste maksimalni dozvoljeni broj aktivnih sesija. Odjavite se na drugim uređajima i pokušajte ponovo.',

    '2fa' => [
        'enabled_successfully' => 'Two-Factor autentifikacija je uspešno aktivirana.',
        'disabled_successfully' => 'Two-Factor autentifikacija je uspešno deaktivirana.',
        'already_enabled' => 'Two-Factor autentifikacija je već uključena za ovog korisnika.',
        'not_enabled' => 'Two-Factor autentifikacija nije uključena za ovog korisnika.',
        'phone_in_use' => 'Korisnik sa navedenim telefonskim brojem i pozivnim brojem države već postoji.',
        'invalid_token' => '2FA token nije validan.',
        'token_sent' => 'Token za verifikaciju je uspešno poslat.',
    ],

];
