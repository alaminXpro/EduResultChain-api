<?php

namespace Vanguard\Http\Controllers\Api\Users;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Vanguard\Events\User\TwoFactorDisabledByAdmin;
use Vanguard\Events\User\TwoFactorEnabledByAdmin;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\TwoFactor\VerifyTwoFactorTokenRequest;
use Vanguard\Http\Resources\UserResource;
use Vanguard\User;

class TwoFactorController extends ApiController
{
    public function __construct()
    {
        $this->middleware('permission:users.manage');
    }

    /**
     * Enable 2FA for the specified user.
     */
    public function update(User $user, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        if ($user->twoFactorEnabled()) {
            return $this->setStatusCode(422)
                ->respondWithError(trans('auth.2fa.already_enabled'));
        }

        $enable($user, false);

        return $this->respondWithArray([
            'message' => trans('auth.2fa.token_sent'),
            'qrcode' => $user->twoFactorQrCodeSvg(),
        ]);
    }

    /**
     * Verify provided 2FA token.
     */
    public function verify(VerifyTwoFactorTokenRequest $request, User $user, ConfirmTwoFactorAuthentication $confirm): UserResource|JsonResponse
    {
        try {
            $confirm($user, $request->input('code'));
        } catch (ValidationException $e) {
            return $this->setStatusCode(422)
                ->respondWithError(trans('auth.2fa.invalid_token'));
        }

        event(new TwoFactorEnabledByAdmin($user));

        return new UserResource($user);
    }

    /**
     * Disable 2FA for specified user.
     */
    public function destroy(User $user): UserResource|JsonResponse
    {
        if (!$user->twoFactorEnabled()) {
            return $this->setStatusCode(422)
                ->respondWithError(trans('auth.2fa.not_enabled'));
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        event(new TwoFactorDisabledByAdmin($user));

        return new UserResource($user);
    }
}
