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
Route::get('/', 'UserController@getIndex')->name('dashboard');
Route::get('/admin', 'AdminController@getIndex');
// This is for enabling CORS support on contact form submissions
Route::options('/contact', 'MerchantController@optionsContact');
Route::post('/contact', 'MerchantController@postContact');
Route::group(['middleware'  =>  'auth:user'], function()
{
    Route::get('/user/keepalive', 'UserController@getKeepAlive');
    Route::get('/user/logout', 'UserController@getLogout');

    // This returns all the needed information
    Route::get('/user', 'UserController@getUserDetails');
    Route::get('/user/details', 'UserController@getUserDetails');
    Route::get('/activation/details', 'MerchantController@getActivationDetails')->name('get_activation_details');
    Route::get('/{mode}/payments', 'TransactionController@getPayments');

    // Order Routes
    Route::get('/{mode}/orders', 'TransactionController@getOrders');
    Route::get('/{mode}/orders/{id}', 'TransactionController@getOrder');
    Route::get('/{mode}/orders/{id}/payments', 'TransactionController@getOrderPayments');

    Route::get('/{mode}/payments/{id}', 'TransactionController@getPayment');
    Route::get('/{mode}/payments/{id}/card', 'TransactionController@getPaymentCardData');
    Route::get('/{mode}/payments/{id}/refunds', 'TransactionController@getPaymentRefunds');

    Route::get('/{mode}/refunds', 'TransactionController@getRefunds');
    Route::get('/{mode}/refunds/{id}', 'TransactionController@getRefund');

    Route::get('/{mode}/settlements', 'TransactionController@getSettlements')->name('settlements');
    Route::get('/{mode}/settlements/{id}', 'TransactionController@getSettlement')->name('settlement');
    Route::get('/{mode}/settlements/{id}/details', 'TransactionController@getSettlementDetails')->name('settlement_detail');

    Route::get('/{mode}/transactions', 'TransactionController@getTransactions');
    Route::get('/{mode}/transactions/{id}', 'TransactionController@getTransaction');

    Route::get('/{mode}/analytics/transactions', 'TransactionController@getAnalytics');
    Route::get('/{mode}/analytics/aggregations', 'TransactionController@getAggregations');
    Route::get('/{mode}/analytics/payment/aggregations', 'TransactionController@getPaymentAggregations');

    Route::get('/{mode}/keys', 'MerchantController@getKeys')->name('get_keys');
    Route::get('/keys/csv', 'MerchantController@getCsv');
    Route::get('/apihost', 'MerchantController@getApihost');

    Route::get('/config', 'MerchantController@getMerchantConfig')->name('get_config');
    Route::put('/config', 'MerchantController@putMerchantConfig')->name('put_config');
    Route::post('/config/logo', 'MerchantController@postMerchantConfigLogo')->name('post_config_logo');

    Route::get('/referrals', 'MerchantController@getReferredMerchants');
    Route::get('/{mode}/webhooks', 'MerchantController@getWebhooks')->name('get_webhooks');
    Route::get('/{mode}/balance', 'MerchantController@getBalance');
    Route::get('/bank_account', 'MerchantController@getBankAccount');

    // Invitation and Team Support
    Route::get('settings/merchants/owned', 'UserController@getOwnedMerchantForUser');
    Route::get('settings/invitations', 'InvitationsController@getPendingInvitationsForUser');
    Route::get('settings/invitations/{invite}/resend', 'InvitationsController@getResendMerchantInvitation');
    Route::get('settings/invitations/pending', 'InvitationsController@switchCurrentMerchant');

    Route::get('/{mode}/reports/invoice', 'TransactionController@getInvoiceReport')->name('reports_invoice');
    Route::get('/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('reports_entity');

    // This is a sensitive route
    Route::get('settings/merchants/switch/{id}', 'UserController@switchCurrentMerchant');

    // Team Administration
    Route::put('settings/merchants/owned/members/{id}', 'MerchantController@updateTeamMember');
    Route::delete('settings/merchants/owned/members/{id}', 'MerchantController@removeTeamMember');

    // Invite Administration (Owners)
    Route::post('settings/invitations', 'InvitationsController@postSendMerchantInvitation');
    Route::put('settings/invitations/{invite}', 'InvitationsController@updateMerchantInvitation');
    Route::delete('settings/invitations/{invite}', 'InvitationsController@deleteMerchantInvitationForUser');

    // Invitation related (User side)
    Route::post('settings/invitations/{invite}/accept', 'InvitationsController@postAcceptMerchantInvitation');
    Route::delete('settings/invitations/{invite}/reject', 'InvitationsController@deleteRejectMerchantInvitation');

    Route::post('/password', 'UserController@postPassword');
    Route::post('/activation', 'MerchantController@postActivation')->name('post_activation');
    Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep')->name('post_activation_save_step');
    Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile')->name('post_activation_save_file');
    Route::post('/{mode}/keys', 'MerchantController@postKeys')->name('post_keys');
    Route::post('/{mode}/key/new', 'MerchantController@postNewKey');
    Route::post('/{mode}/payments/{id}/capture', 'TransactionController@postCapturePayment')->name('post_capture');
    Route::post('/{mode}/payments/{id}/refund', 'TransactionController@postRefundPayment')->name('post_refund');
    Route::post('/{mode}/addfunds', 'TransactionController@postAddfunds');
    Route::post('/{mode}/webhooks', 'MerchantController@postAddWebhook')->name('post_webhooks');
    Route::put('/{mode}/webhooks/{id}', 'MerchantController@putEditWebhook')->name('edit_webhooks');

    // Upgrades a standard invited user to a merchant
    Route::post('/merchants/register', 'UserController@postUpgradeUserToMerchant');

    // Registers a sub-merchant account
    Route::post('/submerchants', 'MerchantController@postRegisterSubmerchant');
});
Route::group([], function()
{
    Route::get('/user/confirm/{token}', 'MerchantController@getConfirm');
    Route::group([], function()
    {
        Route::post('/user/signin', 'UserController@postSignin');
        Route::post('/user/register', 'UserController@postRegister');
        Route::post('/user/resend', 'MerchantController@postResendConfirmation');
        Route::post('/user/password/reset', 'PasswordController@postRemind');
        Route::post('/user/password/reset/{token}', 'PasswordController@postReset');
    });
});

Route::group(['middleware'  =>  'slack'], function ()
{
    Route::post('/slack', 'AdminController@postSlackQuery');
});

Route::group(['middleware'  =>  'admin'], function()
{
    Route::get('/admin/user', 'AdminController@getAdmin');
    Route::get('/admin/user/logout', 'AdminController@getLogout');
    Route::get('/admin/user/keepalive', 'AdminController@getKeepAlive');
    Route::get('/admin/merchant/list', 'AdminController@getMerchantList');
    Route::get('/admin/merchant/{id}', 'AdminController@getMerchant');
    Route::get('/admin/merchant/{id}/balance', 'AdminController@getMerchantBalance');
    Route::get('/admin/merchant/{id}/details', 'AdminController@getMerchantDetails');
    Route::get('/admin/merchant/{id}/features', 'AdminController@getMerchantFeatures');
    // This is the list of banks in netbanking
    Route::get('/admin/merchant/{id}/banks', 'AdminController@getMerchantBanks');
    Route::get('/admin/networks', 'AdminController@getSupportedNetworks');

    // This is the merchant's bank account
    Route::get('/admin/merchant/{id}/bank_account', 'AdminController@getMerchantBankAccount');
    Route::get('/admin/merchant/{id}/login', 'AdminController@getMerchantLogin');
    Route::get('/admin/activity', 'AdminController@getAdminActivity');
    Route::delete('/admin/activity', 'AdminController@deleteOtherAdminActivity');
    Route::delete('/admin/activity/{id}', 'AdminController@deleteAdminActivity');

    Route::get('/admin/pricing/list', 'AdminController@getPricingList');
    Route::get('/admin/pricing/{id}', 'AdminController@getPricingRules');
    Route::get('/admin/merchant/{id}/hdfc_excel', 'AdminController@getMerchantHdfcExcel');
    Route::get('/admin/beneficiary/dl', 'AdminController@getBeneficiaryFile');
    Route::get('/admin/merchant/{id}/screenshot', 'AdminController@getMerchantScreenshot');

    Route::get('admin/{mode}/merchants/aggregations/{resource}', 'AdminController@getMerchantAggregations');
    Route::get('admin/{mode}/merchants/{merchant_id}/aggregations/{resource}', 'AdminController@getSingleMerchantAggregations');

    // Might delete this route later if its not used
    Route::get('/admin/merchant/{id}/tags', 'AdminController@getMerchantTags');
    Route::get('/admin/triggererror', 'AdminController@undefinedMethod');

    // Admin Meta Routes
    Route::post('/admin/password', 'AdminController@postPassword');

    // Pricing Plan Routes
    Route::post('/admin/pricing/new', 'AdminController@postNewPricingPlan');
    Route::post('/admin/pricing/{id}', 'AdminController@postPricingRules');
    Route::delete('/admin/pricing/{planId}/rules/{ruleId}', 'AdminController@deletePricingPlanRule');

    // EMI Routes
    Route::delete('/admin/emi/{emiId}', 'AdminController@deleteEMIPlan');
    Route::post('/admin/emi', 'AdminController@postAddEMIPlan');

    // Admin merchant actions
    Route::get('/admin/merchant/{id}/lock', 'AdminController@getLockMerchantDetails');
    Route::get('/admin/merchant/{id}/unlock', 'AdminController@getUnlockMerchantDetails');
    Route::post('/admin/merchant/{id}/edit', 'AdminController@postEditMerchant');
    Route::post('/admin/merchant/{id}/tags', 'AdminController@postTagMerchant');
    Route::post('/admin/merchant/{id}/features', 'AdminController@syncMerchantFeatures');
    Route::post('/admin/merchant/{id}/comment/edit', 'AdminController@postEditMerchantComment');
    Route::post('/admin/merchant/{id}/banks', 'AdminController@postMerchantBanks');
    Route::post('admin/merchant/{id}/addadjustment', 'AdminController@postAddAdjustment');
    Route::get('/admin/merchant/{id}/activate', 'AdminController@getMerchantActivation');
    Route::get('/admin/merchant/{id}/live/enable', 'AdminController@getMerchantLiveEnable');
    Route::get('/admin/merchant/{id}/live/disable', 'AdminController@getMerchantLiveDisable');
    Route::get('/admin/merchant/{id}/archive', 'AdminController@getMerchantArchive');
    Route::get('/admin/merchant/{id}/unarchive', 'AdminController@getMerchantUnarchive');
    Route::post('/admin/merchant/{id}/methods', 'AdminController@postEditMethods');
    Route::put('/admin/merchants/{id}/credits', 'AdminController@editCredits'); // ????????????????????
    Route::post('/admin/merchants/{id}/international', 'AdminController@postSetMerchantInternational');
    Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal');
    Route::post('/admin/merchant/{id}/pricing', 'AdminController@postMerchantPricing');
    Route::get('/admin/companies/{cin}/info', 'AdminController@getCompanyInfo');
    Route::get('/admin/merchant/{id}/credits_log', 'AdminController@getMerchantCreditsLog');
    Route::post('/admin/merchant/{id}/credits/add', 'AdminController@addMerchantCredits');
    Route::delete('/admin/merchant/{id}/credit/{cid}', 'AdminController@deleteMerchantCredit');

    // Creevey Related routes
    Route::put('/admin/merchant/{id}/screenshot', 'AdminController@captureMerchantScreenshot');
    Route::post('/admin/merchant/{id}/screenshot', 'AdminController@saveMerchantScreenshot');

    // IIN Routes
    Route::post('/admin/iin/add', 'AdminController@postAddIIN');
    Route::delete('/admin/iin/{id}', 'AdminController@deleteIIN');
    Route::put('/admin/iin/{id}', 'AdminController@putEditIIN');
    // EMI Plan Routes
    Route::delete('/admin/emi/{id}', 'AdminController@deleteIIN');

    // Admin Payment Actions
    Route::get('/admin/{mode}/payment/{id}/verify', 'AdminController@getVerifyPayment');
    Route::post('/admin/{mode}/payments/{id}/authorize_failed', 'AdminController@postAuthorizeFailedPayment');
    Route::post('/admin/payments/verify', 'AdminController@verifyAllPayments');
    Route::get('/admin/{mode}/payments/{id}/refunds', 'AdminController@getPaymentRefunds');
    // More admin payment actions
    // These use proxy auth so needs merchantId
    Route::post('/admin/{mode}/{merchantId}/payments/{id}/refund_authorized', 'AdminController@postRefundAuthorizedPayment');
    Route::post('/admin/{mode}/{merchantId}/payments/{id}/refund', 'AdminController@postRefund');
    Route::post('/admin/{mode}/{merchantId}/payments/{id}/capture', 'AdminController@postCapture');
    Route::put('/admin/merchants/{id}/confirmed', 'AdminController@postConfirmMerchant');

    // Admin Main Actions, mostly initiated from the Actions screen
    Route::post('/admin/beneficiary', 'AdminController@generateBeneficiaryFile');
    Route::post('/admin/trigger/error', 'AdminController@triggerError');
    Route::post('/admin/{mode}/refunds/netbanking', 'AdminController@generateNetBankingRefunds');
    Route::post('/admin/settlement/initiate/{channel}', 'AdminController@postInitiateSetl');
    // Newsletter
    Route::post('/admin/newsletter/test', 'AdminController@postSendTestNewsletter');
    Route::post('/admin/newsletter/mail', 'AdminController@postSendNewsletter');
    // Terminal Routes
    Route::delete('/admin/{mode}/terminal/{id}', 'AdminController@deleteTerminal');
    Route::put('/admin/{mode}/terminal/{id}', 'AdminController@editTerminal');
    Route::put('/admin/{mode}/terminal/{id}/toggle', 'AdminController@toggleTerminal');

    // Reconcile settlements
    Route::post('/settlements/reconcile', 'AdminController@postReconcileSettlement');

    Route::post('/admin/{mode}/reconciliate', 'AdminController@postReconciliate');

    Route::group(['middleware'  =>  ['admin', 'superadmin']], function()
    {
        // This is the RAW API route which processes api calls
        Route::post('/api/{path?}', 'AdminController@passThrough')
            ->where('path', '.*$');
        Route::post('/admin/users', 'AdminController@postAddAdmin');
        Route::post('/admin/users/{id}/superadmin', 'AdminController@postPromoteAdmin');
        Route::delete('/admin/users/{id}', 'AdminController@getDeleteAdmin');
        Route::put('/admin/merchant/{id}/email', 'AdminController@putEditMerchantEmail');
        Route::put('/admin/merchant/{id}/bank_account', 'AdminController@putEditBankDetails');

        Route::get('/admin/users', 'AdminController@getAdmins');
    });

    Route::get('/admin/{mode}/fetchentity/{entity}', 'AdminController@getMultipleEntities');
    Route::get('/admin/{mode}/fetchentity/{entity}/{format}', 'AdminController@getMultipleEntities')
            ->where('format', 'csv');
    // This is a very generic route and needs to be defined below
    Route::get('/admin/{mode}/fetchentity/{entity}/{entity_id}', 'AdminController@getEntityById');
});

Route::post('/admin/signin', 'AdminController@postSignin');
Route::group(['middleware' => ['auth.internal']], function()
{
    Route::post('/{mode}/transactions/{resource}', 'TransactionController@postIndex');
});

Route::group(['middleware' => ['auth.cron']], function()
{
    Route::post('/{mode}/analytics/aggregations/day', 'AdminController@updateDayAggregations');
    Route::post('/{mode}/analytics/aggregations/{type}', 'TransactionController@updateTypeAggregations');
});
