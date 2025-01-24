<?php

namespace Vanguard\Http\Controllers\Api\Auth\Password;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Password;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\Auth\PasswordResetRequest;

class ResetController extends ApiController
{
    /**
     * Reset the given user's password.
     */
    public function index(PasswordResetRequest $request): JsonResponse
    {
        $response = Password::reset($request->credentials(), function ($user, $password) {
            $this->resetPassword($user, $password);
        });

        return match ($response) {
            Password::PASSWORD_RESET, Password::INVALID_USER => $this->respondWithSuccess(),
            default => $this->setStatusCode(400)
                ->respondWithError(trans($response)),
        };
    }

    /**
     * Reset the given user's password.
     */
    protected function resetPassword(\Illuminate\Contracts\Auth\CanResetPassword $user, string $password): void
    {
        $user->password = $password;
        $user->save();

        event(new PasswordReset($user));
    }
}
