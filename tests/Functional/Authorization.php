<?php

namespace RZP\Tests\Functional;

use RZP\Constants\Mode;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;


class Authorization
{
    protected $test;
    protected $auth  = [];
    protected $type;
    protected $proxy = false;

    protected $key;
    protected $token;
    protected $secret;
    protected $account;
    protected $orgId;
    protected $admin;
    protected $appHeaders;
    protected $bearerHeaders;
    protected $adminHeaders = [];
    protected $adminProxyHeaders;
    protected $proxyHeaders;
    protected $merchant;

    protected $defaultKey               = 'rzp_test_TheTestAuthKey';
    protected $defaultOAuthKey          = 'rzp_test_oauth_TheTestAuthKey';
    protected $defaultSecret            = 'TheKeySecretForTests';
    protected $defaultDeviceToken       = 'authentication_token';
    protected $defaultMerchantUser      = User::MERCHANT_USER_ID;
    protected $defaultToken             = Org::DEFAULT_TOKEN . Org::DEFAULT_TOKEN_PRINCIPAL;
    protected $defaultOrgId             = Org::RZP_ORG_SIGNED;

    protected $defaultDashboardHostname = 'dashboard.razorpay.in';
    protected $defaultAccountId         = 'acc_10000000000001';

    public function __construct($test)
    {
        $this->test = $test;
    }

    /**
     * Sets the key and secret created setUp call as the
     * default to provide basicauth.
     * You can use your key and secret by overriding this
     * function in child classes.
     */
    public function basicAuth($user = null, $pwd = null)
    {
        $this->auth = [
            'PHP_AUTH_USER' => $user,
            'PHP_AUTH_PW'   => $pwd
        ];
    }

    public function oauthPublicTokenAuth(string $token = null)
    {
        $this->type = 'public';

        $token = $token ?? $this->defaultOAuthKey;

        $this->auth = [
            'PHP_AUTH_USER' => $token
        ];
    }

    public function oauthBearerAuth(string $accessToken)
    {
        $this->type = 'bearer';

        $this->bearerHeaders = [
            'Authorization' => 'Bearer ' . $accessToken
        ];
    }

    public function appAuth($user = 'rzp_test', $pwd = '', $hostName = null)
    {
        $this->proxyHeaders = [];

        if ($pwd === '')
        {
            $dashboardConfig = \Config::get('applications.dashboard');
            $pwd = $dashboardConfig['secret'];
        }

        $this->basicAuth($user, $pwd);

        $this->type = 'app';

        $this->addAppAuthHeaders($hostName);
    }

    public function appBasicAuth($user = null, $pwd = null)
    {
        if ($user === null) {
            $user = $this->defaultKey;
        }

        if ($pwd === null) {
            $pwd = $this->defaultSecret;
        }

        $this->basicAuth($user, $pwd);

        $this->type = 'app';
    }

    public function terminalsAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.terminals_service')['secret']);
    }

    public function mtuLambdaAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.mtu_lambda')['secret']);
    }

    public function razorflowAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.razorflow')['secret']);
    }

    public function subscriptionsAuth()
    {
        $this->appAuth('rzp_test_10000000000000', \Config::get('applications.subscriptions')['secret']);

        $this->proxy = true;
    }

    public function subscriptionsAppAuth()
    {
        $this->appAuth('rzp_test', \Config::get('applications.subscriptions')['secret']);

        $this->proxy = false;
    }

    public function reminderAuth()
    {
        $this->appAuth('rzp_test_10000000000000', \Config::get('applications.reminders')['secret']);

        $this->proxy = true;
    }

    public function reminderAppAuth()
    {
        $this->appAuth('rzp_test', \Config::get('applications.reminders')['secret']);

        $this->proxy = false;
    }

    public function dashboardGuestAppAuth($hostname = null, $mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.dashboard_guest')['secret'], $hostname);

        $this->proxy = false;
    }

    public function affordabilityInternalAppAuth($hostname = null, $mode = 'test'): void
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.affordability')['secret'], $hostname);

        $this->proxy = false;
    }

    public function cardPaymentsInternalAppAuth($hostname = null, $mode = 'test'): void
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.card_payment_service')['secret'], $hostname);

        $this->proxy = false;
    }

    public function trustedBadgeInternalAppAuth($hostname = null, $mode = 'test'): void
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.trusted_badge')['secret'], $hostname);

        $this->proxy = false;
    }

    public function dashboardInternalAppAuth($hostname = null, $mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.dashboard_internal')['secret'], $hostname);

        $this->proxy = false;
    }

    public function payoutInternalAppAuth($mode = 'test', $hostname = null)
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.payouts_service')['secret'], $hostname);

        $this->proxy = false;
    }

    public function salesForceAuth()
    {
        $this->appAuth('rzp_test_10000000000000', \Config::get('applications.salesforce')['secret']);

        $this->proxy = true;
    }

    public function paymentLinksAuth()
    {
        $this->appAuth('rzp_test_10000000000000', \Config::get('applications.payment_links')['secret']);

        $this->proxy = true;
    }

    public function bankingAccountServiceAppAuth($user = 'rzp_test')
    {
        $this->appAuth($user, \Config::get('applications.banking_account_service')['secret']);
    }

    public function capitalCardsClientAppAuth($mode = 'live')
    {
        $capitalCardsConfig = \Config::get('applications.capital_cards_client');

        $pwd                = $capitalCardsConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function bvsAppAuth($user = 'rzp_test')
    {
        $this->appAuth($user, \Config::get('applications.bvs')['secret']);
    }

    public function pgRouterAuth($mode = 'test')
    {
        $pgRouterConfig = \Config::get('applications.pg_router');

        $pwd = $pgRouterConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function magicConsumerAppAuth($user = 'rzp_test')
    {
        // TODO: Change auth config
        $this->appAuth($user, \Config::get('applications.consumer_app')['secret']);
    }

    public function mandateHQAuth()
    {
        $this->appAuth('rzp_test', \Config::get('applications.mandate_hq')['secret']);

        $this->proxy = false;
    }

    public function batchAuth($user = 'rzp_test_10000000000000')
    {
        $this->appAuth($user, \Config::get('applications.batch')['secret']);

        $this->proxy = true;
    }

    public function batchAppAuth($user = 'rzp_test')
    {
        $this->appAuth($user, \Config::get('applications.batch')['secret']);

        $this->proxy = false;
    }

    public function accountServiceAuth($user = 'rzp_test')
    {
        $this->appAuth($user, \Config::get('applications.account_service')['secret']);

        $this->proxy = false;
    }

    public function payoutLinksAppAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.payout_links')['secret']);

        $this->proxy = false;
    }

    public function accountingIntegrationsAppAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.accounting_integrations')['secret']);

        $this->proxy = false;
    }

    public function payoutLinksCustomerPageAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.payout_link_customer_page')['secret']);

        $this->proxy = false;
    }

    public function MetroAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.metro')['secret']);

        $this->proxy = false;
    }

    public function workflowsAppAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.workflows')['secret']);

        $this->proxy = false;
    }

    public function storkAppAuth($user = 'rzp_test')
    {
        $this->appAuth($user, \Config::get('applications.stork')['secret']);

        $this->proxy = false;
    }

    public function mobAppAuthForProxyRoutes($mode = 'test', $merchantId = '10000000000000', $userId = null)
    {
        $this->appAuth('rzp_' . $mode . '_' . $merchantId, \Config::get('applications.master_onboarding')['secret']);

        if ($userId === null)
        {
            $userId = $this->defaultMerchantUser;
        }

        $this->appHeaders['X-Dashboard-User-Id'] = $userId;
    }

    public function mobAppAuthForInternalRoutes($user = 'rzp_test')
    {
        $this->appAuth($user, \Config::get('applications.master_onboarding')['secret']);
    }

    public function freshdeskWebhookAuth($mode = 'test')
    {
        $config = \Config::get('applications.freshdesk_webhook');

        $pwd = $config['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }
    public function friendBuyWebhookAuth($mode = 'test')
    {
        $config = \Config::get('applications.friend_buy_webhook');

        $pwd = $config['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }
    public function vajraAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.vajra')['secret']);
    }

    public function downtimeServiceAuth($mode = 'test')
    {
        $this->appAuth('rzp_' . $mode, \Config::get('applications.downtime_service')['secret']);
    }

    public function careAppAuth()
    {
        $this->appAuth('rzp_test', \Config::get('applications.care')['secret']);

        $this->proxy = false;
    }

    public function cmmaAppAuth()
    {
        $this->appAuth('rzp_test', \Config::get('applications.cmma')['secret']);

        $this->proxy = false;
    }

    public function careAuth()
    {
        $this->appAuth('rzp_test_10000000000000', \Config::get('applications.care')['secret']);

        $this->proxy = true;
    }
    public function addAppAuthHeaders($hostName)
    {
        if ($hostName === null)
        {
            $hostName = $this->defaultDashboardHostname;
        }

        $this->appHeaders = [
            'X-Org-Hostname' => $hostName,
        ];
    }

    public function setAppAuthHeaders($headers)
    {
        $this->appHeaders = array_merge($this->appHeaders, $headers);
    }

    public function appAuthLive($pwd = '')
    {
        $this->appAuth('rzp_live', $pwd);
    }

    public function appAuthTest($pwd = '')
    {
        $this->appAuth('rzp_test', $pwd);
    }

    public function proxyAuth($user = 'rzp_test_10000000000000', $merchantUser = null)
    {
        $this->appAuth($user, \Config::get('applications.merchant_dashboard')['secret']);

        $this->proxy = true;

        $this->addProxyAuthHeaders($merchantUser);
    }

    public function disputesServiceAuth($mode = 'test')
    {
        $disputesConfig = \Config::get('applications.disputes');

        $pwd = $disputesConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function addXOriginHeader()
    {
        $this->appHeaders['X-Request-Origin'] = \Config::get('applications.banking_service_url');
    }

    public function addXBankLMSOriginHeader()
    {
        $this->appHeaders['X-Request-Origin'] = \Config::get('applications.bank_lms_banking_service_url');
    }

    public function hostedProxyAuth($user = 'rzp_test_10000000000000', $merchantUser = null)
    {
        $this->appAuth($user, \Config::get('applications.hosted')['secret']);

        $this->proxy = true;

        $this->addProxyAuthHeaders($merchantUser);
    }

    public function merchantRiskAlertsAppAuth()
    {
        $this->appAuth('rzp_test', \Config::get('applications.merchant_risk_alerts')['secret']);

        $this->proxy = false;
    }

    public function cyberCrimeHelpDeskAppAuth()
    {
        $this->appAuth('rzp_test', \Config::get('applications.cyber_crime_helpdesk')['secret']);

        $this->proxy = false;
    }

    public function addProxyAuthHeaders($user)
    {
        if ($user === null)
        {
            $user = $this->defaultMerchantUser;
        }

        $this->proxyHeaders = [
            'X-Dashboard-User-Id'   => $user,
        ];
    }

    public function setProxyHeader($headers){
        $this->proxyHeaders = [];
    }

    public function getProxyHeaders()
    {
        return $this->proxyHeaders;
    }

    public function proxyAuthTest()
    {
        $this->proxyAuth();
    }

    public function proxyAuthLive()
    {
        $this->proxyAuth('rzp_live_10000000000000', 'MerchantUser01');
    }

    public function publicAuth($key = null)
    {
        $this->type = 'public';

        if ($key === null)
        {
            // $this->key = $this->defaultKey;
            // $key = $this->defaultKey;
            if ($this->key === null)
            {
                $this->key = $this->defaultKey;
                $key = $this->defaultKey;
            }
            else
            {
                $key = $this->key;
            }
        }
        else
        {
            $this->key = $key;
        }

        $this->basicAuth($key, '');
    }

    public function deviceAuth($key = null, $secret = null)
    {
        $this->type = 'device';

        if ($key === null)
        {
            $key = $this->defaultKey;

            $this->key = $key;
        }

        if ($secret === null)
        {
            $secret = $this->defaultDeviceToken;
        }

        $this->setSecret($secret);

        $this->basicAuth($key, $secret);
    }

    public function publicCallbackAuth()
    {
        $this->noAuth();

        $this->type = 'public_callback';
    }

    public function publicTestAuth()
    {
        $this->publicAuth();
    }

    public function publicLiveAuth($key = 'rzp_live_TheLiveAuthKey')
    {
        $this->publicAuth($key);
    }

    public function privateAuth($key = null, $secret = null)
    {
        $this->type = 'private';

        if ($key === null)
        {
            $key = $this->defaultKey;

            $this->key = $key;
        }

        if ($secret === null)
        {
            $secret = $this->defaultSecret;
        }

        $this->setSecret($secret);

        $this->basicAuth($key, $secret);
    }

    /**
     * Format - (100DemoAccount, rzp_test_partner_TestClientId, TestClientSecret)
     *
     * @param      $key
     * @param null $secret
     */
    public function partnerAuth($submerchantId, $key, $secret = null)
    {
        $type = 'private';

        $this->key = $key;

        if ($secret === null)
        {
            $type = 'public';

            $secret = '';
        }

        $this->type = $type;

        $this->setSecret($secret);

        $this->addAccountAuth($submerchantId);

        $this->basicAuth($key, $secret);
    }

    public function adminAuth($mode = 'test', $token = null, $orgId = null, $hostName = null, string $crossOrgId = null)
    {
        $appAuthCaller = 'appAuth' . studly_case($mode);

        $this->$appAuthCaller(\Config::get('applications.admin_dashboard')['secret']);

        $this->type = 'admin';

        $this->addAdminAuthHeaders($orgId, $token, $hostName, $crossOrgId);
    }

    public function adminAuthWithPermission(string $permissionName, $mode = 'test', $token = null, $orgId = null, $hostName = null, string $crossOrgId = null)
    {
        $this->adminAuth($mode, $token, $orgId, $hostName, $crossOrgId);

        $admin = $this->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $permission = Fixtures\Fixtures::getInstance()->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($permission->getId());
    }

    public function adminProxyAuth($account = '10000000000000',
                                   $user = 'rzp_test_10000000000000',
                                   $token = null,
                                   $orgId = null,
                                   $hostName = null)
    {
        $this->appAuth($user, \Config::get('applications.admin_dashboard')['secret']);

        $this->addAdminProxyAuthHeaders($account, $orgId, $token, $hostName);

        $this->type = 'admin_proxy';
    }

    public function dashboardAuth($mode = 'test')
    {
        $this->appAuth('rzp_'.$mode, 'put dashboard pass here');
    }

    public function frontendGraphqlAuth($mode = 'test')
    {
        $config = \Config::get('applications.frontend_graphql');

        $pwd = $config['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function cronAuth($mode = 'test', $hostname = null)
    {
        $cronConfig = \Config::get('applications.cron');

        $pwd = $cronConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd, $hostname);
    }

    public function myOperatorAuth($mode = 'test')
    {
        $myOperatorConfig = \Config::get('applications.myoperator');

        $pwd = $myOperatorConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function yellowMessengerAuth($mode = 'test')
    {
        $config = \Config::get('applications.yellowmessenger');

        $pwd = $config['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function expressAuth($mode = 'test', $user = null)
    {
       if (is_null($user) === false)
       {
            $this->appAuth($user, \Config::get('applications.express')['secret']);
       }
       else
       {
            $expressConfig = \Config::get('applications.express');

            $pwd = $expressConfig['secret'];

            $this->appAuth('rzp_' . $mode, $pwd);
        }

    }

    public function kotakAuth($mode = 'test')
    {
        $kotakConfig = \Config::get('applications.kotak');

        $pwd = $kotakConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function h2hAuth($mode = 'test')
    {
        $h2hConfig = \Config::get('applications.h2h');

        $pwd = $h2hConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function yesbankAuth($mode = 'test')
    {
        $kotakConfig = \Config::get('applications.yesbank');

        $pwd = $kotakConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function iciciAuth($mode = 'test')
    {
        $iciciConfig = \Config::get('applications.icici');

        $pwd = $iciciConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function hdfcEcmsAuth($mode = 'test')
    {
        $hdfcEcmsConfig = \Config::get('applications.hdfc_ecms');

        $pwd = $hdfcEcmsConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function hdfcOtcAuth($mode = 'test')
    {
        $hdfcOTCConfig = \Config::get('applications.hdfc_otc');

        $pwd = $hdfcOTCConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function authServiceAuth($mode = 'test')
    {
        $authServiceConfig = \Config::get('applications.auth_service');

        $pwd = $authServiceConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function noAuth()
    {
        $this->directAuth();
    }

    public function directAuth()
    {
        $this->type = 'direct';

        $this->basicAuth(null, null);
    }

    public function scroogeAuth($mode = 'test')
    {
        $cronConfig = \Config::get('applications.scrooge');

        $pwd = $cronConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function smartCollectAuth($mode = 'test')
    {
        $smartCollectConfig = \Config::get('applications.smart_collect');

        $pwd = $smartCollectConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }
    /**
     * Adds account auth to a request
     *
     * @param string|null $accountId
     */
    public function addAccountAuth($accountId = null)
    {
        if ($accountId === null)
        {
            $accountId = $this->defaultAccountId;
        }

        $this->account = $accountId;
    }

    /**
     * Adds admin auth headers to a request
     */
    public function addAdminAuthHeaders(string $orgId = null, string $adminToken = null, string $orgHostname = null, string $crossOrgId = null)
    {
        if ($adminToken === null)
        {
            $adminToken = $this->defaultToken;
        }

        if ($orgId === null)
        {
            $orgId = $this->defaultOrgId;
        }

        if ($orgHostname === null)
        {
            $orgHostname = $this->defaultDashboardHostname;
        }

        $this->setToken($adminToken);
        $this->setOrganisation($orgId);

        $this->adminHeaders = [
            'X-Org-Id' => $orgId,
            'X-Admin-Token' => $adminToken,
            'X-Org-Hostname' => $orgHostname,
            'X-Cross-Org-Id' => $crossOrgId,
        ];
    }

    /**
     * Adds admin_proxy auth headers to a request
     * @param string|null $account
     * @param string|null $orgId
     * @param string|null $adminToken
     * @param string|null $orgHostname
     */
    public function addAdminProxyAuthHeaders(string $account = null,
                                             string $orgId = null,
                                             string $adminToken = null,
                                             string $orgHostname = null)
    {
        if ($account === null)
        {
            $account = $this->defaultAccountId;
        }

        if ($adminToken === null)
        {
            $adminToken = $this->defaultToken;
        }

        if ($orgId === null)
        {
            $orgId = $this->defaultOrgId;
        }

        if ($orgHostname === null)
        {
            $orgHostname = $this->defaultDashboardHostname;
        }

        $this->setToken($adminToken);
        $this->setOrganisation($orgId);

        $this->adminProxyHeaders = [
            'X-Org-Id'              => $orgId,
            'X-Admin-Token'         => $adminToken,
            'X-Org-Hostname'        => $orgHostname,
            'X-Razorpay-Account'    => $account,
        ];
    }

    /**
     * Remove account auth
     */
    public function deleteAccountAuth()
    {
        $this->account = null;
    }

    public function getCreds()
    {
        return $this->auth;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isPublicAuth()
    {
        return ($this->type === 'public');
    }

    public function isAdminProxyAuth()
    {
        return ($this->type === 'admin_proxy');
    }

    public function isProxyAuth()
    {
        return ($this->proxy === true);
    }

    public function isPrivateAuth()
    {
        return ($this->type === 'private');
    }

    public function isAccountAuth()
    {
        return (empty($this->getAccountHeader()) === false);
    }

    public function isAdminAuth()
    {
        return ($this->type === 'admin');
    }

    public function getAccountHeader()
    {
        $headers = [];

        if ($this->account !== null)
        {
            $headers = [
                'X-Razorpay-Account'    => $this->account
            ];
        }

        return $headers;
    }

    /**
     * Sets admin headers.
     *
     * @param array $adminHeaders
     */
    public function setAdminHeaders(array $adminHeaders = [])
    {
        $this->adminHeaders = array_merge($this->adminHeaders, $adminHeaders);
    }

    public function getAppHeaders()
    {
        return $this->appHeaders;
    }

    public function getAdminHeaders()
    {
        return $this->adminHeaders;
    }

    public function getAdminProxyHeaders()
    {
        return $this->adminProxyHeaders;
    }

    public function getKey()
    {
        return $this->auth['PHP_AUTH_USER'];
    }

    public function getSecret()
    {
        return $this->auth['PHP_AUTH_PW'];
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function setDefaultKey($key)
    {
        $this->defaultKey = $key;

        return $this;
    }

    public function setDefaultSecret($secret)
    {
        $this->defaultSecret = $secret;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function setOrganisation($orgId)
    {
        $this->orgId = $orgId;

        return $this;
    }

    public function setKeyAndSecret($key, $secret)
    {
        $this->setKey($key);

        $this->setSecret($secret);

        return $this;
    }

    public function isSecretNull()
    {
        return ($this->secret === null);
    }

    public function getMode()
    {
        $key = $this->getKey();

        $mode = explode('_', $key)[1];

        return $mode;
    }

    public function getAppAuthKeyForMode()
    {
        $mode = $this->getMode();

        assert(($mode === 'live') or ($mode === 'test'));

        return 'rzp_'.$mode;
    }

    public function appAuthMode()
    {
        $key = $this->getAppAuthKeyForMode();

        $this->appAuth($key);
    }

    public function getAdmin($token = null)
    {
        if ($token === null)
        {
            $token = $this->token ?: $this->defaultToken;
        }

        if ($this->admin === null)
        {
            $this->admin = (new \RZP\Models\Admin\Admin\Token\Repository)
                                ->findOrFailToken($token)->admin;
        }

        return $this->admin;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getOrgId()
    {
        return $this->orgId;
    }

    public function isAppAuth()
    {
        return ($this->type === 'app');
    }

    public function isBearerAuth()
    {
        return ($this->type === 'bearer');
    }

    public function getBearerHeader()
    {
        return $this->bearerHeaders;
    }

    public function ftsAuth($mode = 'test')
    {
        $ftsConfig = \Config::get('applications.fts');

        $pwd = $ftsConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function thirdwatchAuth($mode = 'test')
    {
        $thirdwatchConfig = \Config::get('applications.thirdwatch');

        $pwd = $thirdwatchConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function xpayrollAuth($mode = 'test')
    {
        $xpayrollConfig = \Config::get('applications.xpayroll');

        $pwd = $xpayrollConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function capitalCardsAuth($mode = 'test')
    {
        $cardsServiceConfig = \Config::get('applications.capital_cards_client');

        $pwd = $cardsServiceConfig['secret'];

        $this->appAuth('rzp_'. $mode, $pwd);
    }

    public function capitalCollectionsAuth($mode = 'test')
    {
        $capitalCollectionsConfig = \Config::get('applications.capital_collections_client');

        $pwd = $capitalCollectionsConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function settlementsAuth($mode = 'test')
    {
        $settlementConfig = \Config::get('applications.settlements_service');

        $pwd = $settlementConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function partnershipServiceAuth($mode = 'test')
    {
        $partnershipConfig = \Config::get('applications.partnerships');

        $pwd = $partnershipConfig['secret'];

        $this->appAuth('rzp_' . $mode, $pwd);
    }

    public function setMerchant($merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function addXDashboardIpHeader($hostName)
    {
        $this->appHeaders['X-Dashboard-Ip'] = $hostName;
    }

    public function capitalEarlySettlementAuth($mode = 'test')
    {
        $capitalESConfig = \Config::get('applications.capital_early_settlements');

        $pwd = $capitalESConfig['secret'];

        $this->appAuth('rzp_'.$mode, $pwd);
    }

    public function checkoutServiceInternalAuth(string $mode = Mode::TEST): void
    {
        $this->appAuth(
            'rzp_' . $mode,
            \Config::get('applications.checkout_service')['secret']
        );

        $this->proxy = false;
    }

    public function checkoutServiceProxyAuth(
        string $mode = Mode::TEST,
        string $merchantId = Account::TEST_ACCOUNT
    ): void {
        $this->appAuth(
            "rzp_{$mode}_{$merchantId}",
            \Config::get('applications.checkout_service')['secret']
        );

        $this->proxy = true;
    }
}
