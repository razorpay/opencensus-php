<?php

use Http\URL;

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

//s(Route::getCurrentRequest());
Route::group(array('prefix' => 'v1'), function()
{
    Route::group(array('before' => 'auth.public'), function()
    {
        URL::addRoutes('public');
    });

    Route::group(array('before' => 'auth.private'), function()
    {
        URL::addRoutes('private');
    });

    Route::group(array('before' => 'auth.app'), function()
    {
        URL::addRoutes('internal');
    });
});

Route::get('/', function()
{
    $response['message'] = "Welcome to Razorpay API.";
    return Http\ApiResponse::json($response);
});

Route::any('{all}', function($uri)
{
    return Http\ApiResponse::routeNotFound();
})->where('all', '.*');
