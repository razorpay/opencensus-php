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
        ->where(['path' => '.*'])
        ->name('options_path');

    Route::group(['middleware'  =>  ['set_csp_header']], function () {
        Route::get('/', 'UserController@getIndex')->name('dashboard');
        Route::get('/signup', 'UserController@getIndex')->name('signup');
        Route::get('/signin', 'UserController@getIndex')->name('signin');
        Route::get('/resetpassword', 'UserController@getIndex')->name('resetpassword');
        Route::get('/emailupdate', 'UserController@getIndex')->name('emailupdate');
        Route::get('/app/{path?}', 'UserController@getIndex')->name('dashboard_app')
            ->where(['path' => '.*']);

        Route::get('/tnc/{id}', 'UserController@getTnc')->name('tnc');
        Route::get('/dummyiframe', 'UserController@getDummyIFrameForEasyDashboard')->name('easydashboard');
    });

    // User (guest auth route)
    Route::any('/user/api/{mode}/{path}', 'GenericController@handleAny')
        ->where(['path' => '.*'])
        ->name('user');

    Route::post('/extension/user/logout', 'UserController@getExtensionLogout')
        ->name('extension_user_logout')
        ->middleware(['jwt']);

    // Org
    Route::get('/org', 'AdminController@getOrg')->name('get_org');

    Route::group(['middleware'  => ['auth.graph']], function()
    {
        Route::get('/org-by-domain', 'AdminController@getOrgByDomainName')->name('get_org_by_domain');

    });

    // Growth Public Assets
    Route::post('/v1/growth/assets', 'GenericController@getPublicGrowthAssets')->name('growth_public_assets');

    // Get partner config based on given partner id
    Route::get('/partner_config', 'UserController@getPartnerConfig')->name('partner_config_fetch');


    Route::group(['prefix' => 'admin', 'middleware'  =>  ['set_x_frame']], function () {
        Route::post('/signin', 'AdminController@postSignin')->name('admin_signin');
        Route::post('/2fa/otp-verify', 'AdminController@postVerify2faAuthOtp')->name('admin_2FA_verify');
        Route::post('/2fa/otp-resend', 'AdminController@postResendOtp')->name('admin_2FA_resend');
        Route::get('/', 'AdminController@getIndex')->name('admin_getIndex');
        //admin verify 2fa page
        Route::get('/enter-2fa','AdminController@show2FALayout')
            ->name('enter-2fa')
            ->middleware('set_x_frame');
        //admin account block page
        Route::get('/account_block','AdminController@showAccountBlocked')
            ->name('account_block')
            ->middleware('set_x_frame');
        Route::get('/reset-password','AdminController@resetPassword')
            ->name('reset-password')
            ->middleware('set_x_frame');
        Route::get('/forgot-password','AdminController@forgotPassword')
            ->name('forgot-password')
            ->middleware('set_x_frame');
        Route::post('/forgot_password', 'AdminController@postForgotPassword')->name('forgot_password');
        Route::post('/reset_password', 'AdminController@postResetPassword')->name('reset_password');
    });

    Route::group(['prefix' => 'user'], function()
    {
        Route::post('/signin', 'UserController@postSignin')->name('user_signin'); // ePOS
        Route::post('/demo-signin', 'UserController@postDemoSignin')->name('user_demo_signin');
        // allow users with verified email/mobile to login with otp
        Route::post('/signin/otp', 'UserController@postSendLoginOtp')->name('user_signin_otp');
        Route::post('/signin/otp/verify', 'UserController@postVerifyLoginOtp')->name('user_signin_otp_verify');
        // users need to enter their password after otp login if 2fa is enabled/enforced
        Route::post('/signin/otp/2fa', 'UserController@postOtpLogin2faPassword')->name('user_signin_otp_2fa');
        // allow users with unverified email/mobile to login with password and then verify email/mobile
        Route::post('/signin/verify-user/otp', 'UserController@postSendVerifyUserOtp')->name('user_verify_user_otp');
        Route::post('/signin/verify-user/otp/verify', 'UserController@postVerifyUserOtp')->name('user_verify_user_otp_verify');

        Route::post('/register', 'UserController@postRegister')->name('user_register'); // ePOS
        Route::post('/register_unbounce', 'UserController@postRegisterUnbounce')->name('user_register_unbounce');
        Route::post('/register/otp', 'UserController@postRegisterSendOtp')->name('user_register_otp'); // ePOS
        Route::post('/register/otp/verify', 'UserController@postRegisterVerifyOtp')->name('user_register_otp_verify'); // ePOS
        Route::post('/salesforce/otp', 'UserController@postSendOtpForSalesForceUser')->name('send_otp_salesforce_user'); // ePOS
        Route::post('/salesforce/otp/verify', 'UserController@postVerifyOtpForSalesForceUser')->name('verify_otp_salesforce_user');
        Route::post('/oauth-signin', 'UserController@postOauthSignIn')->name('user_oauth_signin');
        Route::post('/oauth-register', 'UserController@postOauthRegister')->name('user_oauth_register');
        Route::post('/2fa/otp-verify', 'UserController@postSetup2faVerifyOtp')->name('user_2fa_otp_verify');
        Route::post('/2fa', 'userController@post2faOtp')->name('user_2fa');
        Route::patch('/2fa/contact', 'UserController@postUpdate2faContact')->name('user_2fa_contact');
        Route::patch('/password', 'UserController@postSetPassword')->name('user_set_password');
        Route::post('/2fa/otp-resend', 'UserController@postResendOtp')->name('user_2fa_otp_resned');
        Route::get('/session', 'UserController@getSessionData')->middleware(['auth:user'])->name('user_session');
        Route::get('/identifier/{client_id}', 'UserController@getIdentityToken')->middleware(['auth:user'])->name('user_identity');
        Route::post('/salesforce_event', 'UserController@postUserDetailsToSalesforce')->name('user_salesforce_event');
    });

    Route::group(['middleware' => ['auth:user', 'tnc_popup'], 'prefix' => 'user'], function()
    {
        Route::post('/pre_signup', 'MerchantController@postSignup')->name('user_pre_signup');
        Route::get('/business_types', 'MerchantController@getBusinessTypes')->name('user_business_types');
        Route::post('/verify_email', 'UserController@verifyEmailOtp')->name('user_verify_email');
        Route::post('/resend_email_otp', 'UserController@resendEmailOtp')->name('user_resend_email_otp');
        Route::post('/resend', 'MerchantController@postResendConfirmation')->name('user_resend_confirmation');
        Route::get('/keepalive', 'UserController@getKeepAlive')->name('user_keep_alive');
        Route::post('/logout', 'UserController@getLogout')->name('user_logout');

        // This returns all the needed information
        Route::get('/', 'UserController@getUserDetailsV2')->name('user_details'); //ePOS
        Route::get('/get-login-metadata', 'UserController@getLoginMetadata')->name('get_login_metadata');
        Route::get('/mobile', 'UserController@getUserDetailsForMobile')->name('user_mobile_details');
        Route::get('/details', 'UserController@getUserDetailsV2')->name('get_user_details');

        Route::post('/coupons/validate', 'MerchantController@validateCoupon')->name('user_coupons_validate');
        Route::post('/whatsapp/opt_in', 'MerchantController@whatsappOptIn')->name('user_whatsapp/opt_in');
    });

    Route::group(['middleware' => ['auth:user', 'tnc_popup']] , function()
    {
        Route::get('/merchant/experiments', 'MerchantController@getMerchantExperiments')->name('merchant_experiment');
        Route::get('/merchant/features', 'MerchantController@getMerchantFeatures')->name('merchant_features');
        Route::get('/merchant/splitzexperiments', 'MerchantController@getSplitzExperiments')->name('merchant_splitz_experiment');
        Route::get('/merchant/details', 'MerchantController@getMerchantDetails')->name('merchant_details');
        Route::get('/merchant/tags', 'MerchantController@getMerchantTags')->name('merchant_tags');
        Route::get('/merchant/navigation', 'MerchantController@getMerchantNavigationList')->name('merchant_navigation');

        // This is route is owned by 1cc team. It is required to handle Oauth providers callback
        Route::get('/1cc/analytics_integration/oauth/callback/{provider}', 'MerchantController@handleMagicAnalyticsOAuthCallbackURL')->name('magic_analytics_oauth_callback');
    });

    Route::group(['middleware'  =>  ['auth:user', 'verified', 'tnc_popup']], function()
    {
        Route::any('/merchant/api/{mode}/{path}', 'GenericController@handleAny')
            ->where(['path' => '.*'])
            ->name('merchant');

        Route::get('/cards/token', 'GenerateTokenController@generateToken')
            ->name('card_token');

        Route::post('/extension/generate_token', 'UserController@generateJWT')->name('extension_generate_token');

        Route::put('/{mode}/users/{id}/detach', 'MerchantController@removeUser')->name('remove_user');

        // Account Routes
        Route::get('/{mode}/accounts', 'MerchantController@getAccounts')->name('get_accounts');

        Route::get('/{mode}/analytics/transactions', 'TransactionController@getAnalytics')->name('analytics_transactions');
        Route::get('/{mode}/analytics/aggregations', 'TransactionController@getAggregations')->name('analytics_aggregations');
        Route::get('/{mode}/analytics/payment/aggregations', 'TransactionController@getPaymentAggregations')
            ->name('analytics_payments_aggregations');
        // ePOS => the routes which are being used by android ePOS app
        // Routes only used by ePOS
        Route::get('/{mode}/keys', 'MerchantController@getKeys')->name('get_keys'); // ePOS
        Route::post('/{mode}/key/new', 'MerchantController@postNewKey')->name('keys_setup'); // ePOS
        Route::post('/activation', 'MerchantController@postActivation')->name('post_activation'); // ePOS
        Route::post('/activation/save/step/{id}', 'MerchantController@postSaveActivationStep')->name('post_activation_save_step'); // ePOS
        Route::post('/activation/save/file', 'MerchantController@postSaveActivationFile')->name('post_activation_save_file'); // ePOS
        Route::get('/{mode}/invoices', 'MerchantController@getInvoices')->name('invoice_fetch_all'); // ePOS
        Route::post('/{mode}/invoices', 'MerchantController@postCreateInvoice')->name('invoice_create'); // ePOS

        Route::post('/keys/csv', 'MerchantController@getCsv')->name('keys_csv');

        Route::post('/store/app/key', 'MerchantController@storeAppKeys')->name('store_app_keys');
        Route::get('/fetch/app/key', 'MerchantController@fetchAppKeys')->name('fetch_app_keys');

        Route::post('/ezetap/void', 'MerchantController@ezetapVoidApi')->name('ezetap_void_api');
        Route::post('/ezetap/refund', 'MerchantController@ezetapRefundApi')->name('ezetap_refund_api');

        Route::get('/{mode}/reports/broking', 'TransactionController@getTransactionBrokingReport')->name('reports_broking');
        Route::get('/{mode}/reports/invoice', 'TransactionController@getInvoiceReport')->name('reports_invoice');
        Route::get('/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('reports_entity');
        // This is a sensitive route
        Route::get('settings/merchants/switch/{id}', 'UserController@switchCurrentMerchant')->name('merchants_switch');
        // Invitation related (User side)
        Route::post('settings/invitations/{invite}/accept', 'InvitationsController@postAcceptMerchantInvitation')
            ->name('merchant_invitation_accept');

        // Update password
        Route::post('/password', 'UserController@postPassword')->name('change_password');
        Route::post('/{mode}/addfunds', 'TransactionController@postAddfunds')->name('add_funds');
        Route::post('/{mode}/invoices/{invoiceId}/notify/{medium}', 'MerchantController@sendInvoiceNotification')->name('invoices_send_notification');
        Route::get('/{mode}/customers/autocomplete', 'MerchantController@getCustomersForAutocomplete')->name('customer_autocomplete');
        Route::get('/{mode}/items/autocomplete', 'MerchantController@getItemsForAutocomplete')->name('item_autocomplete');

        // Upgrades a standard invited user to a merchant
        Route::post('/merchants/register', 'UserController@postUpgradeUserToMerchant')->name('merchant_register');
        // Registers a sub-merchant account
        Route::post('/submerchants', 'MerchantController@postRegisterSubMerchant')->name('submerchant_register');
        // Send Feedback Mail to support@razorpay.com
        Route::post('/sendfeedback', 'MerchantController@sendFeedback')->name('send_feedback');

        Route::get('/reports/{log_id}', 'MerchantController@downloadReport')->name('report_download');

        Route::get('ufh/file/{file_id}', 'MerchantController@downloadFileFromUFH')->name('download_file_from_ufh');

        Route::post('/user/otp/verify', 'UserController@verifyUserViaOtp')->name('post_user_otp_verify');
        Route::post('/user/verify_contact', 'UserController@verifyContact')->name('post_user_verify_contact');

        Route::get('/support_chat/jwt_token', 'MerchantController@getSupportChatJwtToken')->name('get_support_chat_token');
    });

    Route::group(['middleware'  =>  ['admin', 'admin_access', 'set_x_frame', 'set_csp_header']], function()
    {
        Route::any('/admin/stats/{id}', 'AdminController@getMerchantStats')->name('admin_merchant_stats');
        Route::get('/admin/user', 'AdminController@getAdmin')->name('get_admin_user');
        Route::post('/admin/user/logout', 'AdminController@getLogout')->name('admin_user_logout');
        Route::get('/admin/user/keepalive', 'AdminController@getKeepAlive')->name('admin_user_keepalive');

        Route::post('/admin/features/{entityType}/{entityId}', 'AdminController@addEntityFeatures')->name('admin_features');

        Route::get('/admin/merchant/{id}/login', 'AdminController@getMerchantLogin')
               ->name('admin_merchant_login');
        Route::get('/admin/activity', 'AdminController@getAdminActivity')->name('get_admin_activity');
        Route::delete('/admin/activity', 'AdminController@deleteOtherAdminActivity')->name('delete_admin_activity');
        Route::delete('/admin/activity/{id}', 'AdminController@deleteAdminActivity')->name('delete_admin_activity_by_id');
        Route::get('/admin/merchant/{id}/hdfc_excel', 'AdminController@getMerchantHdfcExcel')->name('get_merchant_hdfc_excel');
        Route::get('/admin/merchant/{id}/screenshot', 'AdminController@getMerchantScreenshot')->name('get_merchant_screenshot');
        Route::get('admin/{mode}/merchants/aggregations', 'AdminController@getMerchantAggregations')
            ->name('get_merchant_aggregations');
        Route::get('admin/{mode}/merchants/{merchant_id}/aggregations', 'AdminController@getSingleMerchantAggregations')
            ->name('get_single_merchant_aggreagtions');

        // Admin merchant actions
        Route::post('/admin/merchant/{id}/edit', 'AdminController@postEditMerchant')->name('admin_merchant_edit');
        Route::get('/admin/merchant/{id}/activate', 'AdminController@getMerchantActivation')->name('admin_merchant_activate');
        Route::post('/admin/merchant/{id}/terminal', 'AdminController@postMerchantTerminal')->name('admin_merchant_terminal');
        Route::put('/admin/merchant/{id}/action', 'AdminController@putAction')->name('admin_merchant_action');
        Route::get('/admin/companies/{cin}/info', 'AdminController@getCompanyInfo')->name('admin_merchant_info');

        // Creevey Related routes
        Route::put('/admin/merchant/{id}/screenshot', 'AdminController@captureMerchantScreenshot')
            ->name('capture_merchant_screenshot');
        Route::post('/admin/merchant/{id}/screenshot', 'AdminController@saveMerchantScreenshot')
            ->name('save_merchant_screenshot');

        Route::post('/admin/{mode}/reconciliate', 'AdminController@postReconciliate')->name('post_reconciliate');

        Route::post('/makeapicall/{path?}', 'AdminController@passThrough')->where('path', '.*$')->name('make_api_call');

        Route::put('/admin/merchant/{id}/email', 'AdminController@putEditMerchantEmail')->name('admin_merchant_email_edit');
        Route::get('/admin/{mode}/fetchentity/{entity}/{format}', 'AdminController@getMultipleEntities')
                ->where('format', 'csv')
                ->name('admin_fetch_entity');

        // Upload logos for orgs
        Route::post('/admin/org/{org_id}', 'AdminController@postUploadOrgLogo')->name('update_org_logo');
        Route::post('/admin/org/{org_id}/bg_img', 'AdminController@postUploadOrgBackgroundImage')
            ->name('upload_org_background_image');
        Route::get('/admin/emaillogs', 'AdminController@getEmailLogs')->name('email_logs_get');

        Route::get('/admin/{mode}/reports/broking', 'TransactionController@getTransactionBrokingReport')->name('admin_reports_broking');
        Route::get('/admin/{mode}/reports/invoice/{merchant_id}', 'TransactionController@getInvoiceReport')->name('admin_reports_invoice');
        Route::get('/admin/{mode}/reports/{entity}', 'TransactionController@getResourceReport')->name('admin_reports_entity');

        Route::get('/admin/admin_reports/{log_id}', 'MerchantController@downloadReport')->name('download_admin_reports');

        Route::any('/admin/api/{mode}/{path}', 'GenericController@handleAny')
            ->where(['path' => '.*'])
            ->name('admin');
        Route::get('/admin/checkout-builder', 'AdminController@getCheckoutBuilder')->name('get_checkout_builder');
    });

    Route::post('/admin/clear-splitz-cache', 'AdminController@clearSplitzCache')->name('clear_splitz_cache');
    Route::get('razorx/{all?}', 'AdminController@getIndex')->name('razorx_catchall')->where(['all' => '.*']);
    Route::get('admin/capital-los/{all?}', 'AdminController@getIndex')->name('capital_catchall')->where(['all' => '.*']);
    Route::get('admin/{all}', 'AdminController@getIndex')->name('admin_catchall')->where(['all' => '.*'])->middleware(['set_x_frame', 'set_csp_header']);
    Route::post('admin/clear-org-cache', 'AdminController@clearOrgCache')->name('clear_org_cache');
    Route::post('/admin/clear-razorx-cache', 'AdminController@clearRazorXCache')->name('clear_razorx_cache');
});

Route::group(['middleware'  => 'graph'], function()
{
    Route::post('/graph', 'GraphController@handleRequestForGraph')
        ->name('graph_request');

});



Route::group(['middleware'  =>  'slack'], function ()
{
    Route::post('/slack', 'AdminController@postSlackQuery')->name('post_slack_query');
});

Route::group(['middleware' => ['auth.internal']], function()
{
    Route::post('/{mode}/transactions/{resource}', 'TransactionController@postIndex')->name('transactions_post_index');
});

Route::group(['middleware' => ['auth.cron']], function()
{
    Route::post('/{mode}/analytics/aggregations/day', 'AdminController@updateDayAggregations')
        ->name('update_day_aggregations_cron');
    Route::post('/{mode}/analytics/aggregations/{type}', 'TransactionController@updateTypeAggregations')
        ->name('update_type_transactions_cron');
});

Route::group(['middleware' => ['auth.oauth']], function()
{
    Route::get('/user/token/{token}/details', 'UserController@getDetailsFromToken')->name('get_user_details_from_token');
});

Route::group(['middleware'  => 'graph_oauth'], function()
{
    Route::post('/graph-oauth', 'GraphController@handleRequestForGraph')
        ->name('graph_oauth');
});

Route::group(['middleware' => 'web_oauth', 'prefix' => 'oauth'], function()
{
    // Dashboard routes hit by X Mobile App's webview
    Route::options('/{path?}', 'GenericController@handleAny')
        ->where(['path' => '.*'])->name('oauth_pre_flight'); // Bearer auth token sent by Mobile App's webview

    // Adding this for handling web view logout, since webview uses /user endpoint
    // but for oauth_logout we need to make a proxy call, therefore route_name is merchant
    // Refer ApiRequestAny constructor
    Route::post('/user/logout', 'UserController@oauthLogout')->name('oauth_user_logout'); // Bearer auth token sent by Mobile App's webview
    Route::post('/user/pre_signup', 'MerchantController@postSignup')->name('oauth_user_pre_signup'); // Bearer auth token sent by Mobile App's webview
    Route::post('/user/verify_email', 'UserController@verifyEmailOtp')->name('oauth_user_verify_email'); // Bearer auth token sent by Mobile App's webview
    Route::get('/user', 'UserController@getUserDetailsV2')->name('oauth_user_details'); // Bearer auth token sent by Mobile App's webview
    Route::get('/merchant/experiments', 'MerchantController@getMerchantExperiments')->name('oauth_merchant_experiments'); // Bearer auth token sent by Mobile App's webview
    Route::get('/merchant/features', 'MerchantController@getMerchantFeatures')->name('oauth_merchant_features');; // Bearer auth token sent by Mobile App's webview
    Route::get('/merchant/details', 'MerchantController@getMerchantDetails')->name('oauth_merchant_details'); // Bearer auth token sent by Mobile App's webview
    Route::any('/merchant/api/{mode}/{path}', 'GenericController@handleAny')
        ->where(['path' => '.*'])
        ->name('oauth_merchant'); // Bearer auth token sent by Mobile App's webview

    /***************************** */
    // Dashboard routes hit by GQL //
    /***************************** */
    // Request flow for these routes looks like:
    //
    // For user unauthenticated routes
    // Mobile(/graph-oauth) -> Edge(passthrough) -> Dashboard -> GQL -> Edge(passthrough) -> Dashboard(HERE!)
    //
    // For user authenticated routes
    // Mobile(/graph-oauth) -> Edge(passthrough) -> Dashboard -> GQL(AT header) -> Edge(attach passport) -> Dashboard(HERE!)
    Route::get('/org', 'AdminController@getOrg')->name('oauth_get_org'); // No passport auth token sent by GQL, since user is not authenticated yet

    // refresh_access_token, email_reset_password
    Route::any('/user/api/{mode}/{path}', 'GenericController@handleAny')
        ->where(['path' => '.*'])
        ->name('oauth_user'); // user unauthenticated route

    Route::post('/user/signin/otp', 'UserController@postSendLoginOtp')->name('oauth_user_signin_otp'); // user unauthenticated route
    Route::post('/user/oauth-signin', 'UserController@postOauthSignIn')->name('oauth_user_oauth_signin'); // user unauthenticated route
    Route::post('/user/signin', 'UserController@postSignin')->name('oauth_user_signin'); // user unauthenticated route
    Route::post('/user/signin/otp/verify', 'UserController@postVerifyLoginOtp')->name('oauth_user_signin_otp_verify'); // user unauthenticated route
    // allow users with unverified email/mobile to login with password and then verify email/mobile
    Route::post('/user/signin/verify-user/otp', 'UserController@postSendVerifyUserOtp')->name('oauth_user_verify_user_otp'); // user unauthenticated route
    Route::post('/user/signin/verify-user/otp/verify', 'UserController@postVerifyUserOtp')->name('oauth_user_verify_user_otp_verify'); // user unauthenticated route

    // 2FA Oauth Token Routes
    Route::post('/user/2fa/otp-verify', 'UserController@postSetup2faVerifyOtp')->name('oauth_post_setup_2fa_verify_otp'); // user authenticated route with 2fa token
    Route::post('/user/signin/otp/2fa', 'UserController@postOtpLogin2faPassword')->name('oauth_post_otp_login_2fa_password'); // user authenticated route with 2fa token
    Route::post('/user/2fa/otp-resend', 'UserController@postResendOtp')->name('oauth_user_2fa_otp_resned'); // user authenticated route with 2fa token
});
