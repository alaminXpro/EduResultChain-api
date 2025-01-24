<?php

namespace Vanguard\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class CheckPermissions
{
    public function __construct(protected Guard $auth)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, $permissions)
    {
        if (! is_array($permissions)) {
            $permissions = explode('|', $permissions);
        }

        if ($this->auth->guest() || ! $request->user()->hasPermission($permissions)) {
            abort(403, 'Forbidden.');
        }

        return $next($request);
    }
}
