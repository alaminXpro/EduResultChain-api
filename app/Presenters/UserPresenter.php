<?php

namespace Vanguard\Presenters;

use Illuminate\Support\Str;
use Vanguard\Support\Enum\UserStatus;

class UserPresenter extends Presenter
{
    public function name(): string
    {
        return sprintf('%s %s', $this->model->first_name, $this->model->last_name);
    }

    public function nameOrEmail(): string
    {
        return trim($this->name()) ?: $this->model->email;
    }

    public function avatar(): string
    {
        if (! $this->model->avatar) {
            return url('assets/img/profile.png');
        }

        return Str::contains($this->model->avatar, ['http', 'gravatar'])
            ? $this->model->avatar
            : url("upload/users/{$this->model->avatar}");
    }

    public function birthday(): string
    {
        return $this->model->birthday
            ? $this->model->birthday->format(config('app.date_format'))
            : 'N/A';
    }

    public function fullAddress(): string
    {
        $address = '';
        $user = $this->model;

        if ($user->address) {
            $address .= $user->address;
        }

        if ($user->country_id) {
            $address .= $user->address ? ", {$user->country->name}" : $user->country->name;
        }

        return $address ?: 'N/A';
    }

    public function lastLogin(): string
    {
        return $this->model->last_login
            ? $this->model->last_login->diffForHumans()
            : 'N/A';
    }

    /**
     * Determine css class used for status labels
     * inside the users table by checking user status.
     */
    public function labelClass(): string
    {
        return match ($this->model->status) {
            UserStatus::ACTIVE => 'success',
            UserStatus::BANNED => 'danger',
            default => 'warning',
        };
    }
}
