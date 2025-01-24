<?php

namespace Vanguard\Services\Auth;

use Illuminate\Foundation\Auth\ThrottlesLogins as ThrottlesLoginsBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ThrottlesLogins
{
    use ThrottlesLoginsBase;

    /**
     * Get the login username to be used by the controller.
     */
    public function username(): string
    {
        return 'username';
    }

    /**
     * Determine how many retries are left for the user.
     */
    protected function retriesLeft(Request $request): int
    {
        $attempts = $this->limiter()->attempts(
            $this->throttleKey($request)
        );

        return $this->maxAttempts() - $attempts + 1;
    }

    /**
     * {@inheritDoc}
     */
    protected function sendLockoutResponse(Request $request): RedirectResponse
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        return redirect('login')
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                $this->username() => $this->getLockoutErrorMessage($seconds),
            ]);
    }

    /**
     * Get the login lockout error message.
     */
    protected function getLockoutErrorMessage($seconds): string
    {
        return trans('auth.throttle', ['seconds' => $seconds]);
    }

    /** {@inheritDoc} */
    protected function maxAttempts()
    {
        return setting('throttle_attempts', 5);
    }

    /** {@inheritDoc} */
    protected function decayMinutes(): int
    {
        $lockout = (int) setting('throttle_lockout_time');

        return max($lockout, 1);
    }
}
