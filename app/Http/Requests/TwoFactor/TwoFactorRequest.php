<?php

namespace Vanguard\Http\Requests\TwoFactor;

use Vanguard\Http\Requests\Request;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\User;

abstract class TwoFactorRequest extends Request
{
    public function authorize(): bool
    {
        if ($userId = $this->get('user')) {
            // Only users with "users.manage" permission can enable 2FA for other users.
            return $this->user()->hasPermission('users.manage') || $this->user()->id == $userId;
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }

    /**
     * Get the user for which we should enable the 2FA.
     */
    public function theUser(): User
    {
        if ($userId = $this->get('user')) {
            return app(UserRepository::class)->find($userId);
        }

        return $this->user();
    }
}
