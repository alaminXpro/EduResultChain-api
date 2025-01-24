<?php

namespace Vanguard\Http\Controllers\Api\Profile;

use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Vanguard\Events\User\TwoFactorDisabled;
use Vanguard\Events\User\TwoFactorEnabled;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Requests\TwoFactor\VerifyTwoFactorTokenRequest;
use Vanguard\Http\Resources\UserResource;

class TwoFactorController extends ApiController
{
    public function update(EnableTwoFactorAuthentication $enable)
    {
        $user = auth()->user();

        if ($user->twoFactorEnabled()) {
            return $this->setStatusCode(422)
                ->respondWithError(trans('auth.2fa.already_enabled'));
        }

        $enable($user);

        return $this->respondWithArray([
            'message' => trans('auth.2fa.token_sent'),
            'qrcode' => $user->twoFactorQrCodeSvg(),
        ]);
    }

    /**
     * Verify provided 2FA token.
     */
    public function verify(VerifyTwoFactorTokenRequest $request, ConfirmTwoFactorAuthentication $confirm): UserResource|\Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        try {
            $confirm($user, $request->input('code'));
        } catch (ValidationException $e) {
            return $this->setStatusCode(422)
                ->respondWithError(trans('auth.2fa.invalid_token'));
        }

        event(new TwoFactorEnabled);

        return new UserResource($user);
    }

    /**
     * Disable 2FA for currently authenticated user.
     */
    public function destroy(): UserResource|\Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        if (!$user->twoFactorEnabled()) {
            return $this->setStatusCode(422)
                ->respondWithError(trans('auth.2fa.not_enabled'));
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        event(new TwoFactorDisabled);

        return new UserResource($user);
    }
}
