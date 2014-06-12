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
});

Route::group(array('before' => 'guest'), function()
{
    Route::get('/login', 'MerchantController@getLogin');

    Route::get('/register', 'MerchantController@getRegister');

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

Route::post('/transactions', 'TransactionController@postIndex');