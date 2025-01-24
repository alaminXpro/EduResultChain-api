<?php

namespace Vanguard\Listeners\Users;

use Illuminate\Auth\Events\Verified;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Support\Enum\UserStatus;

class ActivateUser
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function handle(Verified $event): void
    {
        $this->users->update($event->user->id, [
            'status' => UserStatus::ACTIVE,
        ]);
    }
}
