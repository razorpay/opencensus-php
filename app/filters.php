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
	if($_SERVER['HTTP_HOST'] == 'api.razorpay.com' && !Request::secure()){
							$response['error']['message'] = "Razorpay API is only available over HTTPS";
							$response['error']['code'] = "NONHTTPS";
							return Response::json($response);
	}
});

App::after(function($request, $response)

{
	//
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
Route::filter('auth.private', function($route, $request)
{
	if (!isset($_SERVER['PHP_AUTH_PW']) || !isset($_SERVER['PHP_AUTH_USER']))
	{
		//Used by first request from browser that checks if HTTP AUTH is expected
		return Response::view('error.401', array(), 401)->header('WWW-Authenticate', "Basic realm=\"Protected Area\"");;
	}

	if(! BasicAuth::getInstance()->verifySecret($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
		return Response::view('error.401', array(), 401);
});


/**
 * Allows requests with public keys (time based hash) to get through. (Also allows private key based requests too
 */
Route::filter('auth.public', function($route, $request)
{
		if (!isset($_SERVER['PHP_AUTH_PW']) || !isset($_SERVER['PHP_AUTH_USER']))
		{
			//Used by first request from browser that checks if HTTP AUTH is expected
			return Response::view('error.401', array(), 401)->header('WWW-Authenticate', "Basic realm=\"Protected Area\"");;
		}

		if(! BasicAuth::getInstance()->verifyPublic($_SERVER['PHP_AUTH_USER']))
		{
			if(! BasicAuth::getInstance()->verifySecret($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
				return Response::view('error.401', array(), 401);
		}
		// else
		// {
		// 	if(isset($_POST['hold']))
		// 	{
		// 		$request->merge(array('hold'=>1));
		// 	}
		// }
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
