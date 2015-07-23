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

Route::post('/contact', 'MerchantController@postContact');

Route::group(array('before' => 'auth.merchant'), function()
{
    Route::get('/user', 'MerchantController@getMerchant');

    Route::get('/user/keepalive', 'MerchantController@getKeepAlive');

    Route::get('/user/logout', 'MerchantController@getLogout');

    Route::get('/activation/details', 'MerchantController@getActivationDetails');

    Route::get('/{mode}/payments', 'TransactionController@getPayments');

    Route::get('/{mode}/payments/{id}', 'TransactionController@getPayment');

    Route::get('/{mode}/payments/{id}/refunds', 'TransactionController@getPaymentRefunds');

    Route::get('/{mode}/refunds', 'TransactionController@getRefunds');

    Route::get('/{mode}/refunds/{id}', 'TransactionController@getRefund');

    Route::get('/{mode}/settlements', 'TransactionController@getSettlements');

    Route::get('/{mode}/settlements/{id}', 'TransactionController@getSettlement');

    Route::get('/{mode}/transactions', 'TransactionController@getTransactions');

    Route::get('/{mode}/transactions/{id}', 'TransactionController@getTransaction');

    Route::get('/{mode}/analytics/transactions', 'TransactionController@getAnalytics');

    Route::get('/{mode}/analytics/aggregations', 'TransactionController@getAggregations');

    Route::get('/{mode}/analytics/payment/aggregations', 'TransactionController@getPaymentAggregations');

    Route::get('/{mode}/keys', 'MerchantController@getKeys');

    Route::get('/keys/csv', 'MerchantController@getCsv');

    Route::get('/apihost', 'MerchantController@getApihost');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::post('/password', 'MerchantController@postPassword');

        Route::post('/activation', 'MerchantController@postActivation');

        Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep');

        Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile');

        Route::post('/{mode}/keys', 'MerchantController@postKeys');

        Route::post('/{mode}/key/new', 'MerchantController@postNewKey');

        Route::post('/{mode}/payments/{id}/capture', 'TransactionController@postCapturePayment');

        Route::post('/{mode}/payments/{id}/refund', 'TransactionController@postRefundPayment');

        Route::post('/{mode}/addfunds', 'TransactionController@postAddfunds');
    });
});

Route::group(array('before' => 'guest.merchant'), function()
{
    Route::get('/user/confirm/{token}', 'MerchantController@getConfirm');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::post('/user/signin', 'MerchantController@postSignin');

        Route::post('/user/register', 'MerchantController@postRegister');

        Route::post('/user/resend', 'MerchantController@postResendConfirmation');

        Route::post('/user/password/reset', 'PasswordController@postRemind');

        Route::post('/user/password/reset/{token}', 'PasswordController@postReset');
    });
});

Route::group(array('before' => 'auth.admin'), function()
{
    Route::get('/admin/user', 'AdminController@getAdmin');

    Route::get('/admin/user/logout', 'AdminController@getLogout');

    Route::get('/admin/user/keepalive', 'AdminController@getKeepAlive');

    Route::get('/admin/merchant/list', 'AdminController@getMerchantList');

    Route::get('/admin/merchant/{id}', 'AdminController@getMerchant');

    Route::get('/admin/merchant/{id}/balance', 'AdminController@getMerchantBalance');

    Route::get('/admin/merchant/{id}/details', 'AdminController@getMerchantDetails');

    Route::get('/admin/merchant/{id}/banks', 'AdminController@getMerchantBanks');

    Route::get('/admin/merchant/{id}/login', 'AdminController@getMerchantLogin');

    Route::get('/admin/pricing/list', 'AdminController@getPricingList');

    Route::get('/admin/pricing/{id}', 'AdminController@getPricingRules');

    Route::get('/admin/merchant/{id}/hdfc_excel', 'AdminController@getMerchantHdfcExcel');

    Route::get('/admin/beneficiary/dl', 'AdminController@getBeneficiaryFile');

    Route::group(array('before' => 'csrf'), function()
    {
        Route::get('/admin/merchant/{id}/lock', 'AdminController@getLockMerchantDetails');

        Route::get('/admin/merchant/{id}/unlock', 'AdminController@getUnlockMerchantDetails');

        Route::post('/admin/password', 'AdminController@postPassword');

        Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal');

        Route::post('/admin/merchant/{id}/pricing', 'AdminController@postMerchantPricing');

        Route::post('/admin/pricing/new', 'AdminController@postNewPricingPlan');

        Route::post('/admin/pricing/{id}', 'AdminController@postPricingRules');

        Route::post('/admin/merchant/{id}/edit', 'AdminController@postEditMerchant');

        Route::post('/admin/merchant/{id}/comment/edit', 'AdminController@postEditMerchantComment');

        Route::post('/admin/merchant/{id}/banks', 'AdminController@postMerchantBanks');

        Route::post('admin/merchant/{id}/addadjustment', 'AdminController@postAddAdjustment');

        Route::get('/admin/merchant/{id}/activate', 'AdminController@getMerchantActivation');

        Route::get('/admin/merchant/{id}/live/enable', 'AdminController@getMerchantLiveEnable');

        Route::get('/admin/merchant/{id}/live/disable', 'AdminController@getMerchantLiveDisable');

        Route::get('/admin/merchant/{id}/methods/{method}/enable', 'AdminController@getMerchantMethodEnable');

        Route::get('/admin/merchant/{id}/methods/{method}/disable', 'AdminController@getMerchantMethodDisable');

        Route::post('/admin/settlement/initiate/{channel}', 'AdminController@postInitiateSetl');

        Route::post('/admin/iin/add', 'AdminController@postAddIIN');

        Route::get('/admin/payment/{id}/verify', 'AdminController@getVerifyPayment');
    });

    Route::get('/admin/{mode}/fetchentity/{entity}', 'AdminController@getMultipleEntities');

    Route::get('/admin/{mode}/fetchentity/{entity}/{entity_id}', 'AdminController@getEntityById');

    Route::group(array('before' => 'auth.superadmin'), function()
    {
        Route::get('/admin/users', 'AdminController@getAdmins');

        Route::post('/admin/users/add', array('before'=>'csrf', 'uses'=> 'AdminController@postAddAdmin'));

        Route::get('/admin/users/{id}/delete', array('before'=>'csrf', 'uses'=>'AdminController@getDeleteAdmin'));

    });
});

Route::group(array('before' => 'guest.admin'), function()
{
    Route::post('/admin/signin', array('before' => 'csrf','uses'=> 'AdminController@postSignin'));
});

Route::group(array('before' => 'auth.internal'), function()
{
    Route::post('/{mode}/transactions/{resource}', 'TransactionController@postIndex');
});