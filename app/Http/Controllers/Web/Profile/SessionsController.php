<?php

namespace Vanguard\Http\Controllers\Web\Profile;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Vanguard\Http\Controllers\Controller;
use Vanguard\Repositories\Session\SessionRepository;

class SessionsController extends Controller
{
    public function __construct(private readonly SessionRepository $sessions)
    {
    }

    public function index(): View
    {
        return view('user.sessions', [
            'profile' => true,
            'user' => auth()->user(),
            'sessions' => $this->sessions->getUserSessions(auth()->id()),
        ]);
    }

    public function destroy($session): RedirectResponse
    {
        $this->sessions->invalidateSession($session->id);

        return redirect()->route('profile.sessions')
            ->withSuccess(__('Session invalidated successfully.'));
    }
}
