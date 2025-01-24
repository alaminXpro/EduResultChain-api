<?php

namespace Vanguard\Services\Auth\Social;

use Laravel\Socialite\Contracts\User as SocialUser;

trait ManagesSocialAvatarSize
{
    /**
     * Get appropriate image size for a specific provider.
     */
    protected function getAvatarForProvider(string $provider, SocialUser $socialUser): string
    {
        return match ($provider) {
            'facebook' => str_replace('width=1920', 'width=150', $socialUser->avatar_original),
            'google' => $socialUser->avatar_original.'?sz=150',
            'twitter' => str_replace('_normal', '_200x200', $socialUser->getAvatar()),
            default => $socialUser->getAvatar()
        };
    }
}
