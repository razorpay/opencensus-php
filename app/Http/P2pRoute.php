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
        Requests::P2P_CUSTOMER_INITIATE_VERIFICATION =>
            [
                'post',
                'customers/verification/initiate',
                'DeviceController@initiateVerification',
            ],
        Requests::P2P_CUSTOMER_VERIFICATION =>
            [
                'post',
                'customers/verification/{token}',
                'DeviceController@verification'
            ],
        Requests::P2P_CUSTOMER_INITIATE_GET_TOKEN =>
            [
                'post',
                'customer/token/initiate',
                'DeviceController@initiateGetToken'
            ],
        Requests::P2P_CUSTOMER_GET_TOKEN =>
            [
                'post',
                'customer/token',
                'DeviceController@getToken'
            ],
        Requests::P2P_CUSTOMER_DEREGISTER =>
            [
                'delete',
                'customer/deregister',
                'DeviceController@deregister'
            ],

        /*************** Bank Account **************/
        Requests::P2P_BANKS_FETCH_ALL =>
            [
                'get',
                'banks',
                'BankAccountController@fetchBanks'
            ],
        Requests::P2P_CUSTOMER_BA_INITIATE_RETRIEVE =>
            [
                'post',
                'customer/bank_accounts/retrieve/{bank_id}/initiate',
                'BankAccountController@initiateRetrieve'
            ],
        Requests::P2P_CUSTOMER_BA_RETRIEVE =>
            [
                'post',
                'customer/bank_accounts/retrieve/{bank_id}',
                'BankAccountController@retrieve'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH_ALL =>
            [
                'get',
                'customer/bank_accounts',
                'BankAccountController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH =>
            [
                'get',
                'customer/bank_accounts/{ba_id}',
                'BankAccountController@fetch'
            ],
        Requests::P2P_CUSTOMER_BA_INITIATE_SET_UPI_PIN =>
            [
                'post',
                'customer/bank_accounts/{ba_id}/upipin/initiate',
                'BankAccountController@initiateSetUpiPin'
            ],
        Requests::P2P_CUSTOMER_BA_SET_UPI_PIN =>
            [
                'post',
                'customer/bank_accounts/{ba_id}/upi_pin',
                'BankAccountController@setUpiPin'
            ],
        Requests::P2P_CUSTOMER_BA_INITIATE_FETCH_BALANCE =>
            [
                'post',
                'customer/bank_accounts/{ba_id}/balance/initiate',
                'BankAccountController@initiateFetchBalance'
            ],
        Requests::P2P_CUSTOMER_BA_FETCH_BALANCE =>
            [
                'post',
                'customer/bank_accounts/{ba_id}/balance/',
                'BankAccountController@fetchBalance'
            ],

        /****************** VPA *******************/
        Requests::P2P_HANDLES_FETCH_ALL =>
            [
                'get',
                'handles',
                'VpaController@fetchHandles'
            ],
        Requests::P2P_CUSTOMER_VPA_INITIATE_CREATE =>
            [
                'post',
                'customer/vpa/initiate',
                'VpaController@initiateCreate'
            ],
        Requests::P2P_CUSTOMER_VPA_CREATE =>
            [
                'post',
                'customer/vpa',
                'VpaController@create'
            ],
        Requests::P2P_CUSTOMER_VPA_FETCH_ALL =>
            [
                'get',
                'customer/vpa',
                'VpaController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_VPA_FETCH =>
            [
                'get',
                'customer/vpa/{vpa_id}',
                'VpaController@fetch'
            ],
        Requests::P2P_CUSTOMER_VPA_ASSIGN_BANK_ACCOUNT =>
            [
                'post',
                'customer/vpa/{vpa_id}/assign/{ba_id}',
                'VpaController@assignBankAccount'
            ],
        Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY =>
            [
                'post',
                'customer/vpa/available',
                'VpaController@checkAvailability'
            ],
        Requests::P2P_CUSTOMER_VPA_DELETE =>
            [
                'delete',
                'customer/vpa/{vpa_id}',
                'VpaController@delete'
            ],

        /************* Beneficiaries **************/
        Requests::P2P_CUSTOMER_BENEFICIARIES =>
            [
                'post',
                'customer/beneficiaries',
                'BeneficiaryController@create'
            ],
        Requests::P2P_CUSTOMER_BENEFICIARIES_VALIDATE =>
            [
                'post',
                'customer/beneficiaries/validate',
                'BeneficiaryController@validateBeneficiary'
            ],
        Requests::P2P_CUSTOMER_BENEFICIARIES_FETCH_ALL =>
            [
                'get',
                'customer/beneficiaries',
                'BeneficiaryController@fetchAll'
            ],

        /************* Transactions **************/
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_PAY =>
            [
                'post',
                'customer/transactions/pay/initiate',
                'TransactionController@initiatePay'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_COLLECT =>
            [
                'post',
                'customer/transactions/collect/initiate',
                'TransactionController@initiateCollect'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH_ALL =>
            [
                'get',
                'customer/transactions/',
                'TransactionController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH =>
            [
                'get',
                'customer/transactions/{transaction_id}',
                'TransactionController@fetch'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_AUTHORIZE =>
            [
                'get',
                'customer/transactions/{transaction_id}/authorize/initiate',
                'TransactionController@initiateAuthorize'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE =>
            [
                'post',
                'customer/transactions/{transaction_id}/authorize',
                'TransactionController@authorizeTransaction'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_REJECT_COLLECT =>
            [
                'post',
                'customer/transactions/{transaction_id}/reject',
                'TransactionController@reject'
            ],
    ];

    public static $public = [
        Requests::P2P_HANDLES_FETCH_ALL,
        Requests::P2P_BANKS_FETCH_ALL,
        Requests::P2P_CUSTOMER_INITIATE_VERIFICATION,
        Requests::P2P_CUSTOMER_VERIFICATION,
    ];

    public static $device = [
        Requests::P2P_CUSTOMER_INITIATE_GET_TOKEN,
        Requests::P2P_CUSTOMER_GET_TOKEN,
        Requests::P2P_CUSTOMER_DEREGISTER,

        Requests::P2P_CUSTOMER_BA_INITIATE_RETRIEVE,
        Requests::P2P_CUSTOMER_BA_RETRIEVE,
        Requests::P2P_CUSTOMER_BA_FETCH_ALL,
        Requests::P2P_CUSTOMER_BA_FETCH,
        Requests::P2P_CUSTOMER_BA_INITIATE_SET_UPI_PIN,
        Requests::P2P_CUSTOMER_BA_SET_UPI_PIN,
        Requests::P2P_CUSTOMER_BA_INITIATE_FETCH_BALANCE,
        Requests::P2P_CUSTOMER_BA_FETCH_BALANCE,

        Requests::P2P_CUSTOMER_VPA_INITIATE_CREATE,
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
