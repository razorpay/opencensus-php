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

App::before(function($request)
{
    if ($request->secure() === false)
        return BasicAuth::checkHttps($request);
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

/**
 * Only allows requests with secret keys to get through.
 */
Route::filter('auth.private',  function($route, $request)
{
    return BasicAuth::privateAuth($route, $request);
});

/**
 * Allows requests with public keys to get through.
 */
Route::filter('auth.public', function($route, $request)
{
    return BasicAuth::publicAuth($route, $request);
});

Route::filter('auth.app', function($route, $request)
{
    return BasicAuth::appAuth($route, $request);
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

Route::filter('sameorigin', function($route, $request, $response)
{
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
});
