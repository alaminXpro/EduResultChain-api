<?php

namespace Vanguard\Http\Controllers\Web\Auth;

use Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialUser;
use Socialite;
use Vanguard\Events\User\LoggedIn;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Services\Auth\Social\SocialManager;
use Vanguard\User;

class SocialAuthController extends Controller
{
    public function __construct(private readonly UserRepository $users, private readonly SocialManager $socialManager)
    {
        $this->middleware('guest');
    }

    /**
     * Redirect user to specified provider in order to complete the authentication process.
     */
    public function redirectToProvider(string $provider): RedirectResponse
    {
        if (strtolower($provider) == 'facebook') {
            return Socialite::driver('facebook')->with(['auth_type' => 'rerequest'])->redirect();
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle response authentication provider.
     */
    public function handleProviderCallback(string $provider): RedirectResponse
    {
        if (request()->get('error')) {
            return redirect('login')
                ->withErrors(__('Something went wrong during the authentication process. Please try again.'));
        }

        $socialUser = $this->getUserFromProvider($provider);

        $user = $this->users->findBySocialId($provider, $socialUser->getId());

        if (! $user) {
            if (! setting('reg_enabled')) {
                return redirect('login')
                    ->withErrors(__('Only users who already created an account can log in.'));
            }

            if (! $socialUser->getEmail()) {
                return redirect('login')
                    ->withErrors(__('You have to provide your email address.'));
            }

            $user = $this->socialManager->associate($socialUser, $provider);

            event(new \Illuminate\Auth\Events\Registered($user));
        }

        return $this->loginAndRedirect($user);
    }

    /**
     * Get user from authentication provider.
     */
    private function getUserFromProvider(string $provider): SocialUser
    {
        return Socialite::driver($provider)->user();
    }

    /**
     * Log provided user in and redirect him to intended page.
     */
    private function loginAndRedirect(User $user): RedirectResponse
    {
        if ($user->isBanned()) {
            return redirect()->to('login')
                ->withErrors(__('Your account is banned by administrator.'));
        }

        if (setting('2fa.enabled') && $user->twoFactorEnabled()) {
            session()->put('auth.2fa.id', $user->id);

            return redirect()->route('auth.token');
        }

        Auth::login($user);

        event(new LoggedIn);

        return redirect()->intended('/');
    }
}
