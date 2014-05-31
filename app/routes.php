<?php

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
Route::group(array('after' => 'sameorigin'), function()
{
	Route::group(array('before' => 'auth.public'), function()
	{
		Route::post('transactions', 'TransactionController@postIndex');
	});

	Route::group(array('before' => 'auth.private'), function()
	{
		Route::post('tokens', 'CardController@postIndex');

		Route::get('tokens/{token}', 'CardController@getRetrieve');

		Route::get('transactions/jsonp', 'TransactionController@getJSONP');

		Route::get('transactions/{param}', 'TransactionController@getIndex');

		Route::get('transactions', 'TransactionController@getIndex');

		Route::post('transactions/{id}/refund', 'TransactionController@postRefund');

		Route::post('transactions/{id}/capture', 'TransactionController@postCapture');

		//@todo: temporary
		//create an artisan command and get rid of this
		Route::get('capture', 'TransactionController@capture');

	});
});

Route::post('transactions/callback', 'TransactionController@postCallback');

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
    return Response::view('error.404', array(), 404);
});

Event::listen('500', function($exception)
{
	return Err::handle_error('500', $exception);
	// return Response::error('500');
});
