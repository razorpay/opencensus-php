<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

//
// Initialize BasicAuth with $app
// This is put here instead of BasicAuthServiceProvider
// because ServiceProvider calls the BasicAuth constructor only
// once between unit tests while filters are called every-time
//
App::before(function() use ($app)
{
    $app['basicauth']->init($app);
});

App::before(function()
{
    return BasicAuth::verifyHttps();
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify public, private and application
| basic auth depending on the route.
|
*/

/**
 * Only allows requests with secret keys to get through.
 */
Route::filter('auth.private',  function()
{
    return BasicAuth::privateAuth();
});

/**
 * Allows requests with public keys to get through.
 */
Route::filter('auth.public', function()
{
    return BasicAuth::publicAuth();
});

Route::filter('auth.app', function()
{
    return BasicAuth::appAuth();
});

Route::filter('auth.proxy', function()
{
    return BasicAuth::proxyAuth();
});

Route::filter('auth.public_callback', function()
{
    return BasicAuth::publicCallbackAuth();
});

Route::filter('auth.public_callback', function()
{
    return BasicAuth::directAuth();
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
    if (Session::token() != Input::get('_token'))
    {
        throw new Illuminate\Session\TokenMismatchException;
    }
});

/*
|--------------------------------------------------------------------------
| X-Frame Protection Filter
|--------------------------------------------------------------------------
|
| The X-Frame filter is responsible for protecting your application against
| cross-site iframing. By default laravel does this but we have explicitly
| removed that so this filter needs to be applied everywhere we don't need
| iframe support
|
*/