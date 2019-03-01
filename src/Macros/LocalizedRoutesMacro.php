<?php

namespace CodeZero\LocalizedRoutes\Macros;

use App;
use Config;
use Route;
use CodeZero\LocalizedRoutes\LocalizedRoutesMiddleware;

class LocalizedRoutesMacro
{
    /**
     * Register the macro.
     *
     * @return void
     */
    public static function register()
    {
        Route::macro('localized', function ($callback) {
            // Remember the current locale so we can
            // change it during route registration.
            $currentLocale = App::getLocale();

            $locales = Config::get('localized-routes.supported-locales', []);

            if (in_array(Config::get('localized-routes.register-unprefixed-routes-for-locale'), $locales)) {
                // Register unprefixed / non localized routes
                $callback();
            }

            // Register localized routes for every supported locale
            foreach ($locales as $locale) {
                // Change the current locale so we can
                // use it in the callback, for example
                // to register translated route URI's.
                App::setLocale($locale);

                // Wrap the localized routes in a group,
                // prepend the locale to the URI and the route name
                // and add middleware to the group
                Route::prefix($locale)->name("{$locale}.")->middleware(LocalizedRoutesMiddleware::class)->group($callback);
            }

            // Restore the original locale.
            App::setLocale($currentLocale);
        });
    }
}
