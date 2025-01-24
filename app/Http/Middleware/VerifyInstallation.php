<?php

namespace Vanguard\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vanguard\Http\Controllers\Web\InstallController;

class VerifyInstallation
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
        if (app()->environment('testing')) {
            return $next($request);
        }

        if (! file_exists(base_path('.env')) && ! $request->is('install*')) {
            return redirect()->to('install');
        }

        if (file_exists(base_path('.env')) && $request->is('install*') && ! $request->is('install/complete')) {
            throw new NotFoundHttpException;
        }

        if ($request->is('install*') && ! config()->get('app.key')) {
            config()->set('app.key', InstallController::TMP_APP_KEY);
        }

        return $next($request);
    }
}
