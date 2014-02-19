<?php

use Service\BasicAuth;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Simply tell Laravel the HTTP verbs and URIs it should respond to. It is a
| breeze to setup your application using Laravel's RESTful routing and it
| is perfectly suited for building large applications and simple APIs.
|
| Let's respond to a simple GET request to http://example.com/hello:
|
|		Route::get('hello', function()
|		{
|			return 'Hello World!';
|		});
|
| You can even respond to more than one URI:
|
|		Route::post(array('hello', 'world'), function()
|		{
|			return 'Hello World!';
|		});
|
| It's easy to allow URI wildcards using (:num) or (:any):
|
|		Route::put('hello/(:any)', function($name)
|		{
|			return "Welcome, $name.";
|		});
|
*/

Route::group(array('before' => 'auth.public'), function()
{
	
});

Route::group(array('before' => 'auth'), function()
{
	Route::post('transactions', 'transaction@index');

	Route::post('tokens', 'card@index');

	Route::get('tokens/(:any)', 'card@retrieve');

	Route::get('transactions', 'transaction@index');

	Route::get('transactions/(:any)', 'transaction@index');

	Route::post('transactions/(:any)/refund', 'transaction@refund');

	Route::get('transactions/refund', 'transaction@refund');

	Route::post('transactions/(:any)/process', 'transaction@process');

	Route::get('transactions/success', 'transaction@process');
});

Route::get('/', function()
{
	return View::make('hello');
});

/*
|--------------------------------------------------------------------------
| Application 404 & 500 Error Handlers
|--------------------------------------------------------------------------
|
| To centralize and simplify 404 handling, Laravel uses an awesome event
| system to retrieve the response. Feel free to modify this function to
| your tastes and the needs of your application.
|
| Similarly, we use an event to handle the display of 500 level errors
| within the application. These errors are fired when there is an
| uncaught exception thrown in the application. The exception object
| that is captured during execution is then passed to the 500 listener.
|
*/

Event::listen('404', function()
{
	return Response::error('404');
});

Event::listen('500', function($exception)
{
	return Err::handle_error('500', $exception);
	// return Response::error('500');
});

/*
|--------------------------------------------------------------------------
| Route Filters
|--------------------------------------------------------------------------
|
| Filters provide a convenient method for attaching functionality to your
| routes. The built-in before and after filters are called before and
| after every request to your application, and you may even create
| other filters that can be attached to individual routes.
|
| Let's walk through an example...
|
| First, define a filter:
|
|		Route::filter('filter', function()
|		{
|			return 'Filtered!';
|		});
|
| Next, attach the filter to a route:
|
|		Route::get('/', array('before' => 'filter', function()
|		{
|			return 'Hello World!';
|		}));
|
*/

Route::filter('before', function()
{
	// Do stuff before every request to your application...
});

Route::filter('after', function($response)
{
	// Do stuff after every request to your application...
});

Route::filter('csrf', function()
{
	if (Request::forged()) return Response::error('500');
});

/**
 * Only allows requests with secret keys to get through.
 */
Route::filter('auth', function()
{
	BasicAuth::verify_key();

	if ((BasicAuth::check() == false) or
		(BasicAuth::secret() == false))
		return Response::error('401');
});

/**
 * Only allows requests with public keys to get through.
 */
Route::filter('auth.public', function()
{
	BasicAuth::verify_key();

	if ((BasicAuth::check() == false) or
		(BasicAuth::secret() == true))
		return Response::error('401');

});