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

    Route::get('/password', 'MerchantController@getPassword');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::post('/keys', 'MerchantController@postKeys');

        Route::post('/activation', 'MerchantController@postActivation');

        Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep');

        Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile');

        Route::post('/password', 'MerchantController@postPassword');
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

Route::group(array('before' => 'auth_admin'), function()
{
    Route::get('/admin', 'AdminController@getIndex');

    Route::get('/admin/logout', 'AdminController@getLogout');

    Route::get('/admin/password', 'AdminController@getPassword');

    Route::post('/admin/password', array('before'=>'csrf', 'uses'=>'AdminController@postPassword'));

    Route::get('/admin/merchant/list', 'AdminController@getMerchantList');

    Route::get('/admin/merchant/{id}', 'AdminController@getMerchant');

    Route::get('/admin/merchant/{id}/details', 'AdminController@getMerchantDetails');

    Route::get('/admin/merchant/{id}/login', array('before'=>'csrf', 'uses'=>'AdminController@getMerchantLogin'));

    Route::get('/admin/merchant/{id}/lock', array('before'=>'csrf', 'uses' => 'AdminController@getLockMerchantDetails'));

    Route::get('/admin/merchant/{id}/unlock', array('before'=>'csrf', 'uses' => 'AdminController@getUnlockMerchantDetails'));

    Route::get('/admin/merchant/{id}/terminal', 'AdminController@getMerchantTerminal');

    Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal');

    Route::get('/admin/merchant/{id}/pricing', 'AdminController@getMerchantPricing');

    Route::post('/admin/merchant/{id}/pricing', 'AdminController@postMerchantPricing');

    Route::get('/admin/merchant/{id}/activate', array('before'=>'csrf', 'uses' => 'AdminController@getMerchantActivation'));

    Route::get('/admin/merchant/{id}/deactivate', array('before'=>'csrf', 'uses' => 'AdminController@getMerchantDeactivation'));

    Route::get('/admin/pricing/list', 'AdminController@getPricingList');

    Route::get('/admin/pricing/new', 'AdminController@getNewPricingPlan');

    Route::post('/admin/pricing/new', array('before'=>'csrf', 'uses'=>'AdminController@postNewPricingPlan'));

    Route::get('/admin/pricing/{id}', 'AdminController@getPricingRules');

    Route::post('/admin/pricing/{id}', array('before'=>'csrf', 'uses'=>'AdminController@postPricingRules'));

    Route::group(array('before' => 'superadmin'), function()
    {
        Route::get('/admin/users', 'AdminController@getAdmins');

        Route::get('/admin/users/{id}/delete', array('before'=>'csrf', 'uses'=>'AdminController@getDeleteAdmin'));

        Route::get('/admin/users/add', 'AdminController@getAddAdmin');

        Route::post('/admin/users/add', array('before'=>'csrf', 'uses'=> 'AdminController@postAddAdmin'));

    });
});

Route::group(array('before' => 'guest_admin'), function()
{
    Route::get('/admin/login', 'AdminController@getLogin');

    Route::post('/admin/login', array('before' => 'csrf','uses'=> 'AdminController@postLogin'));

    Route::post('/admin/duologin', 'AdminController@postDuologin');
});

Route::group(array('before' => 'auth.internal'), function()
{
    Route::post('/transactions', 'TransactionController@postIndex');
});

Route::post('/contact', 'MerchantController@postContact');