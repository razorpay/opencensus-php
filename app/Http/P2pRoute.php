<?php

namespace RZP\Http;

use ApiResponse;
use Illuminate\Routing\Router;
use RZP\Foundation\Application;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Controllers\P2p\Requests;
use RZP\Models\Feature\Constants as Feature;

use RZP\Models\Admin\Permission\Name as Permission;

final class P2pRoute
{

    protected static $p2pRoutes = [
        /*************** Customers ****************/
        Requests::P2P_CUSTOMER_START_VERIFICATION =>
            [
                'post',
                'customers/verification/start',
                'CustomerController@startVerification',
            ],
        Requests::P2P_CUSTOMER_VERIFICATION_STATUS =>
            [
                'get',
                'customers/verification/{token}',
                'CustomerController@getVerificationStatus'
            ],
        Requests::P2P_CUSTOMER_CREATE =>
            [
                'post',
                'customers',
                'CustomerController@create'
            ],
        Requests::P2P_CUSTOMER_DELETE =>
            [
                'delete',
                'customers',
                'CustomerController@delete'
            ],

        /*************** Devices ******************/
        Requests::P2P_CUSTOMER_DEVICE_CREATE =>
            [
                'post',
                'customers/{customer_id}/devices',
                'DeviceController@create'
            ],
        Requests::P2P_CUSTOMER_DEVICE_FETCH =>
            [
                'get',
                'customers/{customer_id}/devices',
                'DeviceController@fetch'
            ],
        Requests::P2P_CUSTOMER_DEVICE_REFRESH_TOKEN =>
            [
                'post',
                'customers/{customer_id}/devices/cl_token_refresh',
                'DeviceController@refreshClToken'
            ],
        Requests::P2P_CUSTOMER_DEVICE_DELETE =>
            [
                'delete',
                'customers/{customer_id}/devices',
                'DeviceController@delete'
            ],

        /*************** Bank Account **************/
        Requests::P2P_BANKS_FETCH_ALL =>
            [
                'get',
                'banks',
                'BankAccountController@fetchBanks'
            ],
        Requests::P2P_CUSTOMER_BA_RETRIEVE =>
            [
                'get',
                'customers/{customer_id}/bank_accounts/bank/{bank_code}',
                'BankAccountController@retrieve'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH_ALL =>
            [
                'get',
                'customers/{customer_id}/bank_accounts',
                'BankAccountController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH =>
            [
                'get',
                'customers/{customer_id}/bank_accounts/{ba_id}',
                'BankAccountController@fetch'
            ],
        Requests::P2P_CUSTOMER_BA_INITIATE_SET_UPI_PIN =>
            [
                'get',
                'customers/{customer_id}/bank_accounts/{ba_id}/upipin/initiate',
                'BankAccountController@initiateSetUpiPin'
            ],
        Requests::P2P_CUSTOMER_BA_SET_UPI_PIN =>
            [
                'post',
                'customers/{customer_id}/bank_accounts/{ba_id}/upi_pin',
                'BankAccountController@setUpiPin'
            ],
        Requests::P2P_CUSTOMER_BA_INITIATE_FETCH_BALANCE =>
            [
                'get',
                'customers/{customer_id}/bank_accounts/{ba_id}/balance/initiate',
                'BankAccountController@initiateFetchBalance'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH_BALANCE =>
            [
                'post',
                'customers/{customer_id}/bank_accounts/{ba_id}/balance/',
                'BankAccountController@fetchBalance'
            ],

        /****************** VPA *******************/
        Requests::P2P_HANDLES_FETCH_ALL =>
            [
                'get',
                'handles',
                'VpaController@fetchHandles'
            ],
        Requests::P2P_CUSTOMER_VPA_CREATE =>
            [
                'post',
                'customers/{customer_id}/vpa',
                'VpaController@create'
            ],
        Requests::P2P_CUSTOMER_VPA_FETCH_ALL =>
            [
                'get',
                'customers/{customer_id}/vpa',
                'VpaController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_VPA_FETCH =>
            [
                'get',
                'customers/{customer_id}/vpa/{vpa_id}',
                'VpaController@fetch'
            ],
        Requests::P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT =>
            [
                'post',
                'customers/{customer_id}/vpa/{vpa_id}/assign/{ba_id}',
                'VpaController@assignBankAccount'
            ],
        Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY =>
            [
                'post',
                'customers/{customer_id}/vpa/available',
                'VpaController@checkAvailability'
            ],
        Requests::P2P_CUSTOMER_VPA_DELETE =>
            [
                'delete',
                'customers/{customer_id}/vpa/{vpa_id}',
                'VpaController@delete'
            ],

        /************* Beneficiaries **************/
        Requests::P2P_CUSTOMER_BENEFICIARIES =>
            [
                'post',
                'customers/{customer_id}/beneficiaries',
                'BeneficiaryController@create'
            ],
        Requests::P2P_CUSTOMER_BENEFICIARIES_VALIDATE =>
            [
                'post',
                'customers/{customer_id}/beneficiaries',
                'BeneficiaryController@validate'
            ],
        Requests::P2P_CUSTOMER_BENEFICIARIES_FETCH_ALL =>
            [
                'get',
                'customers/{customer_id}/beneficiaries',
                'BeneficiaryController@fetchAll'
            ],

        /************* Transactions **************/
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_PAY =>
            [
                'post',
                'customers/{customer_id}/transactions/pay/initiate',
                'TransactionController@initiatePay'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_COLLECT =>
            [
                'post',
                'customers/{customer_id}/transactions/collect/initiate',
                'TransactionController@initiateCollect'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH_ALL =>
            [
                'get',
                'customers/{customer_id}/transactions/',
                'TransactionController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH =>
            [
                'get',
                'customers/{customer_id}/transactions/{transaction_id}',
                'TransactionController@fetch'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_AUTHORIZE =>
            [
                'get',
                'customers/{customer_id}/transactions/{transaction_id}/authorize/initiate',
                'TransactionController@initiateAuthorize'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE =>
            [
                'post',
                'customers/{customer_id}/transactions/{transaction_id}/authorize',
                'TransactionController@authorizeTransaction'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_REJECT_COLLECT =>
            [
                'post',
                'customers/{customer_id}/transactions/{transaction_id}/reject',
                'TransactionController@reject'
            ],
    ];

    public static $p2p = [
        Requests::P2P_CUSTOMER_START_VERIFICATION,
        Requests::P2P_CUSTOMER_VERIFICATION_STATUS,
        Requests::P2P_CUSTOMER_CREATE,
        Requests::P2P_CUSTOMER_DELETE,

        Requests::P2P_CUSTOMER_DEVICE_CREATE,
        Requests::P2P_CUSTOMER_DEVICE_FETCH,
        Requests::P2P_CUSTOMER_DEVICE_REFRESH_TOKEN,
        Requests::P2P_CUSTOMER_DEVICE_DELETE,

        Requests::P2P_BANKS_FETCH_ALL,
        Requests::P2P_CUSTOMER_BA_RETRIEVE,
        Requests::P2P_CUSTOMER_BA_FETCH_ALL,
        Requests::P2P_CUSTOMER_BA_FETCH,
        Requests::P2P_CUSTOMER_BA_INITIATE_SET_UPI_PIN,
        Requests::P2P_CUSTOMER_BA_SET_UPI_PIN,
        Requests::P2P_CUSTOMER_BA_INITIATE_FETCH_BALANCE,
        Requests::P2P_CUSTOMER_BA_FETCH_BALANCE,

        Requests::P2P_HANDLES_FETCH_ALL,
        Requests::P2P_CUSTOMER_VPA_CREATE,
        Requests::P2P_CUSTOMER_VPA_FETCH_ALL,
        Requests::P2P_CUSTOMER_VPA_FETCH,
        Requests::P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT,
        Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY,
        Requests::P2P_CUSTOMER_VPA_DELETE,

        Requests::P2P_CUSTOMER_BENEFICIARIES,
        Requests::P2P_CUSTOMER_BENEFICIARIES_VALIDATE,
        Requests::P2P_CUSTOMER_BENEFICIARIES_FETCH_ALL,

        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_PAY,
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_COLLECT,
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH_ALL,
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH,
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_AUTHORIZE,
        Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
        Requests::P2P_CUSTOMER_TRANSACTIONS_REJECT_COLLECT,
    ];

    public static $routePermission = [];

    /**
     * List of routes, requiring session changes
     */
    public static $session = [];

    /**
     * A route can belong to multiple features, mapped here
     */
    public static $routeNameToFeaturesMap = [];

    // Sets TRACE level to CRITICAL for these routes
    const CRITICAL_ROUTES = [];


    /**
     * S2S payment routes
     */
    const S2S_PAYMENT_ROUTES = [];

    const SUBSCRIPTION_PROXY_ROUTES = [];

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var BasicAuth
     */
    protected $ba;

    /**
     * @var Application
     */
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        $this->router = $app['router'];

        $this->ba = $app['basicauth'];
    }

    public function getCurrentRouteName()
    {
        return $this->router->currentRouteName();
    }

    /**
     * Check if provided route is critical route.
     * If null then check for current route
     *
     * @param  string  $route
     * @return boolean
     */
    public function isCriticalRoute($route = null)
    {
        if ($route === null)
        {
            $route = $this->getCurrentRouteName();
        }

        return in_array($route, self::CRITICAL_ROUTES, true);
    }

    public function getUrl($routeName, array $parameters = [], $key = '', $secret = '')
    {
        if (($secret === '') and
            ($key !== ''))
        {
            // It's a public auth.
            $parameters['key_id'] = $key;
            $key = '';
        }

        $urlSegment = \URL::route($routeName, $parameters, false);

        $url = $this->getSchemaHostAndAuth($key, $secret) . $urlSegment;

        return $url;
    }

    public function getUrlWithPublicAuth($routeName, array $parameters = [], $key = '')
    {
        // If current request was on keyless public auth, append the x_entity_id query for public urls
        // only if the same is not required in route parameters in which case it will be there in $parameters already.
        if (($key === '') and ($this->ba->isKeylessPublicAuth() === true))
        {
            if (str_contains(self::$p2pRoutes[$routeName][1], '{x_entity_id}') === false)
            {
                $parameters['x_entity_id'] = $this->ba->getKeylessXEntityId();
            }
        }
        // For a partner token authenticated route, keep the token in the public URL
        else if (($key === '') and ($this->ba->isPartnerAuth() === true))
        {
            $parts = explode(BasicAuth::PARTNER_CALLBACK_KEY_DELIMITER, $this->ba->getPublicKey());

            $key                         = $parts[0];
            $parameters['account_id']    = $this->ba->getAccountId();
        }
        // Else continue with the key_id flow
        else if ($key === '')
        {
            $key = $this->ba->getPublicKey();
        }

        return $this->getUrl($routeName, $parameters, $key);
    }

    public function getUrlWithPublicAuthInQueryParam($routeName, array $parameters = [])
    {
        // TODO: Deprecate this method, remove it's usage and use following directly
        return $this->getUrlWithPublicAuth($routeName, $parameters);
    }

    public function getUrlWithPublicCallbackAuth(array $parameters = [], $key = '', $route = 'payment_callback_with_key_post')
    {
        // If key is not passed, we get the same from basic auth instance
        if ($key === '')
        {
            $key = $this->ba->getPublicKey();
        }

        // For public callback routes, if key is not available(case of key less flow)
        // we use the non-key corresponding route, which would work anyway with key less flow
        // because the route has payment id.
        if (($key === '') and (in_array($route, self::$publicCallback, true) === true))
        {
            $route = str_replace('with_key_', '', $route);
        }

        $url = $this->getUrl($route, $parameters, $key);

        return $url;
    }

    public function getPublicCallbackUrlWithHash($pid, $key = '', $route = 'payment_callback_with_key_post')
    {
        $hash = $this->getHashOf($pid);

        $parameters = ['id' => $pid, 'hash' => $hash];

        return $this->getUrlWithPublicCallbackAuth($parameters, $key, $route);
    }

    public function getUrlWithAuth($relativeUrl, $key = '', $secret = '')
    {
        return $this->getSchemaHostAndAuth($key, $secret) . $relativeUrl;
    }

    protected function getSchemaHostAndAuth($key = '', $secret = '')
    {
        list($schema, $host, $port) = $this->getSchemaHostAndPort();

        $auth = '';
        if ($key !== '')
        {
            $auth = $key;
            if ($secret !== '')
            {
                $auth .= ':' . $secret;
            }

            $auth .= '@';
        }

        $url = $schema . $auth . $host;

        if (($port !== 80) and
            ($port !== 443))
        {
            $url .= ':' . $port;
        }

        return $url;
    }

    protected function getSchemaHostAndPort()
    {
        $request = \Request::getFacadeRoot();

        $schema = $request->getScheme() . '://';

        $host = $request->getHost();

        $port = (int) $request->getPort();

        return [$schema, $host, $port];
    }

    // @codingStandardsIgnoreStart
    public function getDoNotLogURLs()
    {
        $doNotLogUrls = [
            'v1/payments/create/jsonp',
            'payments/create/jsonp',
            self::$p2pRoutes['payment_create_jsonp'][1],
            'v1/payments',
            'v1/payments/create',
            'v1/payments/create/recurring',
            'v1/payments/create/redirect',
            'v1/payments/create/checkout',
            'v1/payments/create/jsonp',
            'v1/payments/create/ajax',
            'v1/payments/create/fees',
            'v1/payments/create/wallet',
            'v1/payments/create/upi'
        ];

        return $doNotLogUrls;
    }

    public function addRouteGroups($groups)
    {
        foreach ($groups as $group)
        {
            foreach (self::$$group as $routeName)
            {
                $this->addRoute($routeName);
            }
        }
    }

    protected function addRoute($name)
    {
        $info = self::$p2pRoutes[$name];

        $methods = explode(',', $info[0]);
        $uri     = $info[1];
        $action  = $info[2];

        // For 'any' we have to register all the methods, there is no http verb called 'any'.
        if ($methods === ['any'])
        {
            $methods = Router::$verbs;
        }

        $route = $this->router->match($methods, $uri, ['as' => $name, 'uses' => $action]);

        if (strpos($uri, '{path?}') !== false)
        {
            $route->where(['path' => '.*']);
        }

        // We add the web middleware group, conditionally to routes which require cookie / session access.
        if (in_array($name, self::$session, true) === true)
        {
            $route->middleware('web');
        }
    }

    public function getApiRouteInCategory($category)
    {
        return array_intersect_key(self::$p2pRoutes, array_flip(self::$$category));
    }

    public static function getApiRoute($name)
    {
        return self::$p2pRoutes[$name];
    }

    /**
     * Returns an array of feature names to which the current route is mapped under
     *
     * @param $route
     *
     * @return array
     */
    public static function getFeaturesForRoute($route) : array
    {
        $features = self::$routeNameToFeaturesMap;

        return $features[$route] ?? [];
    }

    /**
     * Returns the array of features, one of which is required to
     * access the current route.
     *
     * @return array
     */
    public function getCurrentRouteFeatures(): array
    {
        $currentRoute = $this->getCurrentRouteName();
        //
        // A route can belong to multiple features
        // This fetches an array of all features mapped to the route
        //
        return self::getFeaturesForRoute($currentRoute);
    }
}
