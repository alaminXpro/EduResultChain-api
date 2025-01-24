<?php

namespace Vanguard\Listeners\Login;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Vanguard\Events\User\LoggedIn;
use Vanguard\Repositories\User\UserRepository;

class UpdateLastLoginTimestamp
{
    public function __construct(private readonly UserRepository $users, private readonly Guard $guard)
    {
    }

    public function handle(LoggedIn $event): void
    {
        $this->users->update(
            $this->guard->id(),
            ['last_login' => Carbon::now()]
        );
    }
}
