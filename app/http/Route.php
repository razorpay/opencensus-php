<?php

namespace Http;

final class Route
{
    /*
     | The order in which routes are defined is very important.
     | Whenever the order of routes is changed,
     | make sure to run the full test suite
     */

    protected static $apiRoutes = array(
        'payment_create'                    => ['post',     'payments',                                 'PaymentController@postCreatePayment'                   ],
        'payment_create_jsonp'              => ['get',      'payments/create/jsonp',                    'PaymentController@getJSONP'                            ],
        'payment_callback'                  => ['post',     'payments/{id}/callback',                   'PaymentController@postCallback'                        ],
        'payment_refund'                    => ['post',     'payments/{id}/refund',                     'PaymentController@postRefund'                          ],
        'payment_capture'                   => ['post',     'payments/{id}/capture',                    'PaymentController@postCapture'                         ],
        'payment_verify'                    => ['get',      'payments/{id}/verify',                     'PaymentController@getVerify'                           ],
        'payment_fetch_by_id'               => ['get',      'payments/{id}',                            'PaymentController@getPayment'                          ],
        'payment_fetch_multiple'            => ['get',      'payments',                                 'PaymentController@getPayments'                         ],
        'payment_fetch_refunds'             => ['get',      'payments/{id}/refunds',                    'PaymentController@getRefundsForPayment'                ],
        'payment_fetch_refund_by_id'        => ['get',      'payments/{paymentId}/refunds/{rfndId}',    'PaymentController@getRefundByRefundAndPaymentId'       ],
        'payment_auth_expire'               => ['post',     'payments/auth/expire',                     'PaymentController@postAuthExpire'                      ],
        'payment_timeout'                   => ['post',     'payments/timeout',                         'PaymentController@postTimeout'                         ],
        'refund_fetch_by_id'                => ['get',      'refunds/{id}',                             'PaymentController@getRefund'                           ],
        'refund_fetch_multiple'             => ['get',      'refunds',                                  'PaymentController@getRefunds'                          ],
        'merchant_public_get_banks'         => ['get',      'banks',                                    'MerchantController@getBanksPublic'                     ],
        'merchant_public_get_banks2'        => ['get',      'merchant/banks',                           'MerchantController@getBanksPublic'                     ],
        'merchant_get_banks'                => ['get',      'merchants/{id}/banks',                     'MerchantController@getBanks'                           ],
        'merchant_set_banks'                => ['post',     'merchants/{id}/banks',                     'MerchantController@setBanks'                           ],
        'merchant_create'                   => ['post',     'merchants',                                'MerchantController@postCreateMerchant'                 ],
        'merchant_fetch'                    => ['get',      'merchants/{id}',                           'MerchantController@getMerchant'                        ],
        'merchant_fetch_multiple'           => ['get',      'merchants',                                'MerchantController@getMerchants'                       ],
        'merchant_create_key'               => ['post',     'merchants/{id}/keys',                      'MerchantController@postCreateKeys'                     ],
        'merchant_fetch_keys'               => ['get',      'merchants/{id}/keys',                      'MerchantController@getKeys'                            ],
        'merchant_replace_key'              => ['put',      'merchants/{merchantId}/keys/{keyId}',      'MerchantController@putKeys'                            ],
        'merchant_assign_pricing'           => ['post',     'merchants/{id}/pricing',                   'MerchantController@postAssignPricingPlan'              ],
        'merchant_get_pricing'              => ['get',      'merchants/{id}/pricing',                   'MerchantController@getPricingPlan'                     ],
        'merchant_add_bank_account'         => ['post',     'merchants/{id}/bank_account',              'MerchantController@postBankAccount'                    ],
        'merchant_fetch_bank_account'       => ['get',      'merchants/{id}/bank_account',              'MerchantController@getBankAccount'                     ],
        'merchant_create_terminal'          => ['post',     'merchants/{id}/terminals',                 'MerchantController@postCreateTerminal'                 ],
        'merchant_get_terminals'            => ['get',      'merchants/{id}/terminals',                 'MerchantController@getTerminals'                       ],
        'merchant_get_terminal'             => ['get',      'merchants/{mid}/terminals/{tid}',          'MerchantController@getTermianl'                        ],
        'merchant_delete_terminal'          => ['delete',   'merchants{mid}/terminals/{tid}',           'MerchantController@deleteTerminal'                     ],
        'merchant_activate'                 => ['post',     'merchants/{id}/activate',                  'MerchantController@postActivate'                       ],
        'merchant_live_enable'              => ['post',     'merchants/{id}/live/enable',               'MerchantController@postLiveEnable'                     ],
        'merchant_live_disable'             => ['post',     'merchants/{id}/live/disable',              'MerchantController@postLiveDisable'                    ],
        'pricing_create_plan'               => ['post',     'pricing',                                  'PricingController@postCreatePricingPlan'               ],
        'pricing_get_plans'                 => ['get',      'pricing',                                  'PricingController@getPricingPlans'                     ],
        'pricing_get_merchant_plans'        => ['get',      'pricing/merchants',                        'PricingController@getMerchantPricingPlans'             ],
        'pricing_get_gateway_plans'         => ['get',      'pricing/gateways',                         'PricingController@getGatewayPricingPlans'              ],
        'pricing_get_plan'                  => ['get',      'pricing/{id}',                             'PricingController@getPricingPlan'                      ],
        'pricing_get_plan_rule'             => ['get',      'pricing/{planId}/rule/{ruleId}',           'PricingController@getPricingPlanRule'                  ],
        'pricing_add_plan_rule'             => ['post',     'pricing/{id}/rule',                        'PricingController@postAddPricingPlanRule'              ],
        'transaction_fetch_by_id'           => ['get',      'transactions/{id}',                        'SettlementController@getTransactionRecord'             ],
        'transaction_fetch_multiple'        => ['get',      'transactions',                             'SettlementController@getTransactionRecords'            ],
        'setl_fetch_by_id'                  => ['get',      'settlements/{id}',                         'SettlementController@getSettlement'                    ],
        'setl_fetch_multiple'               => ['get',      'settlements',                              'SettlementController@getSettlements'                   ],
        'hdfc_mpr_reconcile'                => ['post',     'gateway/mpr/reconcile',                    'SettlementController@postGatewayMprReconcile'          ],
        'hdfc_mpr_generate'                 => ['post',     'gateway/mpr/generate',                     'SettlementController@postGatewayMprGenerate'           ],
        'setl_delete_file'                  => ['delete',   'settlements/file/{setlFileType}',          'SettlementController@deleteSettlementFile'             ],
        'setl_initiate'                     => ['post',     'settlements/initiate/{channel?}',          'SettlementController@postSettlementInitiate'           ],
        'setl_reconcile_generate'           => ['post',     'settlements/reconcile/generate',           'SettlementController@postSettlementReconcileGenerate'  ],
        'setl_reconcile'                    => ['post',     'settlements/reconcile',                    'SettlementController@postSettlementReconcile'          ],
        'setl_return_generate'              => ['post',     'settlements/return/generate',              'SettlementController@postSettlementReturnGenerate'     ],
        'setl_return'                       => ['post',     'settlements/return',                       'SettlementController@postSettlementReturn'             ],
        'adj_fetch_by_id'                   => ['get',      'adjustments/{id}',                         'AdjustmentController@getAdjustment'                    ],
        'adj_fetch_multiple'                => ['get',      'adjustments',                              'AdjustmentController@getAdjustments'                   ],
        'adj_add'                           => ['post',     'adjustments',                              'AdjustmentController@postAdjustment'                   ],
        'mockhdfc_enroll'                   => ['post',     'gateway/mockhdfc/enroll',                  'MockHdfcController@enroll'                             ],
        'mockhdfc_payment'                  => ['post',     'gateway/mockhdfc/payment',                 'MockHdfcController@payment'                            ],
        'mockhdfc_auth_enrolled'            => ['post',     'gateway/mockhdfc/auth_enrolled',           'MockHdfcController@authEnrolled'                       ],
        'mockhdfc_3dsecure'                 => ['post',     'gateway/3dsecure',                         'MockHdfcController@post3dSecure'                       ],
        'mockatom_choose_bank'              => ['get',      'gateway/mockanb',                          'MockHdfcController@getAtomChooseBank'                  ],
        'mockatom_init_netbanking'          => ['post',     'gateway/mockanb',                          'MockHdfcController@postAtomInitNetbanking'             ],
        'mockatom_rzp_bank'                 => ['post',     'gateway/mockanb/rzp_bank',                 'MockHdfcController@postAtomRzpBankPage'                ],
        'mockatom_rzp_bank_submit'          => ['post',     'gateway/mockanb/rzp_bank/submit',          'MockHdfcController@postAtomRzpBankSubmit'              ],
        );

    public static $public = array(
        'payment_create',
        'payment_create_jsonp',
        'payment_callback',
        'merchant_public_get_banks',
        'merchant_public_get_banks2',
        'mockatom_choose_bank',
        'mockatom_rzp_bank',
        'mockatom_rzp_bank_submit',
        );

    public static $private = array(
        'payment_refund',
        'payment_capture',
        'payment_fetch_by_id',
        'payment_fetch_multiple',
        'payment_fetch_refunds',
        'payment_fetch_refund_by_id',
        );

    public static $internal = array(
        'merchant_create',
        'merchant_fetch',
        'merchant_fetch_multiple',
        'merchant_create_key',
        'merchant_fetch_keys',
        'merchant_replace_key',
        'merchant_assign_pricing',
        'merchant_get_pricing',
        'merchant_add_bank_account',
        'merchant_fetch_bank_account',
        'merchant_create_terminal',
        'merchant_delete_terminal',
        'merchant_get_terminals',
        'merchant_activate',
        'merchant_live_enable',
        'merchant_live_disable',
        'merchant_get_banks',
        'merchant_set_banks',
        'pricing_create_plan',
        'pricing_get_plans',
        'pricing_get_merchant_plans',
        'pricing_get_gateway_plans',
        'pricing_add_plan_rule',
        'pricing_get_plan',
        'pricing_get_plan_rule',
        'setl_initiate',
        'setl_reconcile',
        'setl_reconcile_generate',
        'setl_return_generate',
        'setl_return',
        'setl_delete_file',
        'payment_timeout',
        'hdfc_mpr_reconcile',
        'hdfc_mpr_generate',
        'mockhdfc_enroll',
        'mockhdfc_auth_enrolled',
        'mockhdfc_payment',
        'mockatom_init_netbanking',
        );

    public static $proxy = array(
        'payment_verify',
        'refund_fetch_by_id',
        'refund_fetch_multiple',
        'transaction_fetch_by_id',
        'transaction_fetch_multiple',
        'setl_fetch_by_id',
        'setl_fetch_multiple',
        'adj_fetch_by_id',
        'adj_fetch_multiple',
        'adj_add',
    );

    public static $internalApps = array(
            'dashboard' => array('*'),

            'mock_gateways' => array(
                'mockhdfc_enroll',
                'mockhdfc_auth_enrolled',
                'mockhdfc_payment',
                'mockatom_init_netbanking'),

            'cron' => array(
                'hdfc_mpr_generate',
                'setl_initiate',
                'setl_reconcile_generate',
                'setl_return_generate',
                'payment_timeout'),

            'mailgun' => array(
                'hdfc_mpr_reconcile'),
        );

    protected static $router;

    public static function setRouter($router)
    {
        self::$router = $router;
    }

    public static function getUrl($routeName, $parameters = array(), $key = '', $secret = '')
    {
        if ($secret === '')
        {
            // It's a public auth.
            $parameters['key_id'] = $key;
            $key = '';
        }

        $urlSegment = \URL::route($routeName, $parameters, false);

        $url = self::getSchemaHostAndAuth($key, $secret) . $urlSegment;

        return $url;
    }

    public static function getUrlWithAuth($relativeUrl, $key = '', $secret = '')
    {
        return self::getSchemaHostAndAuth($key, $secret) . $relativeUrl;
    }

    protected static function getSchemaHostAndAuth($key = '', $secret = '')
    {
        $request = \Request::getFacadeRoot();

        $schema = $request->getScheme().'://';
        $host = $request->getHost();

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

        return $url;
    }

    public static function getDoNotLogURLs()
    {
        $doNotLogUrls = array(
            'v1/payments/create/jsonp',
            'payments/create/jsonp',
            self::$apiRoutes['payment_create_jsonp'][1]);

        return $doNotLogUrls;
    }

    public static function isJsonpRoute($path)
    {
        $jsonpRoute = array(
            'v1/payments/create/jsonp',
            'v1/merchant/banks',
            'v1/banks');

        return in_array($path, $jsonpRoute);
    }

    protected static function addRoutes($type)
    {
        foreach (self::$$type as $routeName)
        {
            self::addRoute($routeName);
        }
    }

    protected static function addRoute($name)
    {
        $info = self::$apiRoutes[$name];

        $method = $info[0];
        $uri = $info[1];
        $action = $info[2];

        $router = self::$router;

        $router->$method($uri, array('as' => $name, 'uses' => $action));
    }

    protected static function add3dSecureRoute()
    {
        self::addRoute('mockhdfc_3dsecure');
    }

    public static function defineApiRoutes()
    {
        $router = self::$router;

        self::add3dSecureRoute();

        $router->group(array('prefix' => 'v1'), function() use ($router)
        {
            //
            // First define internal routes and then private and finally public
            // If by mistake a route is defined twice in say internal and public,
            // then it will go into internal app auth and will not expose the route.
            // This must not happen though.
            //
            self::addFilterOnRouteGroups($router, 'auth.app', 'internal');
            self::addFilterOnRouteGroups($router, 'auth.private', 'private');
            self::addFilterOnRouteGroups($router, 'auth.public', 'public');
            self::addFilterOnRouteGroups($router, 'auth.proxy', 'proxy');
        });

        $router->get('/', function()
        {
            $response['message'] = "Welcome to Razorpay API.";
            return ApiResponse::json($response);
        });

        $router->any('{all}', function($uri)
        {
            return ApiResponse::routeNotFound();
        })->where('all', '.*');
    }

    protected static function addFilterOnRouteGroups($router, $filter, $routeGroup)
    {
        $router->group(array('before' => $filter), function() use ($routeGroup)
        {
            self::addRoutes($routeGroup);
        });
    }

    public static function getApiRoutes()
    {
        return self::$apiRoutes;
    }

    public static function getApiRouteInCategory($category)
    {
        return array_intersect_key(self::$apiRoutes, array_flip(self::$$category));
    }

    public static function getApiRoute($name)
    {
        return self::$apiRoutes[$name];
    }

    public static function getApiRouteUrl($name)
    {
        return self::getApiRoute($name)[1];
    }

}