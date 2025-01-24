<?php

namespace Vanguard\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory;

class UseApiGuard
{
    public function __construct(protected Factory $auth)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->auth->shouldUse('sanctum');

        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
