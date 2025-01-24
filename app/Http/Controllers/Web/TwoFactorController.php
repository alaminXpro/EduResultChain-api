<?php

namespace Vanguard\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Vanguard\Events\User\TwoFactorEnabled;
use Vanguard\Events\User\TwoFactorEnabledByAdmin;
use Vanguard\Events\User\TwoFactorDisabled;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Http\Requests\TwoFactor\DisableTwoFactorRequest;
use Vanguard\Http\Requests\TwoFactor\EnableTwoFactorRequest;
use Vanguard\Http\Requests\TwoFactor\VerifyTwoFactorTokenRequest;
use Vanguard\Repositories\User\UserRepository;

class TwoFactorController extends Controller
{
    public function __construct(protected UserRepository $users, protected TwoFactorAuthenticationProvider $twoFactorProvider)
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) use ($users) {
            $user = $request->get('user')
                ? $users->find($request->get('user'))
                : auth()->user();

            return $user->twoFactorEnabled() ? abort(404) : $next($request);
        })->only('enable', 'verification', 'resend', 'verify');
    }

    /**
     * Enable 2FA for currently logged user.
     */
    public function enable(EnableTwoFactorRequest $request, EnableTwoFactorAuthentication $enable)
    {
        $user = $request->theUser();

        $enable($user, $request->boolean('force', false));

        session()->flash('tab', '2fa');

        return redirect()->back()
            ->withSuccess(trans('auth.2fa.enabled_successfully'));
    }

    /**
     * Shows the phone verification page.
     */
    public function verification(): View
    {
        return view('user.two-factor-verification', [
            'user' => request('user'),
        ]);
    }

    /**
     * Verify 2FA token and enable 2FA if token is valid.
     */
    public function verify(VerifyTwoFactorTokenRequest $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $user = $request->theUser();

        try {
            $confirm($user, $request->input('code'));
        } catch (ValidationException $e) {
            session()->flash('tab', '2fa');

            return redirect()->back()
                ->withErrors(trans('auth.2fa.invalid_token'));
        }

        $message = trans('auth.2fa.enabled_successfully');

        if ($user->is(auth()->user())) {
            event(new TwoFactorEnabled);

            return redirect()->route('profile')->withSuccess($message);
        }

        event(new TwoFactorEnabledByAdmin($user));

        return redirect()->route('users.edit', $user)->withSuccess($message);
    }

    /**
     * Disable 2FA for currently logged user.
     */
    public function disable(DisableTwoFactorRequest $request): RedirectResponse
    {
        $user = $request->theUser();

        if (!$user->twoFactorEnabled()) {
            abort(404);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        event(new TwoFactorDisabled);

        return redirect()->back()
            ->withSuccess(trans('auth.2fa.disabled_successfully'));
    }
}
