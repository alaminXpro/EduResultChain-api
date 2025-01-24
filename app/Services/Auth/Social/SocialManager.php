<?php

namespace Vanguard\Services\Auth\Social;

use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;
use Vanguard\Repositories\Role\RoleRepository;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Role;
use Vanguard\Support\Enum\UserStatus;
use Vanguard\User;

class SocialManager
{
    use ManagesSocialAvatarSize;

    public function __construct(private readonly UserRepository $users, private readonly RoleRepository $roles)
    {
    }

    /**
     * Associate social user with given provider. If user with the same email address
     * retrieved from social network exists in our database, we will just associate it
     * with provided social account. If not, user will be created.
     */
    public function associate(SocialUser $socialUser, string $provider): User
    {
        $user = $this->users->findByEmail($socialUser->getEmail());

        if (! $user) {
            // User with email retrieved from social auth provider does not
            // exist in our database. That means that we have to create new user here
            [$firstName, $lastName] = $this->parseUserFullName($socialUser);

            $role = $this->roles->findByName(Role::DEFAULT_USER_ROLE);

            $user = $this->users->create([
                'email' => $socialUser->getEmail(),
                'password' => Str::random(10),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'status' => UserStatus::ACTIVE,
                'avatar' => $this->getAvatarForProvider($provider, $socialUser),
                'role_id' => $role->id,
                'email_verified_at' => now(),
            ]);
        }

        // Associate social account with user account inside our application
        $this->users->associateSocialAccountForUser($user->id, $provider, $socialUser);

        return $user;
    }

    /**
     * Parse User's name from his social network account.
     */
    private function parseUserFullName(SocialUser $user): array
    {
        $name = $user->getName();

        if (str_contains($name, ' ')) {
            return explode(' ', $name, 2);
        }

        return [$name, ''];
    }
}
