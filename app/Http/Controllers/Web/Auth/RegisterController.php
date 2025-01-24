<?php

namespace Vanguard\Http\Controllers\Web\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Http\Requests\Auth\RegisterRequest;
use Vanguard\Repositories\Role\RoleRepository;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Role;

class RegisterController extends Controller
{
    public function __construct(private readonly UserRepository $users)
    {
        $this->middleware('registration')->only('show', 'register');
    }

    public function show(): View
    {
        return view('auth.register', [
            'socialProviders' => config('auth.social.providers'),
        ]);
    }

    public function register(RegisterRequest $request, RoleRepository $roles): RedirectResponse
    {
        $user = $this->users->create(
            array_merge(
                $request->validFormData(),
                ['role_id' => $roles->findByName(Role::DEFAULT_USER_ROLE)->id],
            )
        );

        event(new Registered($user));

        $message = setting('reg_email_confirmation')
            ? __('Your account is created successfully! Please confirm your email.')
            : __('Your account is created successfully!');

        \Auth::login($user);

        return redirect('/')->with('success', $message);
    }
}
