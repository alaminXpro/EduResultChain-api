<?php

namespace Vanguard\Http\Controllers\Web\Auth;

use Auth;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vanguard\Events\User\LoggedIn;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Http\Requests\TwoFactor\TwoFactorLoginRequest;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Services\Auth\ThrottlesLogins;

class TwoFactorTokenController extends Controller
{
    use ThrottlesLogins;

    public function __construct(private readonly UserRepository $users)
    {
    }

    /**
     * Show Two-Factor Token form.
     */
    public function show(): View|RedirectResponse
    {
        return session('auth.2fa.id') ? view('auth.token') : redirect('login');
    }

    /**
     * Handle Two-Factor token form submission.
     */
    public function update(TwoFactorLoginRequest $request): RedirectResponse
    {
        $this->validate($request, ['code' => 'required']);

        if (! session('auth.2fa.id')) {
            return redirect('login');
        }

        $user = $this->users->find(
            $request->session()->pull('auth.2fa.id')
        );

        if (!$user) {
            throw new NotFoundHttpException;
        }

        $customRedirect = $request->session()->pull('auth.redirect_to') ?: '';

        if (!$request->hasValidCode($user)) {
            return redirect()->to('login' . ($customRedirect ? "?to={$customRedirect}" : ''))
                ->withErrors(trans('auth.2fa.invalid_token'));
        }

        Auth::login($user);

        event(new LoggedIn);

        return redirect()->intended($customRedirect ?: '/');
    }
}
