<?php

namespace Vanguard\Http\Controllers\Api;

use Carbon\Carbon;
use Vanguard\Http\Resources\UserResource;
use Vanguard\Repositories\User\UserRepository;
use Vanguard\Support\Enum\UserStatus;

class StatsController extends ApiController
{
    public function __construct(private UserRepository $users)
    {
        $this->middleware('role:Admin');
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        $usersPerMonth = $this->users->countOfNewUsersPerMonthPerRole(
            Carbon::now()->subYear()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $usersPerStatus = [
            'total' => $this->users->count(),
            'new' => $this->users->newUsersCount(),
            'banned' => $this->users->countByStatus(UserStatus::BANNED),
            'unconfirmed' => $this->users->countByStatus(UserStatus::UNCONFIRMED),
        ];

        $users = UserResource::collection($this->users->latest(7));

        return $this->respondWithArray([
            'users_per_month' => $usersPerMonth,
            'users_per_status' => $usersPerStatus,
            'latest_registrations' => $users->resolve(),
        ]);
    }
}
