<?php

namespace Vanguard\Repositories\Session;

use Illuminate\Support\Collection;

interface SessionRepository
{
    /**
     * Find session by id.
     */
    public function find(string $sessionId): ?\stdClass;

    /**
     * Get all active sessions for specified user.
     */
    public function getUserSessions(int $userId): Collection;

    /**
     * Get number of active sessions for the specified user.
     */
    public function getActiveSessionsCount(int $userId): int;

    /**
     * Invalidate specified session for provided user
     */
    public function invalidateSession(string $sessionId): void;

    /**
     * Invalidate all sessions for user with given id.
     */
    public function invalidateAllSessionsForUser(int $userId): void;
}
