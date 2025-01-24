<?php

namespace Vanguard\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vanguard\Repositories\Role\RoleRepository;
use Vanguard\Repositories\Session\SessionRepository;
use Vanguard\Repositories\User\UserRepository;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * This namespace is applied to the controller routes in your web routes file.
     * In addition, it is set as the URL generator's root namespace.
     */
    protected string $webNamespace = 'Vanguard\Http\Controllers\Web';

    /**
     * This namespace is applied to the controller routes in your api routes file.
     */
    protected string $apiNamespace = 'Vanguard\Http\Controllers\Api';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        parent::boot();

        $this->bindUser();
        $this->bindRole();
        $this->bindSession();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        if ($this->app['config']->get('auth.expose_api')) {
            $this->mapApiRoutes();
        }

        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::group([
            'namespace' => $this->webNamespace,
            'middleware' => 'web',
        ], function ($router) {
            require base_path('routes/web.php');
        });
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        Route::group([
            'middleware' => 'api',
            'namespace' => $this->apiNamespace,
            'prefix' => 'api',
        ], function () {
            require base_path('routes/api.php');
        });
    }

    private function bindUser(): void
    {
        $this->bindUsingRepository('user', UserRepository::class);
    }

    private function bindRole(): void
    {
        $this->bindUsingRepository('role', RoleRepository::class);
    }

    private function bindSession(): void
    {
        $this->bindUsingRepository('session', SessionRepository::class);
    }

    private function bindUsingRepository($entity, $repository, $method = 'find'): void
    {
        Route::bind($entity, function ($id) use ($repository, $method) {
            if ($object = app($repository)->$method($id)) {
                return $object;
            }

            throw new NotFoundHttpException('Resource not found.');
        });
    }
}
