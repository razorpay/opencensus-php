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
        'checkout'                          => ['get',      'checkout',                                 'MerchantController@getCheckout'                                ],
        'merchant_methods'                  => ['get',      'methods',                                  'MerchantController@getPaymentMethods'                          ],
        'payment_create'                    => ['post',     'payments',                                 'PaymentCreateController@postCreatePayment'                     ],
        'payment_create_checkout'           => ['post',     'payments/create/checkout',                 'PaymentCreateController@postCreatePaymentCheckoutCallback'     ],
        'payment_create_jsonp'              => ['get',      'payments/create/jsonp',                    'PaymentCreateController@getJSONP'                              ],
        'payment_callback_post'             => ['post',     'payments/{id}/callback/{hash}',            'PaymentCreateController@postCallback'                          ],
        'payment_callback_get'              => ['get',      'payments/{id}/callback/{hash}',            'PaymentCreateController@postCallback'                          ],
        'payment_callback_with_key_post'    => ['post',     'payments/{id}/callback/{hash}/{key}',      'PaymentCreateController@postCallback'                          ],
        'payment_callback_with_key_get'     => ['get',      'payments/{id}/callback/{hash}/{key}',      'PaymentCreateController@postCallback'                          ],
        'payment_refund'                    => ['post',     'payments/{id}/refund',                     'PaymentController@postRefund'                                  ],
        'payment_capture'                   => ['post',     'payments/{id}/capture',                    'PaymentController@postCapture'                                 ],
        'payment_verify'                    => ['get',      'payments/{id}/verify',                     'PaymentController@getVerify'                                   ],
        'payment_cancel'                    => ['get',      'payments/{id}/cancel',                     'PaymentController@postCancel'                                  ],
        'payment_authorize_failed'          => ['post',     'payments/{id}/authorize_failed',           'PaymentController@postAuthorizeFailedPayment'                  ],
        'payment_authorize_refund'          => ['post',     'payments/{id}/authorize_refund',           'PaymentController@postRefundAuthorized'                        ],
        'payment_fetch_by_id'               => ['get',      'payments/{id}',                            'PaymentController@getPayment'                                  ],
        'payment_fetch_multiple'            => ['get',      'payments',                                 'PaymentController@getPayments'                                 ],
        'payment_fetch_refunds'             => ['get',      'payments/{id}/refunds',                    'PaymentController@getRefundsForPayment'                        ],
        'payment_fetch_refund_by_id'        => ['get',      'payments/{paymentId}/refunds/{rfndId}',    'PaymentController@getRefundByRefundAndPaymentId'               ],
        'payment_auth_notify'               => ['get',      'payments/auth/notify',                     'PaymentController@getAuthNotify',                              ],
        'payment_auth_expire'               => ['post',     'payments/auth/expire',                     'PaymentController@postAuthExpire'                              ],
        'payment_timeout'                   => ['post',     'payments/timeout',                         'PaymentController@postTimeout'                                 ],
        'payment_auto_capture'              => ['post',     'payments/autocapture',                     'PaymentController@postAutoCapture'                             ],
        'payment_auto_capture_email'        => ['get',      'payments/autocapture/email',               'PaymentController@getAutoCaptureEmail'                         ],
        'payment_verify_all'                => ['get',      'payments/verify/all',                      'PaymentController@getVerifyPayments'                           ],
        'refund_fetch_by_id'                => ['get',      'refunds/{id}',                             'PaymentController@getRefund'                                   ],
        'refund_fetch_multiple'             => ['get',      'refunds',                                  'PaymentController@getRefunds'                                  ],
        'refund_hdfcnb'                     => ['get',      'refunds/hdfcnb/generate',                  'PaymentController@generateHdfcNetbankingRefunds'               ],
        'card_fetch_by_id'                  => ['get',      'cards/{id}',                               'PaymentController@getCard'                                     ],
        'card_fetch_multiple'               => ['get',      'cards',                                    'PaymentController@getCards'                                    ],
        'iin_fetch_by_iin'                  => ['get',      'iins/{id}',                                'CardController@getIin'                                         ],
        'iin_fetch_multiple'                => ['get',      'iins',                                     'CardController@getIins'                                        ],
        'iin_add'                           => ['post',     'iins',                                     'CardController@postIin'                                        ],
        'merchant_public_get_banks'         => ['get',      'banks',                                    'MerchantController@getBanksPublic'                             ],
        'merchant_secret'                   => ['get',      'keys/{id}/secret',                         'MerchantController@getKeySecret'                               ],
        'merchant_get_banks'                => ['get',      'merchants/{id}/banks',                     'MerchantController@getBanks'                                   ],
        'merchant_set_banks'                => ['post',     'merchants/{id}/banks',                     'MerchantController@setBanks'                                   ],
        'merchant_set_all_banks'            => ['put',      'merchants/banks',                          'MerchantController@putBanksForAllMerchants'                    ],
        'merchant_daily_report'             => ['post',     'merchants/report',                         'MerchantController@sendDailyReport'                            ],
        'merchant_create'                   => ['post',     'merchants',                                'MerchantController@postCreateMerchant'                         ],
        'merchant_fetch'                    => ['get',      'merchants/{id}',                           'MerchantController@getMerchant'                                ],
        'merchant_edit'                     => ['put',      'merchants/{id}',                           'MerchantController@putMerchant'                                ],
        'merchant_edit_email'               => ['put',      'merchants/{id}/email',                     'MerchantController@putMerchantEmail'                                ],
        'merchant_fetch_multiple'           => ['get',      'merchants',                                'MerchantController@getMerchants'                               ],
        'merchant_create_key'               => ['post',     'merchants/{id}/keys',                      'MerchantController@postCreateKeys'                             ],
        'merchant_fetch_keys'               => ['get',      'merchants/{id}/keys',                      'MerchantController@getKeys'                                    ],
        'merchant_replace_key'              => ['put',      'merchants/{merchantId}/keys/{keyId}',      'MerchantController@putKeys'                                    ],
        'merchant_assign_pricing'           => ['post',     'merchants/{id}/pricing',                   'MerchantController@postAssignPricingPlan'                      ],
        'merchant_get_pricing'              => ['get',      'merchants/{id}/pricing',                   'MerchantController@getPricingPlan'                             ],
        'merchant_add_bank_account'         => ['post',     'merchants/{id}/bank_account',              'MerchantController@postBankAccount'                            ],
        'merchant_fetch_bank_account'       => ['get',      'merchants/{id}/bank_account',              'MerchantController@getBankAccount'                             ],
        'merchant_create_terminal'          => ['post',     'merchants/{id}/terminals',                 'MerchantController@postCreateTerminal'                         ],
        'merchant_get_terminals'            => ['get',      'merchants/{id}/terminals',                 'MerchantController@getTerminals'                               ],
        'merchant_get_terminal'             => ['get',      'merchants/{mid}/terminals/{tid}',          'MerchantController@getTerminal'                                ],
        'merchant_delete_terminal'          => ['delete',   'merchants/{mid}/terminals/{tid}',          'MerchantController@deleteTerminal'                             ],
        'merchant_modify_terminal'          => ['put',      'merchants/{mid}/terminals/{tid}',          'MerchantController@putTerminal'                                ],
        'merchant_put_payment_methods'      => ['put',      'merchants/{mid}/methods',                  'MerchantController@putMethods'                                 ],
        'merchant_activate'                 => ['post',     'merchants/{id}/activate',                  'MerchantController@postActivate'                               ],
        'merchant_live_enable'              => ['post',     'merchants/{id}/live/enable',               'MerchantController@postLiveEnable'                             ],
        'merchant_live_disable'             => ['post',     'merchants/{id}/live/disable',              'MerchantController@postLiveDisable'                            ],
        'merchant_fetch_balance'            => ['get',      'merchants/{id}/balance',                   'MerchantController@getBalance'                                 ],
        'merchant_beneficiary_file'         => ['get',      'merchants/beneficiary/file',               'MerchantController@getMerchantBeneficiaryFile'                 ],
        'key_fetch_by_id'                   => ['get',      'keys/{id}',                                'KeyController@getKey'                                          ],
        'key_fetch_multiple'                => ['get',      'keys',                                     'KeyController@getKeys'                                         ],
        'terminal_delete'                   => ['delete',   'terminals/{id}',                           'MerchantController@deleteTerminal2'                            ],
        'terminal_edit'                     => ['put',      'terminals/{id}',                           'MerchantController@putTerminal2'                               ],
        'pricing_create_plan'               => ['post',     'pricing',                                  'PricingController@postCreatePricingPlan'                       ],
        'pricing_get_plans'                 => ['get',      'pricing',                                  'PricingController@getPricingPlans'                             ],
        'pricing_get_merchant_plans'        => ['get',      'pricing/merchants',                        'PricingController@getMerchantPricingPlans'                     ],
        'pricing_get_gateway_plans'         => ['get',      'pricing/gateways',                         'PricingController@getGatewayPricingPlans'                      ],
        'pricing_get_plan'                  => ['get',      'pricing/{id}',                             'PricingController@getPricingPlan'                              ],
        'pricing_get_plan_rule'             => ['get',      'pricing/{planId}/rule/{ruleId}',           'PricingController@getPricingPlanRule'                          ],
        'pricing_add_plan_rule'             => ['post',     'pricing/{id}/rule',                        'PricingController@postAddPricingPlanRule'                      ],
        'transaction_fetch_by_id'           => ['get',      'transactions/{id}',                        'TransactionController@getTransaction'                          ],
        'transaction_fetch_multiple'        => ['get',      'transactions',                             'TransactionController@getTransactions'                         ],
        'setl_fetch_by_id'                  => ['get',      'settlements/{id}',                         'SettlementController@getSettlement'                            ],
        'setl_fetch_multiple'               => ['get',      'settlements',                              'SettlementController@getSettlements'                           ],
        'setl_fetch_transactions'           => ['get',      'settlements/{id}/transactions',            'SettlementController@getSettlementTransactions'                ],
        'hdfc_mpr_reconcile'                => ['post',     'gateway/mpr/reconcile',                    'SettlementController@postGatewayMprReconcile'                  ],
        'hdfc_mpr_generate'                 => ['post',     'gateway/mpr/generate',                     'SettlementController@postGatewayMprGenerate'                   ],
        'setl_delete_file'                  => ['delete',   'settlements/file/{setlFileType}',          'SettlementController@deleteSettlementFile'                     ],
        'setl_initiate'                     => ['post',     'settlements/initiate/{channel?}',          'SettlementController@postSettlementInitiate'                   ],
        'setl_reconcile_generate'           => ['post',     'settlements/reconcile/generate',           'SettlementController@postSettlementReconcileGenerate'          ],
        'setl_reconcile'                    => ['post',     'settlements/reconcile',                    'SettlementController@postSettlementReconcile'                  ],
        'setl_return_generate'              => ['post',     'settlements/return/generate',              'SettlementController@postSettlementReturnGenerate'             ],
        'setl_return'                       => ['post',     'settlements/return',                       'SettlementController@postSettlementReturn'                     ],
        'daily_setl_fetch_by_id'            => ['get',      'dailysettlements/{id}',                    'SettlementController@getDailySettlement'                       ],
        'daily_setl_fetch_multiple'         => ['get',      'dailysettlements',                         'SettlementController@getDailySettlements'                      ],
        'adj_fetch_by_id'                   => ['get',      'adjustments/{id}',                         'AdjustmentController@getAdjustment'                            ],
        'adj_fetch_multiple'                => ['get',      'adjustments',                              'AdjustmentController@getAdjustments'                           ],
        'adj_add'                           => ['post',     'adjustments',                              'AdjustmentController@postAdjustment'                           ],
        'mockhdfc_enroll'                   => ['post',     'gateway/mockhdfc/enroll',                  'MockGatewayController@enroll'                                  ],
        'mockhdfc_payment'                  => ['post',     'gateway/mockhdfc/payment',                 'MockGatewayController@payment'                                 ],
        'mockhdfc_auth_enrolled'            => ['post',     'gateway/mockhdfc/auth_enrolled',           'MockGatewayController@authEnrolled'                            ],
        'mockhdfc_3dsecure'                 => ['post',     'gateway/3dsecure',                         'MockGatewayController@post3dSecure'                            ],
        'mockatom_init_payment'             => ['post',     'gateway/mockanb',                          'MockGatewayController@postAtomInitPayment'                     ],
        'mockatom_choose_org'               => ['get',      'gateway/mockanb',                          'MockGatewayController@getAtomChooseOrg'                        ],
        'mockatom_rzp_payment'              => ['post',     'gateway/mockanb/payment',                  'MockGatewayController@postAtomRzpPayment'                      ],
        'mockatom_rzp_payment_submit'       => ['post',     'gateway/mockanb/payment/submit',           'MockGatewayController@postAtomRzpPaymentSubmit'                ],
        'mock_axis_migs_payment'            => ['post',     'gateway/mockaxismigs/payment',             'MockGatewayController@postAxisPayment'                         ],
        'mock_axis_genius_payment'          => ['post',     'gateway/mockaxisgenius/payment',           'MockGatewayController@postAxisGeniusPayment'                   ],
        'mock_kotak_payment'                => ['get',      'gateway/mockkotak/payment',                'MockGatewayController@getKotakPayment'                         ],
        'mock_paytm_payment'                => ['post',     'gateway/mockpaytm/payment',                'MockGatewayController@postPaytmPayment'                        ],
        'mock_mobikwik_payment'             => ['post',     'gateway/mockmobikwik/payment',             'MockGatewayController@postMobikwikPayment'                     ],
        'mock_billdesk_payment'             => ['post',     'gateway/mockbilldesk/payment',             'MockGatewayController@postBilldeskPayment'                     ],
        'mock_sharp_payment'                => ['post',     'gateway/mocksharp/payment',                'MockGatewayController@getSharpPayment'                         ],
        'mock_sharp_payment_submit'         => ['post',     'gateway/mocksharp/payment/submit',         'MockGatewayController@postSharpPayment'                        ],
        'mock_netbanking_payment'           => ['post',     'gateway/mock/netbanking/{bank}',           'MockGatewayController@postNetbankingPayment'                   ],
        'admin_fetch_entity_multiple'       => ['get',      'admin/{type}',                             'AdminController@getEntityMultiple'                             ],
        'admin_fetch_entity_by_id'          => ['get',      'admin/{type}/{id}',                        'AdminController@getEntityById'                                 ],
        'send_test_newsletter'              => ['post',     'admin/newsletter/test',                    'AdminController@postSendTestNewsletter'                        ],
        'send_newsletter'                   => ['post',     'admin/newsletter/mail',                    'AdminController@postSendNewsletter'                            ],
        'gateway_payment_callback_axis'     => ['post',     'callback/axis',                            'GatewayController@callbackAxis'                                ],
        'gateway_payment_callback'          => ['post',     'callback/{gateway}',                       'GatewayController@callbackGateway'                             ],
        'gateway_payment_callback'          => ['get',      'callback/{gateway}',                       'GatewayController@callbackGateway'                             ],
        'dummy_return_callback'             => ['post',     'return/callback',                          'PaymentController@postDummyReturnCallback'                     ],
        'dummy_critical_error'              => ['get',      'trigger/error',                            'AdminController@getTriggerError'                               ],
    );

    public static $public = array(
        'checkout',
        'payment_create',
        'payment_create_checkout',
        'payment_create_jsonp',
        'payment_cancel',
        'merchant_public_get_banks',
        'merchant_methods',
        'mockatom_init_payment',
        'mockatom_choose_org',
        'mockatom_rzp_payment',
        'mockatom_rzp_payment_submit',
        'mock_axis_migs_payment',
        'mock_axis_genius_payment',
        'mock_kotak_payment',
        'mock_paytm_payment',
        'mock_mobikwik_payment',
        'mock_netbanking_payment',
        'mock_billdesk_payment',
        'mock_sharp_payment',
        'mock_sharp_payment_submit',
        'dummy_return_callback',
        'dummy_critical_error',
    );

    public static $publicCallback = array(
        'payment_callback_with_key_post',
        'payment_callback_with_key_get',
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
        'admin_fetch_entity_multiple',
        'admin_fetch_entity_by_id',
        'merchant_secret',
        'merchant_create',
        'merchant_edit',
        'merchant_edit_email',
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
        'merchant_daily_report',
        'merchant_delete_terminal',
        'merchant_get_terminals',
        'merchant_activate',
        'merchant_live_enable',
        'merchant_live_disable',
        'merchant_put_payment_methods',
        'merchant_get_banks',
        'merchant_set_banks',
        'merchant_set_all_banks',
        'merchant_fetch_balance',
        'merchant_beneficiary_file',
        'terminal_delete',
        'terminal_edit',
        'key_fetch_by_id',
        'key_fetch_multiple',
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
        'daily_setl_fetch_by_id',
        'daily_setl_fetch_multiple',
        'payment_verify',
        'payment_authorize_failed',
        'payment_timeout',
        'payment_auth_notify',
        'payment_auto_capture',
        'payment_auto_capture_email',
        'payment_verify_all',
        'refund_hdfcnb',
        'hdfc_mpr_reconcile',
        'hdfc_mpr_generate',
        'mockhdfc_enroll',
        'mockhdfc_auth_enrolled',
        'mockhdfc_payment',
        'admin_fetch_entity_multiple',
        'admin_fetch_entity_by_id',
        'iin_fetch_by_iin',
        'iin_fetch_multiple',
        'iin_add',
        'send_test_newsletter',
        'send_newsletter'
        );

    public static $proxy = array(
        'refund_fetch_by_id',
        'refund_fetch_multiple',
        'transaction_fetch_by_id',
        'transaction_fetch_multiple',
        'setl_fetch_by_id',
        'setl_fetch_multiple',
        'setl_fetch_transactions',
        'adj_fetch_by_id',
        'adj_fetch_multiple',
        'adj_add',
        'card_fetch_by_id',
        'card_fetch_multiple',
        'payment_authorize_refund',
    );

    public static $direct = array(
    );

    public static $internalApps = array(
            'dashboard' => array('*'),

            'mock_gateways' => array(
                'mockhdfc_enroll',
                'mockhdfc_auth_enrolled',
                'mockhdfc_payment',),

            'cron' => array(
                'hdfc_mpr_generate',
                'setl_initiate',
                'setl_reconcile_generate',
                'setl_return_generate',
                'payment_auth_notify',
                'payment_timeout',
                'merchant_daily_report',
                'payment_auto_capture',
                'payment_verify_all',
                'refund_hdfcnb'),

            'mailgun' => array(
                'hdfc_mpr_reconcile'),

            'hosted' => array(
                'merchant_secret'),
        );

    protected static $jsonpRoutes = array(
        'checkout',
        'payment_create_jsonp',
        'merchant_public_get_banks',
        'merchant_methods',
    );

    protected static $router;

    public static function setRouter($router)
    {
        self::$router = $router;
    }

    public static function getUrl($routeName, array $parameters = array(), $key = '', $secret = '')
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

    public static function getUrlWithPublicAuth($routeName, array $parameters = array(), $key = '')
    {
        if ($key === '')
        {
            $key = \BasicAuth::getPublicKey();
        }

        return self::getUrl($routeName, $parameters, $key);
    }

    public static function getUrlWithPublicCallbackAuth(array $parameters = array())
    {
        $key = \BasicAuth::getPublicKey();

        return self::getUrl('payment_callback_with_key_post', $parameters, $key);
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

    public static function isJsonpRoute($route)
    {
        $jsonpRoutes = self::$jsonpRoutes;

        return in_array($route, $jsonpRoute);
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
            self::addFilterOnRouteGroups($router, 'auth.public_callback', 'publicCallback');
            self::addFilterOnRouteGroups($router, 'auth.proxy', 'proxy');
            self::addFilterOnRouteGroups($router, 'auth.direct', 'direct');
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
