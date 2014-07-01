<?php

use Models\Service\BasicAuth;

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
    if ((isset($_SERVER['HTTP_HOST'])) and
        ($_SERVER['HTTP_HOST'] == 'api.razorpay.com') and
        (Request::secure() === false))
    {
        $response['error']['message'] = "Razorpay API is only available over HTTPS";

        $response['error']['code'] = "BAD_REQUEST_ERROR";

        return Response::json($response);
    }
});

//
//  Prevent browser caching
//
App::after(function($request, $response)
{
    //
    // Ask browser not to cache
    //
    $response->headers->set('Cache-Control','nocache, no-store, max-age=0, must-revalidate');

    $response->headers->set('Pragma','no-cache');

    //
    // Put old time so that any browser cache gets expired
    //
    $response->headers->set('Expires','Fri, 01 Jan 1990 00:00:00 GMT');
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

Response::macro('httpAuthExpected', function()
{
    return Response::view('error.401', array(), 401)
                   ->header('WWW-Authenticate', "Basic realm=\"Protected Area\"");
});

if (! function_exists('isBasicAuthUserAndPwdNotSet'))
{
    function isBasicAuthUserAndPwdNotSet()
    {
        return ((isset($_SERVER['PHP_AUTH_PW']) === false) or
                (isset($_SERVER['PHP_AUTH_USER']) === false));
    }
}

if (! function_exists('basicAuthVerifySecret'))
{
    function basicAuthVerifySecret()
    {
        return BasicAuth::getInstance()
                        ->verifySecret(
                            $_SERVER['PHP_AUTH_USER'],
                            $_SERVER['PHP_AUTH_PW']);
    }
}

if (! function_exists('basicAuthVerifySecret'))
{
    function basicAuthVerifyApp()
    {
        return BasicAuth::getInstance()
                        ->verifyApp(
                            $_SERVER['PHP_AUTH_USER'],
                            $_SERVER['PHP_AUTH_PW']);
    }
}

/**
 * Only allows requests with secret keys to get through.
 */
Route::filter('auth.private', function($route, $request)
{
    if (isBasicAuthUserAndPwdNotSet())
    {
        //
        // Used by first request from browser that
        // checks if HTTP AUTH is expected
        //
        return Response::httpAuthExpected();
    }

    if (basicAuthVerifySecret() === false)
    {
        //
        // @todo: add check for internal IP here
        //
        if (basicAuthVerifyApp() === false)
        {
            return Response::view('error.401', array(), 401);
        }
    }
});


/**
 * Allows requests with public keys to get through. Also allows private key based requests too
 */
Route::filter('auth.public', function($route, $request)
{
    if (isBasicAuthUserAndPwdNotSet())
    {
        //
        // Used by first request from browser that checks if BasicAuth is expected
        //
        return Response::httpAuthExpected();
    }

    $ba = BasicAuth::getInstance();

    if($ba->verifyPublic($_SERVER['PHP_AUTH_USER']) === false)
    {
        if (basicAuthVerifySecret() === false)
        {
            return Response::view('error.401', array(), 401);
        }
    }
});

Route::filter('auth.app', function($route, $request)
{
    if (isBasicAuthUserAndPwdNotSet())
    {
        //
        // Used by first request from browser
        // that checks if HTTP AUTH is expected
        //
        return Response::httpAuthExpected();
    }

    if (basicAuthVerifyApp() === false)
    {
        return Response::view('error.401', array(), 401);
    }
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
    if (Auth::check()) return Redirect::to('/');
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
