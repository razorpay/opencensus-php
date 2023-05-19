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
        Requests::P2P_TURBO_PREFERENCES =>
            [
                'post',
                '/turbo/preferences',
                'PreferencesController@getPreferences'
            ],
        Requests::P2P_TURBO_GATEWAY_CONFIG =>
            [
                'post',
                '/turbo/{gateway_name}/config',
                'ClientController@getGatewayConfig'
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
                'customer/vpa/{vpa_id}/assign',
                'VpaController@assignBankAccount'
            ],
        Requests::P2P_CUSTOMER_VPA_SET_DEFAULT =>
            [
                'post',
                'customer/vpa/{vpa_id}/default',
                'VpaController@setDefault'
            ],
        Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY =>
            [
                'post',
                'customer/vpa/available',
                'VpaController@checkAvailability'
            ],
        Requests::P2P_CUSTOMER_VPA_INITIATE_CHECK_AVAILABILITY =>
            [
                'post',
                'customer/vpa/available/initiate',
                'VpaController@initiateCheckAvailability'
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
        Requests::P2P_CUSTOMER_BENEFICIARIES_HANDLE =>
            [
                'post',
                'customer/beneficiaries/handle',
                'BeneficiaryController@handle'
            ],

        /************* Blacklist **************/
        Requests::P2P_MERCHANT_BLACKLIST_ADD_BATCH =>
            [
                'post',
                'merchant/blacklist/add_batch',
                'BlackListController@create'
            ],
        Requests::P2P_MERCHANT_BLACKLIST_REMOVE_BATCH =>
            [
                'post',
                'merchant/blacklist/remove_batch',
                'BlackListController@remove'
            ],
        Requests::P2P_MERCHANT_BLACKLIST_FETCH_ALL =>
            [
                'get',
                'merchant/blacklist',
                'BlackListController@fetchAll'
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
                'post',
                'customer/transactions/{transaction_id}/authorize/initiate',
                'TransactionController@initiateAuthorize'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE =>
            [
                'post',
                'customer/transactions/{transaction_id}/authorize',
                'TransactionController@authorizeTransaction'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_REJECT =>
            [
                'post',
                'customer/transactions/{transaction_id}/reject/initiate',
                'TransactionController@initiateReject'
            ],
        Requests::P2P_CUSTOMER_TRANSACTIONS_REJECT            =>
            [
                'post',
                'customer/transactions/{transaction_id}/reject',
                'TransactionController@reject'
            ],
        Requests::P2P_CUSTOMER_CONCERNS_TRANSACTION_RAISE     =>
            [
                'post',
                'customer/concerns/transactions/{transaction_id}',
                'TransactionController@raiseConcern'
            ],
        Requests::P2P_CUSTOMER_CONCERNS_TRANSACTION_STATUS    =>
            [
                'post',
                'customer/concerns/transactions/{transaction_id}/status',
                'TransactionController@concernStatus'
            ],
        Requests::P2P_CUSTOMER_CONCERNS_TRANSACTION_FETCH_ALL =>
            [
                'get',
                'customer/concerns/transactions',
                'TransactionController@fetchAllConcerns'
            ],

        /*************** Callbacks **************/
        Requests::P2P_GATEWAY_CALLBACK                        =>
            [
                'post',
                'callback/{gateway}',
                'UpiController@gatewayCallback'
            ],

        Requests::P2P_TURBO_GATEWAY_CALLBACK                  =>
            [
                'post',
                'turbo/{gateway}/callback',
                'TurboController@turboGatewayCallback'
            ],

        /*************** Merchant **************/
        Requests::P2P_MERCHANT_BENEFICIARY_VALIDATE           =>
            [
                'post',
                'merchant/beneficiaries/validate',
                'BeneficiaryController@validateBeneficiary'
            ],
        Requests::P2P_MERCHANT_DEVICE_UPDATE_WITH_ACTION       =>
            [
                'post',
                'merchant/devices/{device_id}/{action}',
                'DeviceController@updateWithAction'
            ],
        Requests::P2P_MERCHANT_DEVICES_FETCH_ALL =>
            [
                'get',
                'merchant/devices',
                'DeviceController@fetchAll'
            ],
        Requests::P2P_MERCHANT_VPA_FETCH_ALL =>
            [
                'get',
                'merchant/vpa',
                'VpaController@fetchAll'
            ],

        /************* Mandates **************/
        Requests::P2P_CUSTOMER_MANDATE_FETCH =>
            [
                'get',
                'customer/mandates/{mandate_id}',
                'MandateController@fetch'
            ],
        Requests::P2P_CUSTOMER_MANDATE_FETCH_ALL =>
            [
                'get',
                'customer/mandates',
                'MandateController@fetchAll'
            ],
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_AUTHORIZE =>
            [
                'post',
                'customer/mandates/{mandate_id}/authorize/initiate',
                'MandateController@initiateAuthorize'
            ],
        Requests::P2P_CUSTOMER_MANDATE_AUTHORIZE =>
            [
                'post',
                'customer/mandates/{mandate_id}/authorize',
                'MandateController@authorizeMandate'
            ],
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_REJECT =>
            [
                'post',
                'customer/mandates/{mandate_id}/reject/initiate',
                'MandateController@initiateReject'
            ],
        Requests::P2P_CUSTOMER_MANDATE_REJECT =>
            [
                'post',
                'customer/mandates/{mandate_id}/reject',
                'MandateController@rejectMandate'
            ],
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_PAUSE =>
            [
                'post',
                'customer/mandates/{mandate_id}/pause/initiate',
                'MandateController@initiatePause'
            ],
        Requests::P2P_CUSTOMER_MANDATE_PAUSE =>
            [
                'post',
                'customer/mandates/{mandate_id}/pause',
                'MandateController@pauseMandate'
            ],
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_UNPAUSE =>
            [
                'post',
                'customer/mandates/{mandate_id}/unpause/initiate',
                'MandateController@initiateUnpause'
            ],
        Requests::P2P_CUSTOMER_MANDATE_UNPAUSE =>
            [
                'post',
                'customer/mandates/{mandate_id}/unpause',
                'MandateController@unpauseMandate'
            ],
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_REVOKE =>
            [
                'post',
                'customer/mandates/{mandate_id}/revoke/initiate',
                'MandateController@initiateRevoke'
            ],
        Requests::P2P_CUSTOMER_MANDATE_REVOKE =>
            [
                'post',
                'customer/mandates/{mandate_id}/revoke',
                'MandateController@revokeMandate'
            ],
    ];

    public static $public = [
        Requests::P2P_HANDLES_FETCH_ALL,
        Requests::P2P_BANKS_FETCH_ALL,
        Requests::P2P_CUSTOMER_INITIATE_VERIFICATION,
        Requests::P2P_CUSTOMER_VERIFICATION,
        Requests::P2P_TURBO_PREFERENCES,
        Requests::P2P_TURBO_GATEWAY_CONFIG,
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
        Requests::P2P_CUSTOMER_VPA_SET_DEFAULT,
        Requests::P2P_CUSTOMER_VPA_CHECK_AVAILABILITY,
        Requests::P2P_CUSTOMER_VPA_INITIATE_CHECK_AVAILABILITY,
        Requests::P2P_CUSTOMER_VPA_DELETE,

        Requests::P2P_CUSTOMER_BENEFICIARIES,
        Requests::P2P_CUSTOMER_BENEFICIARIES_VALIDATE,
        Requests::P2P_CUSTOMER_BENEFICIARIES_FETCH_ALL,
        Requests::P2P_CUSTOMER_BENEFICIARIES_HANDLE,

        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_PAY,
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_COLLECT,
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH_ALL,
        Requests::P2P_CUSTOMER_TRANSACTIONS_FETCH,
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_AUTHORIZE,
        Requests::P2P_CUSTOMER_TRANSACTIONS_AUTHORIZE,
        Requests::P2P_CUSTOMER_TRANSACTIONS_INITIATE_REJECT,
        Requests::P2P_CUSTOMER_TRANSACTIONS_REJECT,
        Requests::P2P_CUSTOMER_CONCERNS_TRANSACTION_RAISE,
        Requests::P2P_CUSTOMER_CONCERNS_TRANSACTION_FETCH_ALL,
        Requests::P2P_CUSTOMER_CONCERNS_TRANSACTION_STATUS,
        Requests::P2P_CUSTOMER_MANDATE_FETCH,
        Requests::P2P_CUSTOMER_MANDATE_FETCH_ALL,
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_AUTHORIZE,
        Requests::P2P_CUSTOMER_MANDATE_AUTHORIZE,
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_REJECT,
        Requests::P2P_CUSTOMER_MANDATE_REJECT,
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_PAUSE,
        Requests::P2P_CUSTOMER_MANDATE_PAUSE,
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_UNPAUSE,
        Requests::P2P_CUSTOMER_MANDATE_UNPAUSE,
        Requests::P2P_CUSTOMER_MANDATE_INITIATE_REVOKE,
        Requests::P2P_CUSTOMER_MANDATE_REVOKE,
    ];

    public static $direct = [
        Requests::P2P_GATEWAY_CALLBACK,
        Requests::P2P_TURBO_GATEWAY_CALLBACK,
    ];

    public static $private = [
        Requests::P2P_MERCHANT_BENEFICIARY_VALIDATE,
        Requests::P2P_MERCHANT_DEVICE_UPDATE_WITH_ACTION,
        Requests::P2P_MERCHANT_DEVICES_FETCH_ALL,
        Requests::P2P_MERCHANT_VPA_FETCH_ALL,
        Requests::P2P_MERCHANT_BLACKLIST_ADD_BATCH,
        Requests::P2P_MERCHANT_BLACKLIST_REMOVE_BATCH,
        Requests::P2P_MERCHANT_BLACKLIST_FETCH_ALL,
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


        // Attaching error handler to p2p routes
        $route->middleware('error_handler_setter_for_php_laravel_upgrade');

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
