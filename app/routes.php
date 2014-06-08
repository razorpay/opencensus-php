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
});

Route::group(array('before' => 'guest'), function()
{
    Route::get('/login', 'MerchantController@getLogin');
    
    Route::post('/login', 'MerchantController@postLogin');

    Route::get('/register', 'MerchantController@getRegister');
    
    Route::post('/register', 'MerchantController@postRegister');
});

Route::post('/transactions', 'TransactionController@postIndex');