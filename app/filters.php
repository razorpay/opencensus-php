
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

Route::filter('auth', function()
{
	if (Auth::merchant()->guest()) return Response::json(array('success' => false, 'data' => array()));
});

Route::filter('auth_admin', function()
{
	if (Auth::admin()->guest()) return Response::json(array('success' => false, 'data' => array()));
});

Route::filter('superadmin', function()
{
    if (Auth::admin()->user()->isSuperAdmin() === false)
        return App::abort(403, 'Unauthorized action.');
});

Route::filter('auth.internal', function()
{
	if ($_SERVER['REMOTE_ADDR'] !== $_SERVER['SERVER_ADDR'] || $_SERVER['REMOTE_ADDR'] !== \Config::get('api.ip'))
		return Response::view('error.401', array(), 401);
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
	if (Auth::merchant()->check()) return Redirect::to('/');
});

Route::filter('guest_admin', function()
{
	if (Auth::admin()->check()) return Redirect::to('/admin');
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
		return array('success'=>false, 'errors'=>array('Session timed out. Please refresh the page and try again.'));
	}
});
