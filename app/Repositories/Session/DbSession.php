<?php

namespace Vanguard\Repositories\Session;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;
use stdClass;
use Vanguard\Repositories\User\UserRepository;

class DbSession implements SessionRepository
{
    public function __construct(private readonly UserRepository $users, private readonly Agent $agent)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getUserSessions($userId): Collection
    {
        $validTimestamp = Carbon::now()->subMinutes(config('session.lifetime'))->timestamp;

        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $validTimestamp)
            ->get()
            ->map(function ($session) {
                return $this->mapSessionAttributes($session);
            });
    }

    private function mapSessionAttributes($session): stdClass
    {
        $this->agent->setUserAgent($session->user_agent);

        $session->last_activity = Carbon::createFromTimestamp($session->last_activity);
        $session->platform = $this->agent->platform();
        $session->browser = $this->agent->browser();
        $session->device = $this->agent->device();

        return $session;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateSession($sessionId): void
    {
        $user = $this->users->findBySessionId($sessionId);

        DB::table('sessions')
            ->where('id', $sessionId)
            ->delete();

        $this->users->update($user->id, ['remember_token' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function find($sessionId): ?stdClass
    {
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->first();

        return $session
            ? $this->mapSessionAttributes($session)
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateAllSessionsForUser($userId): void
    {
        DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();

        $this->users->update($userId, ['remember_token' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSessionsCount(int $userId): int
    {
        $validTimestamp = Carbon::now()->subMinutes(config('session.lifetime'))->timestamp;

        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', $validTimestamp)
            ->count();
    }
}
