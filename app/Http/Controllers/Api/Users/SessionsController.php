<?php

namespace Vanguard\Http\Controllers\Api\Users;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Vanguard\Http\Controllers\Api\ApiController;
use Vanguard\Http\Resources\SessionResource;
use Vanguard\Repositories\Session\SessionRepository;
use Vanguard\User;

class SessionsController extends ApiController
{
    public function __construct()
    {
        $this->middleware('permission:users.manage');
        $this->middleware('session.database');
    }

    public function index(User $user, SessionRepository $sessions): AnonymousResourceCollection
    {
        return SessionResource::collection(
            $sessions->getUserSessions($user->id)
        );
    }
}
