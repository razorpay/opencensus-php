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

Route::group(['middleware' => ['web']], function () {
    Route::get('/', 'UserController@getIndex')->name('dashboard');
    Route::get('/admin', 'AdminController@getIndex');

    // This is for enabling CORS support on contact form submissions
    Route::options('/contact', 'MerchantController@optionsContact');
    Route::post('/contact', 'MerchantController@postContact');

    Route::get('/invitation', 'MerchantController@getInvitationDetails');

    // Org
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/auth', 'AdminController@initiateAuth');

        Route::get('/org', 'AdminController@getOrg');
        Route::get('/google_oauth_url', 'AdminController@getGoogleOAuthUrl');
        Route::post('/signin', 'AdminController@postSignin');

        Route::post('/password/reset', 'PasswordController@forgotAdminPassword');
        Route::post('/password/reset/{token}', 'PasswordController@resetAdminPassword');
    });

    Route::group([], function()
    {
        Route::get('/user/confirm/{token}', 'UserController@getConfirm');
        Route::group([], function()
        {
            Route::post('/user/signin', 'UserController@postSignin'); // ePOS
            Route::post('/user/register', 'UserController@postRegister'); // ePOS
            Route::post('/user/resend', 'MerchantController@postResendConfirmation');
            Route::post('/user/password/reset', 'PasswordController@postRemind');
            Route::post('/user/password/reset/{token}', 'PasswordController@postReset');
            Route::post('/user/pre_signup', 'MerchantController@postSignup');
            Route::get('/user/pre_signup', 'MerchantController@getSignup');
            Route::post('/user/track_lead', 'UserController@trackLead');
        });
    });

    Route::group(['middleware'  =>  'auth:user'], function()
    {
        Route::any('/user/generic', 'GenericController@handle');

        Route::get('/user/keepalive', 'UserController@getKeepAlive');
        Route::get('/user/logout', 'UserController@getLogout');

        // This returns all the needed information
        Route::get('/user', 'UserController@getUserDetails');
        Route::get('/user/details', 'UserController@getUserDetails');
        Route::get('/activation/details', 'MerchantController@getActivationDetails')->name('get_activation_details');
        Route::get('/activation/details/{merchantId}', 'MerchantController@getActivationDetails')->name('get_activation_details');

        // Batch Refund Routes
        Route::group(['prefix' => '{mode}/batches'], function () {
            Route::get('/', 'MerchantController@fetchMultipleBatches')->name('batch_fetch_multiple');
            Route::get('{id}', 'MerchantController@fetchBatchById')->name('batch_fetch_single');
            Route::get('{id}/download', 'MerchantController@downloadBatchFile')->name('batch_download');

            Route::post('/', 'MerchantController@uploadBatchFile')->name('batch_upload');
            Route::post('{id}/retry', 'MerchantController@retryBatchFile')->name('batch_retry');
        });

        // Account Routes
        Route::get('/{mode}/accounts', 'MerchantController@getAccounts')->name('get_accounts');

        // Support role does not have access to this

        Route::get('/{mode}/transactions', 'TransactionController@getTransactions');
        Route::get('/{mode}/transactions/{id}', 'TransactionController@getTransaction');

        Route::get('/{mode}/analytics/transactions', 'TransactionController@getAnalytics');
        Route::get('/{mode}/analytics/aggregations', 'TransactionController@getAggregations');
        Route::get('/{mode}/analytics/payment/aggregations', 'TransactionController@getPaymentAggregations');

        // adding keys as being used in android
        // ePOS => the routes which are being used by android ePOS app
        Route::get('/{mode}/keys', 'MerchantController@getKeys')->name('get_keys'); // ePOS
        Route::post('/{mode}/key/new', 'MerchantController@postNewKey')->name('keys_setup'); // ePOS

        Route::get('/keys/csv', 'MerchantController@getCsv');
        Route::get('/apihost', 'MerchantController@getApihost');

        Route::get('/referrals', 'MerchantController@getReferredMerchants')->name('referred_merchants_list');

        // This also returns credits
        Route::get('/bank_account', 'MerchantController@getBankAccount')->name('bank_account_fetch');

        // Invitation and Team Support
        Route::get('settings/merchants/owned', 'MerchantController@getUsersListWithInvites')->name('team_users_list');

        // Shown in profile page
        Route::get('settings/invitations', 'InvitationsController@getPendingInvitationsForUser');

        Route::get('/{mode}/reports/broking', 'TransactionController@getTransactionBrokingReport')->name('reports_broking');
        Route::get('/{mode}/reports/invoice', 'TransactionController@getInvoiceReport')->name('reports_invoice');
        Route::get('/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('reports_entity');

        // This is a sensitive route
        Route::get('settings/merchants/switch/{id}', 'UserController@switchCurrentMerchant');

        // Team Administration
        // TODO: Convert this to POST
        Route::get('settings/invitations/{invite}/resend', 'InvitationsController@getResendMerchantInvitation')->name('invitation_resend');
        Route::put('settings/merchants/owned/members/{id}', 'MerchantController@updateTeamMember', 'team_users_update');
        Route::delete('settings/merchants/owned/members/{id}', 'MerchantController@removeTeamMember', 'team_users_delete');

        // Invite Administration (Owners)
        Route::post('settings/invitations', 'InvitationsController@postSendMerchantInvitation')->name('invitations_send');
        Route::put('settings/invitations/{invite}', 'InvitationsController@updateMerchantInvitation')->name('invitations_edit');
        Route::delete('settings/invitations/{invite}', 'InvitationsController@deleteMerchantInvitationForUser')->name('invitations_delete');

        // Invitation related (User side)
        Route::post('settings/invitations/{invite}/accept', 'InvitationsController@postAcceptMerchantInvitation');
        Route::delete('settings/invitations/{invite}/reject', 'InvitationsController@deleteRejectMerchantInvitation');

        Route::post('/password', 'UserController@postPassword');
        Route::post('/activation', 'MerchantController@postActivation')->name('post_activation'); // ePOS
        Route::post('/activation/{merchantId}', 'MerchantController@postActivation')->name('post_activation');
        Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep')->name('post_activation_save_step'); // ePOS
        Route::post('/activation/save/step/{id}/{merchantId}', 'MerchantController@postSaveActivationStep')->name('post_activation_save_step');
        Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile')->name('post_activation_save_file'); // ePOS
        Route::post('/activation/save/file/{merchantId}', 'MerchantController@postSaveActivationFile')->name('post_activation_save_file');
        Route::post('/{mode}/addfunds', 'TransactionController@postAddfunds');
        Route::get('/{mode}/invoices', 'MerchantController@getInvoices')->name('invoice_fetch_all'); // ePOS
        Route::post('/{mode}/invoices', 'MerchantController@postCreateInvoice')->name('invoice_create'); // ePOS
        Route::post('/{mode}/invoices/{invoiceId}/notify/{medium}', 'MerchantController@sendInvoiceNotification')->name('invoices_send_notification');

        Route::get('/{mode}/customers/autocomplete', 'MerchantController@getCustomersForAutocomplete')->name('customer_autocomplete');

        Route::get('/{mode}/items/autocomplete', 'MerchantController@getItemsForAutocomplete')->name('item_autocomplete');

        // Upgrades a standard invited user to a merchant
        Route::post('/merchants/register', 'UserController@postUpgradeUserToMerchant');

        // Registers a sub-merchant account
        Route::post('/submerchants', 'MerchantController@postRegisterSubmerchant')->name('submerchant_register');
        Route::post('/subusers', 'MerchantController@postRegisterSubUser')->name('subuser_register');

    });

    Route::group(['middleware'  =>  ['admin', 'admin_access']], function()
    {
        Route::any('/admin/generic', 'GenericController@handle');

        Route::get('/admin/user', 'AdminController@getAdmin');
        Route::get('/admin/user/logout', 'AdminController@getLogout');
        Route::get('/admin/user/keepalive', 'AdminController@getKeepAlive');
        Route::get('/admin/merchant/list', 'AdminController@getMerchantList');
        Route::get('/admin/merchant/{id}', 'AdminController@getMerchant');
        Route::get('/admin/merchant/{id}/details', 'AdminController@getMerchantDetails');

        Route::post('/admin/features/{entityType}/{entityId}', 'AdminController@addEntityFeatures');
        Route::delete('/admin/features/{entityId}/{featureName}', 'AdminController@deleteEntityFeature')
                ->name('admin_delete_features');

        Route::get('/admin/merchant/{id}/login', 'AdminController@getMerchantLogin')
               ->name('admin_merchant_login');
        Route::get('/admin/activity', 'AdminController@getAdminActivity');
        Route::delete('/admin/activity', 'AdminController@deleteOtherAdminActivity');
        Route::delete('/admin/activity/{id}', 'AdminController@deleteAdminActivity');

        Route::get('/admin/merchant/{id}/hdfc_excel', 'AdminController@getMerchantHdfcExcel');
        Route::get('/admin/file/{fileId}', 'AdminController@getUploadedFile');
        Route::get('/admin/merchant/{id}/screenshot', 'AdminController@getMerchantScreenshot');

        Route::get('admin/{mode}/merchants/aggregations/{resource}', 'AdminController@getMerchantAggregations');
        Route::get('admin/{mode}/merchants/{merchant_id}/aggregations/{resource}', 'AdminController@getSingleMerchantAggregations');

        // Might delete this route later if its not used
        Route::get('/admin/merchant/{id}/tags', 'AdminController@getMerchantTags');
        Route::get('/admin/triggererror', 'AdminController@undefinedMethod');

        // Admin Meta Routes
        Route::post('/admin/password', 'AdminController@postPassword');
        Route::put('/admin/{id}/edit', 'AdminController@putEdit');

        // EMI Routes
        Route::delete('/admin/emi/{emiId}', 'AdminController@deleteEMIPlan');

        // Admin merchant actions
        Route::post('/admin/merchant/{id}/edit', 'AdminController@postEditMerchant');
        Route::post('/admin/merchant/{id}/tags', 'AdminController@postTagMerchant');
        Route::post('/admin/merchant/{id}/comment/edit', 'AdminController@postEditMerchantComment');
        Route::post('admin/merchant/{id}/addadjustment', 'AdminController@postAddAdjustment');
        Route::get('/admin/merchant/{id}/activate', 'AdminController@getMerchantActivation');
        Route::get('/admin/merchant/{id}/live/enable', 'AdminController@getMerchantLiveEnable');
        Route::get('/admin/merchant/{id}/live/disable', 'AdminController@getMerchantLiveDisable');
        Route::get('/admin/merchant/{id}/archive', 'AdminController@getMerchantArchive');
        Route::get('/admin/merchant/{id}/unarchive', 'AdminController@getMerchantUnarchive');
        Route::get('/admin/merchant/{id}/suspend', 'AdminController@getMerchantSuspend');
        Route::get('/admin/merchant/{id}/unsuspend', 'AdminController@getMerchantUnsuspend');
        Route::put('/admin/merchants/{id}/credits', 'AdminController@editCredits');
        Route::post('/admin/merchants/{id}/international', 'AdminController@postSetMerchantInternational');
        Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal');
        Route::get('/admin/companies/{cin}/info', 'AdminController@getCompanyInfo');

        // Creevey Related routes
        Route::put('/admin/merchant/{id}/screenshot', 'AdminController@captureMerchantScreenshot');
        Route::post('/admin/merchant/{id}/screenshot', 'AdminController@saveMerchantScreenshot');

        // IIN Routes
        Route::delete('/admin/iin/{id}', 'AdminController@deleteIIN');
        Route::put('/admin/iin/{id}', 'AdminController@putEditIIN');
        // EMI Plan Routes
        Route::delete('/admin/emi/{id}', 'AdminController@deleteIIN');

        // Admin Payment Actions
        Route::get('/admin/{mode}/payments/{id}/analytics', 'AdminController@getPaymentAnalytics');
        Route::get('/admin/{mode}/payments/{id}/refunds', 'AdminController@getPaymentRefunds');

        // More admin payment actions
        // These use proxy auth so needs merchantId
        Route::post('/admin/{mode}/{merchantId}/payments/{id}/refund_authorized', 'AdminController@postRefundAuthorizedPayment');
        Route::post('/admin/{mode}/{merchantId}/payments/{id}/refund', 'AdminController@postRefund');
        Route::post('/admin/{mode}/{merchantId}/payments/{id}/capture', 'AdminController@postCapture')
                ->name('admin_payment_capture');
        Route::post('/admin/users/confirm', 'AdminController@postConfirmUser');

        // Newsletter
        Route::post('/admin/newsletter/test', 'AdminController@postSendTestNewsletter');
        Route::post('/admin/newsletter/mail', 'AdminController@postSendNewsletter');
        // Terminal Routes
        Route::delete('/admin/{mode}/terminal/{id}', 'AdminController@deleteTerminal');
        Route::put('/admin/{mode}/terminal/{id}', 'AdminController@editTerminal');
        Route::put('/admin/{mode}/terminal/{id}/toggle', 'AdminController@toggleTerminal');

        Route::put('/admin/{mode}/terminal/{id}/merchant/{mid}', 'AdminController@assignSubMerchantToTerminal');
        Route::delete('/admin/{mode}/terminal/{id}/merchant/{mid}', 'AdminController@unassignSubMerchantToTerminal');
        Route::put('/admin/{mode}/terminal/{id}/reassign', 'AdminController@changePrimaryMerchant');

        // Reconcile settlements
        Route::post('/settlements/reconcile', 'AdminController@postReconcileSettlement');

        Route::post('/admin/{mode}/reconciliate', 'AdminController@postReconciliate');

        Route::get('admin/schedule/list', 'AdminController@getScheduleList');
        Route::post('admin/merchant/{id}/schedules', 'AdminController@postMerchantSchedule');

        Route::group(['middleware'  =>  ['admin', 'superadmin', 'admin_access']], function()
        {
            // This is the RAW API route which processes api calls
            Route::post('/api/{path?}', 'AdminController@passThrough')
                ->where('path', '.*$');
            Route::post('/admin/users', 'AdminController@postAddAdmin');
            Route::post('/admin/users/{id}/superadmin', 'AdminController@postPromoteAdmin');
            Route::delete('/admin/users/{id}', 'AdminController@getDeleteAdmin');

            Route::get('/admin/users', 'AdminController@getAdmins');
        });

        Route::put('/admin/merchant/{id}/email', 'AdminController@putEditMerchantEmail');
        Route::put('/admin/merchant/{id}/bank_account', 'AdminController@putEditBankDetails');

        Route::get('/admin/{mode}/fetchentity/{entity}', 'AdminController@getMultipleEntities')
                ->name('admin_fetch_entity');
        Route::get('/admin/{mode}/fetchentity/{entity}/{format}', 'AdminController@getMultipleEntities')
                ->where('format', 'csv')
                ->name('admin_fetch_entity');
        // This is a very generic route and needs to be defined below
        Route::get('/admin/{mode}/fetchentity/{entity}/{entity_id}', 'AdminController@getEntityById')
                ->name('admin_fetch_entity');

        // Upload logos for orgs
        Route::post('/admin/org/{org_id}', 'AdminController@postUploadOrgLogo');

        Route::get('/admin/auditlogs', 'AdminController@getAuditLogs');
        Route::get('admin/get_current');

        Route::get('/admin/emaillogs', 'AdminController@getEmailLogs')->name('email_logs_get');
        Route::get('/admin/emailbounces/{email}', 'AdminController@getEmailBounce')->name('email_bounce_get');
        Route::delete('/admin/emailbounces/{email}', 'AdminController@deleteEmailBounce')->name('email_bounce_delete');
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
