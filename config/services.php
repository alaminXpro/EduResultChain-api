<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'stripe' => [
        'model' => Vanguard\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'mailtrap' => [
        'default_inbox' => '58948',
        'secret' => env('MAILTRAP_SECRET'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_CALLBACK_URI'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CALLBACK_URI'),
    ],

    'authy' => [
        'key' => env('AUTHY_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IPFS Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration for the IPFS service used to store
    | and retrieve result data hashes.
    |
    */
    'ipfs' => [
        'endpoint' => env('IPFS_ENDPOINT', 'http://localhost:5001'),
        'gateway' => env('IPFS_GATEWAY', 'http://localhost:8080/ipfs/'),
        'timeout' => env('IPFS_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blockchain Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration for the blockchain service used to
    | register and verify result hashes.
    |
    */
    'blockchain' => [
        'endpoint' => env('BLOCKCHAIN_ENDPOINT', 'http://localhost:3000/api'),
        'network' => env('BLOCKCHAIN_NETWORK', 'testnet'),
        'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'timeout' => env('BLOCKCHAIN_TIMEOUT', 60),
    ],
];
