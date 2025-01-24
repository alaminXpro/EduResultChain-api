<?php

namespace Vanguard\Listeners\Registration;

use Illuminate\Auth\Events\Registered;
use Mail;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Role;

class SendSignUpNotification
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function handle(Registered $event): void
    {
        if (! setting('notifications_signup_email')) {
            return;
        }

        foreach ($this->users->getUsersWithRole(Role::DEFAULT_ADMIN_ROLE) as $user) {
            Mail::to($user)->send(new \Vanguard\Mail\UserRegistered($event->user));
        }
    }
}
