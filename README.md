# Laravel Localized Routes

[![GitHub release](https://img.shields.io/github/release/codezero-be/laravel-localized-routes.svg)]()
[![License](https://img.shields.io/packagist/l/codezero/laravel-localized-routes.svg)]()
[![Build Status](https://scrutinizer-ci.com/g/codezero-be/laravel-localized-routes/badges/build.png?b=master)](https://scrutinizer-ci.com/g/codezero-be/laravel-localized-routes/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/codezero-be/laravel-localized-routes/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/codezero-be/laravel-localized-routes/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/codezero-be/laravel-localized-routes/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/codezero-be/laravel-localized-routes/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/codezero/laravel-localized-routes.svg)](https://packagist.org/packages/codezero/laravel-localized-routes)

#### A convenient way to set up, manage and use localized routes in a Laravel app.

- [Automatically register](#register-routes) a route for each locale you wish to support.
- [Generate localized route URL's](#generate-route-urls) in the simplest way using the `route()` helper.
- [Redirect to localized routes](#redirect-to-routes) using the `redirect()->route()` helper.
- Allow routes to be [cached](#cache-routes).
- Let you work with routes without thinking too much about locales.
- Optionally [translate each segment](#translate-routes) in your URI's.

## Requirements

- PHP >= 7.1
- Laravel >= 5.6

## Install

```php
composer require codezero/laravel-localized-routes
```

> Laravel will automatically register the ServiceProvider.

#### Publish Configuration File

```php
php artisan vendor:publish --provider="CodeZero\LocalizedRoutes\LocalizedRoutesServiceProvider" --tag="config"
```

You will now find a `localized-routes.php` file in the `config` folder.

#### Configuration

Customize your published `config/localized-routes.php` file according to your wishes:

```php
    /**
     * The locales you wish to support.
     */
    'supported-locales' => ['en', 'nl'],

    /**
     * If set to true the unprefixed routes are also registered.
     */
    'register-unprefixed-routes' => false,

    /**
     * If set to true the app locale is set to the locale from localized route.
     */
    'set-app-locale' => false,
```

## Register Routes

Example:

```php
Route::localized(function () {

    Route::get('about', AboutController::class.'@index')
        ->name('about');

    Route::name('admin.')->group(function () {
        Route::get('admin/reports', ReportsController::class.'@index')
            ->name('reports.index');
    });

});
```

The routes defined in the `Route::localized` closure are automatically registered for each configured locale. This will prepend the locale to the route's URI and name.

If you set the `register-unprefixed-routes` option in your config file to `true`, unprefixed routes are also registered.
Those routes are treated as non localized routes and a redirect will be triggered if the locale of a localized route matches the app default locale. 

| URI               | Name                   |
| ----------------- | ---------------------- |
| /about            | about                  |
| /admin/reports    | admin.reports.index    |
| /en/about         | en.about               |
| /nl/about         | nl.about               |
| /en/admin/reports | en.admin.reports.index |
| /nl/admin/reports | nl.admin.reports.index |

In the above example, with the `register-unprefixed-routes` option set to `true`, there are 6 routes registered.
Assuming `en` is the default locale, `/en/about` will redirect to `/about`. Same with `/en/admin/reports` to `/admin/reports`.

### Generate Route URL's

You can get the URL of your named routes as usual, using the `route()` helper.

Normally you would have to include the locale whenever you want to generate a URL:

```php
$url = route(app()->getLocale().'.admin.reports.index');
```

Because that's rather ugly, this package overwrites the `route()` function and the underlying `UrlGenerator` class with an additional, optional `$locale` argument and takes care of the locale prefix for you. If you don't specify a locale, either a normal, non-localized route or a route in the current locale is returned.

```php
route($name, $parameters = [], $absolute = true, $locale = null)
```

A few examples:

```php
app()->setLocale('en');
app()->getLocale(); // 'en'

$url = route('home'); // /home (normal routes have priority)
$url = route('about'); // /en/about (current locale)

// Get specific locales...
// This is most useful if you want to generate a URL to switch language.
$url = route('about', [], true, 'en'); // /en/about
$url = route('about', [], true, 'nl'); // /nl/about

// You could also do this, but it kinda defeats the purpose...
$url = route('en.about'); // /en/about
$url = route('en.about', [], true, 'nl'); // /nl/about
```

> **Note:** in a most practical scenario you would register a route either localized **or** non-localized, but not both. If you do, you will always need to specify a locale to get the URL, because non-localized routes always have priority when using the `route()` function.

### Redirect to Routes

Laravel's `Redirector` uses the same `UrlGenerator` as the `route()` function behind the scenes. Because we are overriding this class, you can easily redirect to your routes.

```php
return redirect()->route('home'); // redirects to /home
return redirect()->route('about'); // redirects to /en/about (current locale)
```

You can't redirect to URL's in a specific locale this way, but if you need to, you can of course just use the `route()` function.

```php
return redirect(route('about', [], true, 'nl')); // redirects to /nl/about
```

### Translate Routes

If you want to translate the segments of your URI's, create a `routes.php` language file for each locale you [configured](#configure-supported-locales):

```
resources
 └── lang
      ├── en
      │    └── routes.php
      └── nl
           └── routes.php
```

In these files, add a translation for each segment.

```php
// lang/nl/routes.php
return [
    'about' => 'over',
    'us' => 'ons',
];
```

Now you can use our `Lang::uri()` macro during route registration:

```php
Route::localized(function () {

    Route::get(Lang::uri('about/us'), AboutController::class.'@index')
        ->name('about.us');

});
```

The above will generate:

- /en/about/us
- /nl/over/ons

> If a translation is not found, the original segment is used.

## Route Placeholders

Placeholders are not translated via language files. These are values you would provide via the `route()` function. The `Lang::uri()` macro will skip any placeholder segment.

If you have a model that uses a route key that is translated in the current locale, then you can still simply pass the model to the `route()` function to get translated URL's.

An example...

#### Given we have a model like this:

```php
class Post extends \Illuminate\Database\Eloquent\Model
{
    public function getRouteKey()
    {
        $slugs = [
            'en' => 'en-slug',
            'nl' => 'nl-slug',
        ];

        return $slugs[app()->getLocale()];
    }
}
```

> **TIP:** checkout [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable) for translatable models.

#### If we have a localized route like this:

```php
Route::localized(function () {

    Route::get('posts/{post}', PostsController::class.'@show')
        ->name('posts.show');

});
```

#### We can now get the URL with the appropriate slug:

```php
app()->setLocale('en');
app()->getLocale(); // 'en'

$post = new Post;

$url = route('posts.show', $post); // /en/posts/en-slug
$url = route('posts.show', $post, true, 'nl'); // /nl/posts/nl-slug
```

## Cache Routes

In production you can safely cache your routes per usual.

```php
php artisan route:cache
```

## Testing

```
composer test
```

## Security

If you discover any security related issues, please [e-mail me](mailto:ivan@codezero.be) instead of using the issue tracker.

## Changelog

See a list of important changes in the [changelog](CHANGELOG.md).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
