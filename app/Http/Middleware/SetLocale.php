<?php

namespace Vanguard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Vanguard\Support\Locale;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $this->getLocale($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getLocale(Request $request): string
    {
        $newLocale = $request->get('lang');

        if (! $newLocale || ! Locale::validateLocale($newLocale)) {
            return $this->getSelectedLocale();
        }

        session()->put('locale', $newLocale);

        return $newLocale;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getSelectedLocale(): string
    {
        if (session()->has('locale')) {
            return session()->get('locale');
        }

        $locale = \Request::getPreferredLanguage(Locale::AVAILABLE_LOCALES) ?? config('app.locale');

        session()->put('locale', $locale);

        return $locale;
    }
}
