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
*/

/*
 | The order in which routes are defined is very important.
 | Whenever the order of routes is changed,
 | make sure to run the full test suite
 */

$method = URL::TXN_CALLBACK_METHOD;
Route::$method(
    URL::TXN_CALLBACK_URL,
    'TransactionController@postCallback');

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

Route::group(array('before' => 'auth.app'), function()
{
    Route::post('merchants', 'MerchantController@postIndex');

    Route::get('keys', 'MerchantController@getKeys');

    Route::put('keys/{id}', 'MerchantController@putKeys');
});

Route::get('/', function()
{
    $response['message'] = "Welcome to Razorpay API.";
    return Response::json($response);
});

Route::any('{all}', function($uri)
{
    return Response::routeNotFound();
})->where('all', '.*');
