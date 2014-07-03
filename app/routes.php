<?php

use Constants\URL;

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
|       Route::get('hello', function()
|       {
|           return 'Hello World!';
|       });
|
| You can even respond to more than one URI:
|
|       Route::post(array('hello', 'world'), function()
|       {
|           return 'Hello World!';
|       });
|
| It's easy to allow URI wildcards using (:num) or (:any):
|
|       Route::put('hello/(:any)', function($name)
|       {
|           return "Welcome, $name.";
|       });
|
*/

Route::group(array('after' => 'sameorigin'), function()
{
    Route::group(array('before' => 'auth.public'), function()
    {
        $method = URL::TXN_CREATE_METHOD;
        Route::$method(
            URL::TXN_CREATE_URL,
            'TransactionController@postIndex');

        $method = URL::TXN_JSONP_METHOD;
        Route::$method(
            URL::TXN_JSONP_URL,
            'TransactionController@getJSONP');
    });

    Route::group(array('before' => 'auth.private'), function()
    {
        Route::post('tokens', 'CardController@postIndex');

        Route::get('tokens/{token}', 'CardController@getRetrieve');

        $method = URL::TXN_RETRIEVE_METHOD;
        Route::$method(
            URL::TXN_RETRIEVE_BY_ID_URL,
            'TransactionController@getTxnById');

        Route::$method(
            URL::TXN_RETRIEVE_MULTIPLE_URL,
            'TransactionController@getMultipleTxn');

        $method = URL::TXN_REFUND_METHOD;
        Route::$method(
            URL::TXN_REFUND_URL,
            'TransactionController@postRefund');

        $method = URL::TXN_CAPTURE_METHOD;
        Route::$method(
            URL::TXN_CAPTURE_URL,
            'TransactionController@postCapture');
    });
});

$method = URL::TXN_CALLBACK_METHOD;
Route::$method(
    URL::TXN_CALLBACK_URL,
    'TransactionController@postCallback');

Route::group(array('before' => 'auth.app'), function()
{
    Route::post('merchants', 'MerchantController@postIndex');

    Route::get('merchants/keys', 'MerchantController@getKeys');

    Route::put('merchants/keys', 'MerchantController@updateKeys');
});

Route::get('/', function()
{
    $response['message'] = "Welcome to Razorpay API.";
    return Response::json($response);
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
