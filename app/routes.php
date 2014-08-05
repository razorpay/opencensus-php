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

Route::group(array('before' => 'auth.public'), function()
{
    $method = URL::TXN_CALLBACK_METHOD;
    Route::$method(
        URL::TXN_CALLBACK_URL,
        'TransactionController@postCallback');

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

Route::group(array('before' => 'auth.app'), function()
{
    Route::post('merchants', 'MerchantController@postCreateMerchant');

    Route::get('merchants/{id}/keys', 'MerchantController@getKeys');

    Route::put('merchants/{merchantId}/keys/{keyId}', 'MerchantController@putKeys');

    Route::post('merchants/{id}/pricing', 'MerchantController@postAssignPricingPlan');

    Route::get('merchants/{id}/pricing', 'MerchantController@getPricingPlan');

    Route::post('merchants/{id}/terminal', 'MerchantController@postCreateTerminal');

    Route::get('merchants/{id}/terminal', 'MerchantController@getTerminals');

    Route::post('pricing', 'PricingController@postCreatePricingPlan');

    Route::get('pricing', 'PricingController@getPricingPlans');

    Route::get('pricing/merchants', 'PricingController@getMerchantPricingPlans');

    Route::get('pricing/gateways', 'PricingsController@getGatewayPricingPlans');

    Route::post('pricing/{id}/rule', 'PricingController@postAddPricingPlanRule');

    Route::get('pricing/{id}', 'PricingController@getPricingPlan');

    Route::get('pricing/{planId}/rule/{ruleId}', 'PricingController@getPricingPlanRule');
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
