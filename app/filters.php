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
	//
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
Route::filter('auth', function($route, $request)
{
	if($_SERVER['SERVER_NAME'] == 'test.razorpay.com')
	{
		$_SERVER['PHP_AUTH_PW']= 'd9c6bf091a1a64cb5678d8c1d5e7360f';
	}
	
	if (!isset($_SERVER['PHP_AUTH_PW']) && !isset($_SERVER['PHP_AUTH_USER']))
	{
		//Used by first request from browser thaty checks if HTTP AUTH is expected
		return Response::view('error.401', array(), 401)->header('WWW-Authenticate', "Basic realm=\"Protected Area\"");;
	}

	if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] == '')
	{	
		// For private key based auth
		if (isset($_SERVER['PHP_AUTH_PW']))
		{
			$key = $_SERVER['PHP_AUTH_PW'];

			if (BasicAuth::getInstance()->verifySecret($key) == false)
			{
				return Response::view('error.401', array(), 401);
			}
		}
		else
		{
			return Response::view('error.401', array(), 401);
		}
	}
	else 
	{ 
		//For time based hash auth
		$merchant_id = isset($_SERVER['PHP_AUTH_USER']);
		if (isset($_SERVER['PHP_AUTH_PW']))
		{
			$hash = $_SERVER['PHP_AUTH_PW'];
			
			if(BasicAuth::getInstance()->authenticate(array('id' => $merchant_id, 'hash' => $hash))==false)
			{
				return Response::view('error.401', array(), 401);
			}
			else
			{
				//Change transaction to hold type if called by public key auth
				if(isset($_POST['hold'])){
					$request->merge(array('hold'=>1));
				} 
			}
		}
		else
		{
			return Response::view('error.401', array(), 401);
		}
	}

});


/**
 * Only allows requests with public keys to get through.
 */
Route::filter('auth.public', function()
{
	if (isset($_SERVER['PHP_AUTH_USER']))
	{
		$key = $_SERVER['PHP_AUTH_USER'];

		if (BasicAuth::verifyPublic($key) == false)
		{
			return Response::view('error.401', array(), 401);
		}
	}
	else return Response::view('error.401', array(), 401);

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