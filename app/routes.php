<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


Route::group(array('before' => 'auth'), function()
{
    Route::get('/', 'MerchantController@getIndex');

    Route::get('/logout', 'MerchantController@getLogout');

    Route::get('/transactions', 'TransactionController@getTransactions');

    Route::get('/transactions/{id}', 'TransactionController@getTransaction');

    Route::get('/analytics/transactions', 'TransactionController@getAnalytics');

    Route::get('/analytics/aggregations', 'TransactionController@getAggregations');

    Route::get('/keys/csv', 'MerchantController@getCsv');

    Route::get('/account', 'MerchantController@getAccount');

    Route::get('/keys', 'MerchantController@getKeys');

    Route::get('/activation', 'MerchantController@getActivation');

    Route::get('/activation/details', 'MerchantController@getActivationDetails');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::post('/keys', 'MerchantController@postKeys');

        Route::post('/activation', 'MerchantController@postActivation');

        Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep');

        Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile');
    });
});

Route::group(array('before' => 'guest'), function()
{
    Route::get('/login', 'MerchantController@getLogin');

    Route::get('/register', 'MerchantController@getRegister');

    Route::get('/register/confirm/{token}', 'MerchantController@getConfirm');

    Route::get('/password/reset', 'PasswordController@getRemind');

    Route::get('/password/reset/{token}', 'PasswordController@getReset');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::post('/login', 'MerchantController@postLogin');

        Route::post('/register', 'MerchantController@postRegister');

        Route::post('/password/reset', 'PasswordController@postRemind');

        Route::post('/password/reset/{token}', 'PasswordController@postReset');
    });
});

Route::group(array('before' => 'auth.internal'), function()
{
    Route::post('/transactions', 'TransactionController@postIndex');
});

Route::post('/contact', 'MerchantController@postContact');