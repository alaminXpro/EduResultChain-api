<?php

namespace Vanguard\Http\Middleware;

use Closure;
use Vanguard\Repositories\User\UserRepository;

class VerifyTwoFactorCode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        $user = $this->getUser($request);

        if ($user->two_factor_secret) {
            return $next($request);
        }

        abort(404);
    }

    /**
     * @return mixed
     */
    private function getUser($request)
    {
        if ($userId = $request->get('user')) {
            return app(UserRepository::class)->find($userId);
        }

        return $request->user();
    }
}
