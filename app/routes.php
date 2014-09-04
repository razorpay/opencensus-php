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


Route::get('/', 'MerchantController@getIndex');

Route::get('/admin', 'AdminController@getIndex');

Route::group(array('before' => 'auth'), function()
{
    Route::get('/user', 'MerchantController@getUser');

    Route::get('/user/keepalive', 'MerchantController@getKeepAlive');

    Route::get('/user/logout', 'MerchantController@getLogout');

    Route::get('/keys/csv', 'MerchantController@getCsv');

    Route::get('/activation/details', 'MerchantController@getActivationDetails');

    Route::get('/{mode}/transactions', 'TransactionController@getTransactions');

    Route::get('/{mode}/transactions/{id}', 'TransactionController@getTransaction');

    Route::get('/{mode}/analytics/transactions', 'TransactionController@getAnalytics');

    Route::get('/{mode}/analytics/aggregations', 'TransactionController@getAggregations');

    Route::get('/{mode}/keys', 'MerchantController@getKeys');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::post('/activation', 'MerchantController@postActivation');

        Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep');

        Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile');

        Route::post('/password', 'MerchantController@postPassword');

        Route::post('/{mode}/keys', 'MerchantController@postKeys');

        Route::post('/{mode}/key/new', 'MerchantController@postNewKey');

        Route::post('/{mode}/transactions/{id}/capture', 'TransactionController@postCaptureTransaction');

        Route::post('/{mode}/transactions/{id}/refund', 'TransactionController@postRefundTransaction');
    });
});

Route::group(array('before' => 'guest'), function()
{
    Route::get('/user/confirm/{token}', 'MerchantController@getConfirm');
    
    Route::group(array('before' => 'csrf'), function()
    {
        Route::post('/user/signin', 'MerchantController@postSignin');

        Route::post('/user/register', 'MerchantController@postRegister');

        Route::post('/user/password/reset', 'PasswordController@postRemind');

        Route::post('/user/password/reset/{token}', 'PasswordController@postReset');
    });
});

Route::group(array('before' => 'auth_admin'), function()
{   
    Route::get('/admin/user', 'AdminController@getAdmin');

    Route::get('/admin/logout', 'AdminController@getLogout');

    Route::get('/admin/keepalive', 'AdminController@getKeepAlive');

    Route::get('/admin/merchant/list', 'AdminController@getMerchantList');

    Route::get('/admin/merchant/{id}', 'AdminController@getMerchant');

    Route::get('/admin/merchant/{id}/details', 'AdminController@getMerchantDetails');

    Route::get('/admin/merchant/{id}/login', 'AdminController@getMerchantLogin');

    Route::get('/admin/merchant/{id}/terminal', 'AdminController@getMerchantTerminal');

    Route::get('/admin/merchant/{id}/pricing', 'AdminController@getMerchantPricing');

    Route::get('/admin/pricing/list', 'AdminController@getPricingList');

    Route::get('/admin/pricing/new', 'AdminController@getNewPricingPlan');

    Route::get('/admin/pricing/{id}', 'AdminController@getPricingRules');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::get('/admin/merchant/{id}/lock', 'AdminController@getLockMerchantDetails');

        Route::get('/admin/merchant/{id}/unlock', 'AdminController@getUnlockMerchantDetails');

        Route::post('/admin/password', 'AdminController@postPassword');

        Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal');

        Route::post('/admin/merchant/{id}/pricing', 'AdminController@postMerchantPricing');
        
        Route::post('/admin/pricing/new', 'AdminController@postNewPricingPlan');

        Route::post('/admin/pricing/{id}', 'AdminController@postPricingRules');
        
        Route::get('/admin/merchant/{id}/activate', 'AdminController@getMerchantActivation');

        Route::get('/admin/merchant/{id}/deactivate', 'AdminController@getMerchantDeactivation');
    });

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
    Route::post('/admin/signin', array('before' => 'csrf','uses'=> 'AdminController@postSignin'));

    Route::post('/admin/duologin', 'AdminController@postDuologin');
});

Route::group(array('before' => 'auth.internal'), function()
{
    Route::post('/{mode}/transactions', 'TransactionController@postIndex');
});

Route::post('/contact', 'MerchantController@postContact');