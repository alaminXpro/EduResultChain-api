<?php

namespace Vanguard\Listeners\Users;

use Vanguard\Events\User\Banned;
use Vanguard\Repositories\Session\SessionRepository;

class InvalidateSessions
{
    public function __construct(private readonly SessionRepository $sessions)
    {
    }

    public function handle(Banned $event): void
    {
        $user = $event->getBannedUser();

        $this->sessions->invalidateAllSessionsForUser($user->id);
    }
}
