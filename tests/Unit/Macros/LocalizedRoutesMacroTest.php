<?php

namespace CodeZero\LocalizedRoutes\Tests\Unit\Macros;

use App;
use CodeZero\LocalizedRoutes\Tests\TestCase;
use Config;
use Illuminate\Support\Collection;
use Route;

class LocalizedRoutesMacroTest extends TestCase
{
    protected function setAvailableLocales($locales)
    {
        Config::set('localized-routes.supported-locales', $locales);
    }

    protected function getRoutes()
    {
        // Route::has() doesn't seem to be working
        // when you create routes on the fly.
        // So this is a bit of a workaround...
        return new Collection(Route::getRoutes());
    }

    /** @test */
    public function it_registers_a_route_for_each_locale()
    {
        $this->setAvailableLocales(['en', 'nl']);

        Route::localized(function () {
            Route::get('route', function () {})
                ->name('route.name');
        });

        $routes = $this->getRoutes();
        $names = $routes->pluck('action.as');
        $uris = $routes->pluck('uri');

        if (Config::get('localized-routes.register-unprefixed-routes-for-locale')) {
            $this->assertContains('route.name', $names);
        } else {
            $this->assertNotContains('route.name', $names);
        }

        $this->assertContains('en.route.name', $names);
        $this->assertContains('nl.route.name', $names);

        if (Config::get('localized-routes.register-unprefixed-routes-for-locale')) {
            $this->assertContains('route', $uris);
        } else {
            $this->assertNotContains('route', $uris);
        }

        $this->assertContains('en/route', $uris);
        $this->assertContains('nl/route', $uris);
    }

    /** @test */
    public function it_registers_a_root_route_for_each_locale()
    {
        $this->setAvailableLocales(['en', 'nl']);

        Route::localized(function () {
            Route::get('/', function () {})
                ->name('home');
        });

        $routes = $this->getRoutes();
        $names = $routes->pluck('action.as');
        $uris = $routes->pluck('uri');

        if (Config::get('localized-routes.register-unprefixed-routes-for-locale')) {
            $this->assertContains('home', $names);
        } else {
            $this->assertNotContains('home', $names);
        }

        $this->assertContains('en.home', $names);
        $this->assertContains('nl.home', $names);

        if (Config::get('localized-routes.register-unprefixed-routes-for-locale')) {
            $this->assertContains('/', $uris);
        } else {
            $this->assertNotContains('/', $uris);
        }

        $this->assertContains('en', $uris);
        $this->assertContains('nl', $uris);
    }

    /** @test */
    public function it_temporarily_changes_the_app_locale_when_registering_the_routes()
    {
        $this->setAvailableLocales(['nl']);

        $this->assertEquals('en', App::getLocale());

        Route::localized(function () {
            Route::get('myroute', function () {})
                ->name('myroute.name');
        });

        $names = $this->getRoutes()->pluck('action.as');

        $this->assertContains('nl.myroute.name', $names);

        $this->assertEquals('en', App::getLocale());
    }
}
