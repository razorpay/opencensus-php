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

Route::get('/status', 'AdminController@getStatus')->name('status');

Route::get('/ext/{all?}', 'UserController@getBrowserExtensionIndex')->name('extension_catchall')->where(['all' => '.*']);

Route::group(['middleware' => ['jwt_session']], function() {
    Route::any('/extension/api/{mode}/{path?}', 'GenericController@handleAnyExtension')
            ->where(['path' => '.*'])
            ->name('extension_merchant')
            ->middleware(['jwt']);

    Route::get('/extension/jwt/validate', 'UserController@validateJWT')
            ->name('extension_validate_jwt')
            ->middleware(['jwt']);
});

// Everything in this group is a unauthenticated route
// Please take care to not return any sensitive information
// here
Route::group(['middleware' => ['web']], function () {

    // adding options route to the routing layer. could have been at server level but because of some logic we are
    // keeping it in app layer.
    Route::options('/{path?}', 'GenericController@handleAny')
        ->where(['path' => '.*']);


    Route::group(['middleware'  =>  ['set_csp_header']], function () {
        Route::get('/', 'UserController@getIndex')->name('dashboard');
        Route::get('/signup', 'UserController@getIndex')->name('signup');
        Route::get('/signin', 'UserController@getIndex')->name('signin');
        Route::get('/app/{path?}', 'UserController@getIndex')->name('dashboard_app')
            ->where(['path' => '.*']);

        Route::get('/tnc/{id}', 'UserController@getTnc')->name('tnc');
    });

    // User (guest auth route)
    Route::any('/user/api/{mode}/{path?}', 'GenericController@handleAny')
        ->where(['path' => '.*'])
        ->name('user');

    Route::post('/extension/user/logout', 'UserController@getExtensionLogout')
        ->name('extension_user_logout')
        ->middleware(['jwt']);

    // Org
    Route::get('/org', 'AdminController@getOrg')->name('get_org');

    // Growth Public Assets
    Route::post('/v1/growth/assets', 'GenericController@getPublicGrowthAssets')->name('growth_public_assets');

    Route::group(['prefix' => 'admin', 'middleware'  =>  ['set_x_frame']], function () {
        Route::post('/signin', 'AdminController@postSignin')->name('admin_signin');
        Route::get('/', 'AdminController@getIndex')->name('admin_getIndex');
    });

    Route::group(['prefix' => 'user'], function()
    {
        Route::post('/signin', 'UserController@postSignin')->name('user_signin'); // ePOS
        Route::post('/demo-signin', 'UserController@postDemoSignin');
        // allow users with verified email/mobile to login with otp
        Route::post('/signin/otp', 'UserController@postSendLoginOtp')->name('user_signin_otp');
        Route::post('/signin/otp/verify', 'UserController@postVerifyLoginOtp')->name('user_signin_otp_verify');
        // users need to enter their password after otp login if 2fa is enabled/enforced
        Route::post('/signin/otp/2fa', 'UserController@postOtpLogin2faPassword')->name('user_signin_otp_2fa');
        // allow users with unverified email/mobile to login with password and then verify email/mobile
        Route::post('/signin/verify-user/otp', 'UserController@postSendVerifyUserOtp')->name('user_verify_user_otp');
        Route::post('/signin/verify-user/otp/verify', 'UserController@postVerifyUserOtp')->name('user_verify_user_otp_verify');

        Route::post('/register', 'UserController@postRegister')->name('user_register'); // ePOS
        Route::post('/register/otp', 'UserController@postRegisterSendOtp')->name('user_register_otp'); // ePOS
        Route::post('/register/otp/verify', 'UserController@postRegisterVerifyOtp')->name('user_register_otp_verify'); // ePOS
        Route::post('/oauth-signin', 'UserController@postOauthSignIn')->name('user_oauth_signin');
        Route::post('/oauth-register', 'UserController@postOauthRegister')->name('user_oauth_register');
        Route::post('/2fa_setup/verify-mobile', 'UserController@postSetup2faVerifyMobile')->name('user_2fa_setup_verify_mobile');
        Route::post('/2fa/otp-verify', 'UserController@postSetup2faVerifyOtp')->name('user_2fa_otp_verify');
        Route::post('/2fa', 'userController@post2faOtp')->name('user_2fa');
        Route::patch('/2fa/contact', 'UserController@postUpdate2faContact')->name('user_2fa_contact');
        Route::post('/2fa/otp-resend', 'UserController@postResendOtp')->name('user_2fa_otp_resned');
        Route::get('/session', 'UserController@getSessionData')->middleware(['auth:user'])->name('user_session');
        Route::get('/identifier/{client_id}', 'UserController@getIdentityToken')->middleware(['auth:user'])->name('user_identity');
    });

    Route::group(['middleware' => 'auth:user', 'prefix' => 'user'], function()
    {
        Route::post('/pre_signup', 'MerchantController@postSignup')->name('user_pre_signup');
        Route::post('/verify_email', 'UserController@verifyEmailOtp')->name('user_verify_email');
        Route::post('/resend_email_otp', 'UserController@resendEmailOtp')->name('user_resend_email_otp');
        Route::post('/resend', 'MerchantController@postResendConfirmation')->name('user_resend_confirmation');
        Route::get('/keepalive', 'UserController@getKeepAlive')->name('user_keep_alive');
        Route::post('/logout', 'UserController@getLogout')->name('user_logout');

        // This returns all the needed information
        Route::get('/', 'UserController@getUserDetailsV2')->name('user_details'); //ePOS
        Route::get('/mobile', 'UserController@getUserDetailsForMobile')->name('user_mobile_details');
        Route::get('/details', 'UserController@getUserDetailsV2')->name('get_user_details');

        Route::post('/coupons/validate', 'MerchantController@validateCoupon')->name('user_coupons_validate');
        Route::post('/whatsapp/opt_in', 'MerchantController@whatsappOptIn')->name('user_whatsapp/opt_in');
    });

    Route::group(['middleware' => 'auth:user'] , function()
    {
        Route::get('/merchant/experiments', 'MerchantController@getMerchantExperiments');
        Route::get('/merchant/features', 'MerchantController@getMerchantFeatures');
        Route::get('/merchant/splitzexperiments', 'MerchantController@getSplitzExperiments');
        Route::get('/merchant/details', 'MerchantController@getMerchantDetails')->name('merchant_details');
    });

    Route::group(['middleware'  =>  ['auth:user', 'verified']], function()
    {
        Route::any('/merchant/api/{mode}/{path}', 'GenericController@handleAny')
            ->where(['path' => '.*'])
            ->name('merchant');

        Route::post('/extension/generate_token', 'UserController@generateJWT')->name('extension_generate_token');

        Route::put('/{mode}/users/{id}/detach', 'MerchantController@removeUser')->name('remove_user');

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

        Route::post('/keys/csv', 'MerchantController@getCsv');
        Route::get('/{mode}/reports/broking', 'TransactionController@getTransactionBrokingReport')->name('reports_broking');
        Route::get('/{mode}/reports/invoice', 'TransactionController@getInvoiceReport')->name('reports_invoice');
        Route::get('/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('reports_entity');
        // This is a sensitive route
        Route::get('settings/merchants/switch/{id}', 'UserController@switchCurrentMerchant');
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
        // Send Feedback Mail to support@razorpay.com
        Route::post('/sendfeedback', 'MerchantController@sendFeedback')->name('send_feedback');

        Route::get('/reports/{log_id}', 'MerchantController@downloadReport')->name('report_download');

        Route::get('ufh/file/{file_id}', 'MerchantController@downloadFileFromUFH');

        Route::post('/user/otp/verify', 'UserController@verifyUserViaOtp');
        Route::post('/user/verify_contact', 'UserController@verifyContact');
    });

    Route::group(['middleware'  =>  ['admin', 'admin_access', 'set_x_frame']], function()
    {
        Route::any('/admin/stats/{id}', 'AdminController@getMerchantStats')->name('admin_merchant_stats');
        Route::get('/admin/user', 'AdminController@getAdmin');
        Route::post('/admin/user/logout', 'AdminController@getLogout');
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
        Route::get('/admin/merchant/{id}/activate', 'AdminController@getMerchantActivation');
        Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal');
        Route::put('/admin/merchant/{id}/action', 'AdminController@putAction');
        Route::get('/admin/companies/{cin}/info', 'AdminController@getCompanyInfo');

        // Creevey Related routes
        Route::put('/admin/merchant/{id}/screenshot', 'AdminController@captureMerchantScreenshot');
        Route::post('/admin/merchant/{id}/screenshot', 'AdminController@saveMerchantScreenshot');

        Route::post('/admin/{mode}/reconciliate', 'AdminController@postReconciliate');

        Route::post('/makeapicall/{path?}', 'AdminController@passThrough')->where('path', '.*$');

        Route::put('/admin/merchant/{id}/email', 'AdminController@putEditMerchantEmail');
        Route::get('/admin/{mode}/fetchentity/{entity}/{format}', 'AdminController@getMultipleEntities')
                ->where('format', 'csv')
                ->name('admin_fetch_entity');

        // Upload logos for orgs
        Route::post('/admin/org/{org_id}', 'AdminController@postUploadOrgLogo');
        Route::post('/admin/org/{org_id}/bg_img', 'AdminController@postUploadOrgBackgroundImage');
        Route::get('/admin/emaillogs', 'AdminController@getEmailLogs')->name('email_logs_get');

        Route::get('/admin/{mode}/reports/broking', 'TransactionController@getTransactionBrokingReport')->name('reports_broking');
        Route::get('/admin/{mode}/reports/invoice/{merchant_id}', 'TransactionController@getInvoiceReport')->name('reports_invoice');
        Route::get('/admin/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('reports_entity');

        Route::get('/admin/admin_reports/{log_id}', 'MerchantController@downloadReport');

        Route::any('/admin/api/{mode}/{path}', 'GenericController@handleAny')
            ->where(['path' => '.*'])
            ->name('admin');
        Route::get('/admin/checkout-builder', 'AdminController@getCheckoutBuilder');
    });

    Route::get('razorx/{all?}', 'AdminController@getIndex')->name('razorx_catchall')->where(['all' => '.*']);
    Route::get('admin/{all}', 'AdminController@getIndex')->name('admin_catchall')->where(['all' => '.*']);
});

Route::group(['middleware'  => 'graph'], function()
{
    Route::post('/graph', 'GraphController@handleRequestForGraph')
        ->name('graph_request');
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
