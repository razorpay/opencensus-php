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

// Everything in this group is a unauthenticated route
// Please take care to not return any sensitive information
// here
Route::group(['middleware' => ['web']], function () {
    Route::get('/', 'UserController@getIndex')->name('dashboard');
    Route::get('/admin', 'AdminController@getIndex');
    Route::get('/status', 'AdminController@getStatus');

    // This is for enabling CORS support on contact form submissions
    Route::options('/contact', 'MerchantController@optionsContact');
    Route::post('/contact', 'MerchantController@postContact');

    // Org
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/auth', 'AdminController@initiateAuth');
        Route::get('/org', 'AdminController@getOrg');
        Route::post('/signin', 'AdminController@postSignin');
    });

    Route::group(['prefix' => 'user'], function()
    {
        Route::post('/signin', 'UserController@postSignin'); // ePOS
        Route::post('/register', 'UserController@postRegister'); // ePOS
        Route::post('/resend', 'MerchantController@postResendConfirmation');
        Route::post('/password/reset', 'PasswordController@postRemind');
        Route::post('/password/reset/{token}', 'PasswordController@postReset');
        Route::get('/invitations/token/{token}', 'InvitationsController@fetchByToken');

        // Adding the following here since auth:user middleware should be after cors
        Route::options('/session', 'UserController@getSessionData')->middleware(['cors', 'auth:user']);
        Route::get('/session', 'UserController@getSessionData')->middleware(['cors', 'auth:user']);
    });

    Route::group(['middleware' => 'auth:user', 'prefix' => 'user'], function()
    {
        Route::post('/pre_signup', 'MerchantController@postSignup');
        Route::get('/keepalive', 'UserController@getKeepAlive');
        Route::get('/logout', 'UserController@getLogout');
        // This returns all the needed information
        Route::get('/', 'UserController@getUserDetailsV2'); //ePOS
        Route::get('/details', 'UserController@getUserDetailsV2');
    });

    // Generic guest route with no authentication
    Route::group(['middleware' => ['guest.generic']], function()
    {
        Route::any('/guest/generic', 'GenericController@handle');
    });

    Route::group(['middleware'  =>  ['auth:user', 'verified']], function()
    {
        Route::any('/user/generic', 'GenericController@handle');
        // Account Routes
        Route::get('/{mode}/accounts', 'MerchantController@getAccounts')->name('get_accounts');

        Route::get('/{mode}/analytics/transactions', 'TransactionController@getAnalytics');
        Route::get('/{mode}/analytics/aggregations', 'TransactionController@getAggregations');
        Route::get('/{mode}/analytics/payment/aggregations', 'TransactionController@getPaymentAggregations');
        // ePOS => the routes which are being used by android ePOS app
        // Routes only used by ePOS
        Route::get('/{mode}/keys', 'MerchantController@getKeys')->name('get_keys'); // ePOS
        Route::post('/{mode}/key/new', 'MerchantController@postNewKey')->name('keys_setup'); // ePOS
        Route::post('/activation', 'MerchantController@postActivation')->name('post_activation'); // ePOS
        Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep')->name('post_activation_save_step'); // ePOS
        Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile')->name('post_activation_save_file'); // ePOS
        Route::get('/{mode}/invoices', 'MerchantController@getInvoices')->name('invoice_fetch_all'); // ePOS
        Route::post('/{mode}/invoices', 'MerchantController@postCreateInvoice')->name('invoice_create'); // ePOS

        Route::get('/keys/csv', 'MerchantController@getCsv');
        Route::get('/apihost', 'MerchantController@getApihost');
        Route::get('/{mode}/reports/broking', 'TransactionController@getTransactionBrokingReport')->name('reports_broking');
        Route::get('/{mode}/reports/invoice', 'TransactionController@getInvoiceReport')->name('reports_invoice');
        Route::get('/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('reports_entity');
        // This is a sensitive route
        Route::get('settings/merchants/switch/{id}', 'UserController@switchCurrentMerchant');
        // Team Administration
        Route::put('settings/merchants/owned/members/{id}', 'MerchantController@updateTeamMember', 'team_users_update');
        Route::delete('settings/merchants/owned/members/{id}', 'MerchantController@removeTeamMember', 'team_users_delete');
        // Invitation related (User side)
        Route::post('settings/invitations/{invite}/accept', 'InvitationsController@postAcceptMerchantInvitation');

        // Update password
        Route::post('/password', 'UserController@postPassword');
        Route::post('/{mode}/addfunds', 'TransactionController@postAddfunds');
        Route::post('/{mode}/invoices/{invoiceId}/notify/{medium}', 'MerchantController@sendInvoiceNotification')->name('invoices_send_notification');
        Route::get('/{mode}/customers/autocomplete', 'MerchantController@getCustomersForAutocomplete')->name('customer_autocomplete');
        Route::get('/{mode}/items/autocomplete', 'MerchantController@getItemsForAutocomplete')->name('item_autocomplete');

        // Upgrades a standard invited user to a merchant
        Route::post('/merchants/register', 'UserController@postUpgradeUserToMerchant');
        // Registers a sub-merchant account
        Route::post('/submerchants', 'MerchantController@postRegisterSubMerchant')->name('submerchant_register');
        Route::post('/subusers', 'MerchantController@postRegisterSubUser')->name('subuser_register');
        // Send Feedback Mail to support@razorpay.com
        Route::post('/sendfeedback', 'MerchantController@sendFeedback')->name('send_feedback');
    });

    Route::group(['middleware'  =>  ['admin', 'admin_access']], function()
    {
        Route::any('/admin/generic', 'GenericController@handle');
        Route::get('/admin/user', 'AdminController@getAdmin');
        Route::get('/admin/user/logout', 'AdminController@getLogout');
        Route::get('/admin/user/keepalive', 'AdminController@getKeepAlive');

        Route::post('/admin/features/{entityType}/{entityId}', 'AdminController@addEntityFeatures');

        Route::get('/admin/merchant/{id}/login', 'AdminController@getMerchantLogin')
               ->name('admin_merchant_login');
        Route::get('/admin/activity', 'AdminController@getAdminActivity');
        Route::delete('/admin/activity', 'AdminController@deleteOtherAdminActivity');
        Route::delete('/admin/activity/{id}', 'AdminController@deleteAdminActivity');
        Route::get('/admin/merchant/{id}/hdfc_excel', 'AdminController@getMerchantHdfcExcel');
        Route::get('/admin/merchant/{id}/screenshot', 'AdminController@getMerchantScreenshot');
        Route::get('admin/{mode}/merchants/aggregations', 'AdminController@getMerchantAggregations');
        Route::get('admin/{mode}/merchants/{merchant_id}/aggregations', 'AdminController@getSingleMerchantAggregations');

        // Admin merchant actions
        Route::post('/admin/merchant/{id}/edit', 'AdminController@postEditMerchant');
        Route::post('/admin/merchant/{id}/tags', 'AdminController@postTagMerchant');
        Route::get('/admin/merchant/{id}/activate', 'AdminController@getMerchantActivation');
        Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal');
        Route::get('/admin/companies/{cin}/info', 'AdminController@getCompanyInfo');

        // Creevey Related routes
        Route::put('/admin/merchant/{id}/screenshot', 'AdminController@captureMerchantScreenshot');
        Route::post('/admin/merchant/{id}/screenshot', 'AdminController@saveMerchantScreenshot');

        Route::post('/admin/users/confirm', 'AdminController@postConfirmUser');
        // Reconcile settlements
        Route::post('/settlements/reconcile', 'AdminController@postReconcileSettlement');
        Route::post('/admin/{mode}/reconciliate', 'AdminController@postReconciliate');

        Route::group(['middleware'  =>  ['superadmin']], function()
        {
            // This is the RAW API route which processes api calls
            Route::post('/api/{path?}', 'AdminController@passThrough')
                ->where('path', '.*$');
        });

        Route::put('/admin/merchant/{id}/email', 'AdminController@putEditMerchantEmail');
        Route::get('/admin/{mode}/fetchentity/{entity}/{format}', 'AdminController@getMultipleEntities')
                ->where('format', 'csv')
                ->name('admin_fetch_entity');

        // Upload logos for orgs
        Route::post('/admin/org/{org_id}', 'AdminController@postUploadOrgLogo');
        Route::get('/admin/emaillogs', 'AdminController@getEmailLogs')->name('email_logs_get');

        Route::get('/admin/{mode}/reports/broking', 'TransactionController@getTransactionBrokingReport')->name('reports_broking');
        Route::get('/admin/{mode}/reports/invoice', 'TransactionController@getInvoiceReport')->name('reports_invoice');
        Route::get('/admin/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('reports_entity');
    });
});

Route::group(['middleware'  =>  'slack'], function ()
{
    Route::post('/slack', 'AdminController@postSlackQuery');
});

Route::group(['middleware' => ['auth.internal']], function()
{
    Route::post('/{mode}/transactions/{resource}', 'TransactionController@postIndex');
});

Route::group(['middleware' => ['auth.cron']], function()
{
    Route::post('/{mode}/analytics/aggregations/day', 'AdminController@updateDayAggregations');
    Route::post('/{mode}/analytics/aggregations/{type}', 'TransactionController@updateTypeAggregations');
});

Route::group(['middleware' => ['auth.oauth']], function()
{
    Route::get('/user/token/{token}/details', 'UserController@getDetailsFromToken');
});
