<?php

namespace Vanguard\Http\Controllers\Web\Users;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Repositories\Session\SessionRepository;
use Vanguard\User;

class SessionsController extends Controller
{
    public function __construct(private readonly SessionRepository $sessions)
    {
        $this->middleware('permission:users.manage');
    }

    public function index(User $user): View
    {
        return view('user.sessions', [
            'adminView' => true,
            'user' => $user,
            'sessions' => $this->sessions->getUserSessions($user->id),
        ]);
    }

    public function destroy(User $user, $session): RedirectResponse
    {
        $this->sessions->invalidateSession($session->id);

        return redirect()->route('user.sessions', $user->id)
            ->withSuccess(__('Session invalidated successfully.'));
    }
}
