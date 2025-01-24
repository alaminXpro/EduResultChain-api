<?php

namespace Vanguard\Http\Requests\TwoFactor;

use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Vanguard\Http\Requests\Request;

class TwoFactorLoginRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if ($userId = $this->get('user')) {
            // Only users with "users.manage" permission can enable 2FA for other users.
            return $this->user()->hasPermission('users.manage') || $this->user()->id == $userId;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'code' => 'nullable|string',
        ];
    }

    private function clear2FAUserId($result)
    {
        if ($result) {
            $this->session()->forget('auth.2fa.id');
        }
    }

    public function hasValidCode($user): bool
    {
        try {
            if (!$this->code) {
                return false;
            }

            $twoFactorProvider = app(TwoFactorAuthenticationProvider::class);
            $decryptedSecret = decrypt($user->two_factor_secret);
            $verificationResult = $twoFactorProvider->verify($decryptedSecret, $this->code);

            return tap($verificationResult, fn($result) => $this->clear2FAUserId($result));
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            return false;
        }
    }
}
