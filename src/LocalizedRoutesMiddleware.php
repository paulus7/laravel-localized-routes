<?php

namespace CodeZero\LocalizedRoutes;

use App;
use Config;
use Closure;
use Illuminate\Support\Facades\Redirect;

class LocalizedRoutesMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Config::get('localized-routes.register-unprefixed-routes-for-locale')) {

            // Redirect to unprefixed route if the locale from localized route matches defined locale

            $locale = trim($request->route()->getPrefix(), '/');

            if ($locale === Config::get('localized-routes.register-unprefixed-routes-for-locale') && in_array($locale, Config::get('localized-routes.supported-locales', []))) {
                return Redirect::route(substr($request->route()->getName(), strlen($locale)+1));
            }
        }

        if (Config::get('localized-routes.set-app-locale')) {

            // Set app locale to the locale from localized route

            $locale = trim($request->route()->getPrefix(), '/');

            if (in_array($locale, Config::get('localized-routes.supported-locales', []))) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
