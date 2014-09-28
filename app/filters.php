<?php

use Http\AppResponse;

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
    //Set Cookie for use by angular (Setting in after filter fails selenium tests, hence queuing in before filter)
	Cookie::queue('XSRF-TOKEN', csrf_token(), 0, '/', null, false, false);
});


App::after(function($request, $response)
{
	//This is necessary for protection against json/jsonp array vulnerability
	//Refer https://docs.angularjs.org/api/ng/service/$http JSON Vulnerability Protection
	if($response instanceof \Illuminate\Http\JsonResponse) {
        $json = ")]}',\n" . $response->getContent();
    
        return $response->setContent($json);
    }
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

Route::filter('auth', function()
{
	if (Auth::merchant()->guest()) 
		return Response::json(array('success' => false, 'data' => array()));
});

Route::filter('auth_admin', function()
{
	if (Auth::admin()->guest()) 
		return Response::json(array('success' => false, 'data' => array()));
});

Route::filter('superadmin', function()
{
    if (Auth::admin()->user()->isSuperAdmin() === false)
        return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
});

Route::filter('auth.internal', function()
{
	if ($_SERVER['PHP_AUTH_USER'] !== \Config::get('api.auth_user') or $_SERVER['PHP_AUTH_PW'] !== \Config::get('api.auth_pass'))
		return Response::json(array('success' => false, 'errors' => ['Unauthorised']));
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
	if (Auth::merchant()->check())  
		return AppResponse::jsonResponse(array("You are already logged in, please refresh and try again"));
});

Route::filter('guest_admin', function()
{
	if (Auth::admin()->check()) 
		return AppResponse::jsonResponse(array("You are already logged in, please refresh and try again"));
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
    //Angular sends X-XSRF-TOKEN header with all request because XSRF-TOKEN cookie is set in after filter   
	if (!Request::header('X-XSRF-TOKEN') or (Session::token() !== Crypt::decrypt(Request::header('X-XSRF-TOKEN'))))
		return AppResponse::jsonResponse(array('Invalid session. Please refresh the page and try again.'));
});
