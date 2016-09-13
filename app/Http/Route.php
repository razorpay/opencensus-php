<?php

namespace RZP\Http;

final class Route
{
    /*
     | The order in which routes are defined is very important.
     | Whenever the order of routes is changed,
     | make sure to run the full test suite
     */

    protected static $apiRoutes = array(
        'account'                                 => ['get',      'account',                                  'PublicController@getAccount'                                       ],
        'checkout'                                => ['get',      'checkout',                                 'MerchantController@getCheckout'                                    ],
        'checkout_public'                         => ['get',      'checkout/public',                          'MerchantController@getCheckoutPublic'                              ],
        'merchant_methods'                        => ['get',      'methods',                                  'MerchantController@getPaymentMethods'                              ],
        'merchant_checkout_preferences'           => ['get',      'preferences',                              'MerchantController@getCheckoutPreferences'                         ],
        'payment_create'                          => ['post',     'payments',                                 'PaymentCreateController@postCreatePayment'                         ],
        'payment_create_private'                  => ['post',     'payments/create',                          'PaymentCreateController@postCreatePayment'                         ],
        'payment_create_checkout'                 => ['post',     'payments/create/checkout',                 'PaymentCreateController@postCreatePaymentCheckoutCallback'         ],
        'payment_create_jsonp'                    => ['get',      'payments/create/jsonp',                    'PaymentCreateController@getCreatePaymentJsonp'                     ],
        'payment_create_ajax'                     => ['post',     'payments/create/ajax',                     'PaymentCreateController@postAJAX'                                  ],
        'payment_create_fees'                     => ['post',     'payments/create/fees',                     'PaymentCreateController@postCreatePaymentFees'                     ],
        'payment_create_wallet'                   => ['post',     'payments/create/wallet',                   'PaymentCreateController@postCreateWalletPayment'                   ],
        'payment_callback_post'                   => ['post',     'payments/{id}/callback/{hash}',            'PaymentCreateController@postCallback'                              ],
        'payment_callback_get'                    => ['get',      'payments/{id}/callback/{hash}',            'PaymentCreateController@postCallback'                              ],
        'payment_callback_with_key_post'          => ['post',     'payments/{id}/callback/{hash}/{key}',      'PaymentCreateController@postCallback'                              ],
        'payment_callback_with_key_get'           => ['get',      'payments/{id}/callback/{hash}/{key}',      'PaymentCreateController@postCallback'                              ],
        'payment_otp_submit'                      => ['post',     'payments/{id}/otp_submit/{hash}',          'PaymentCreateController@postOtpSubmit'                             ],
        'payment_otp_resend'                      => ['post',     'payments/{id}/otp_resend',                 'PaymentCreateController@postOtpResend'                             ],
        'payment_topup_ajax'                      => ['post',     'payments/{id}/topup/ajax',                 'PaymentCreateController@postTopupAjax'                             ],
        'payment_topup_post'                      => ['post',     'payments/{id}/topup',                      'PaymentCreateController@postTopup'                                 ],
        'payment_redirect'                        => ['post',     'payments/{id}/redirect',                   'PaymentCreateController@postRedirect'                              ],
        'payment_refund'                          => ['post',     'payments/{id}/refund',                     'PaymentController@postRefund'                                      ],
        'payment_capture'                         => ['post',     'payments/{id}/capture',                    'PaymentController@postCapture'                                     ],
        'payment_verify'                          => ['get',      'payments/{id}/verify',                     'PaymentController@getVerify'                                       ],
        'payment_force_authorize'                 => ['post',     'payments/{id}/force_authorize',            'PaymentController@postForceAuthorize'                              ],
        'payment_cancel'                          => ['get',      'payments/{id}/cancel',                     'PaymentController@postCancel'                                      ],
        'payment_authorize_failed'                => ['post',     'payments/{id}/authorize_failed',           'PaymentController@postAuthorizeFailedPayment'                      ],
        'payment_authorize_refund'                => ['post',     'payments/{id}/authorize_refund',           'PaymentController@postRefundAuthorized'                            ],
        'payment_add_metadata'                    => ['post',     'payments/{id}/metadata',                   'PaymentController@postPaymentMetadata'                             ],
        'payment_fetch_by_id'                     => ['get',      'payments/{id}',                            'PaymentController@getPayment'                                      ],
        'payment_fetch_multiple'                  => ['get',      'payments',                                 'PaymentController@getPayments'                                     ],
        'payment_fetch_card_details'              => ['get',      'payments/{id}/card',                       'PaymentController@getCardForPayment'                               ],
        'payment_fetch_refunds'                   => ['get',      'payments/{id}/refunds',                    'PaymentController@getRefundsForPayment'                            ],
        'payment_fetch_refund_by_id'              => ['get',      'payments/{paymentId}/refunds/{rfndId}',    'PaymentController@getRefundByRefundAndPaymentId'                   ],
        'payment_auth_notify'                     => ['get',      'payments/auth/notify',                     'PaymentController@getAuthNotify',                                  ],
        'payment_timeout'                         => ['post',     'payments/timeout',                         'PaymentController@postTimeout'                                     ],
        'payment_auto_capture'                    => ['post',     'payments/autocapture',                     'PaymentController@postAutoCapture'                                 ],
        'payment_auto_capture_email'              => ['get',      'payments/autocapture/email',               'PaymentController@getAutoCaptureEmail'                             ],
        'payment_verify_multiple'                 => ['get',      'payments/verify/{filter}',                 'PaymentController@getVerifyPayments'                               ],
        'payment_capture_reminder'                => ['get',      'payments/all/reminder',                    'PaymentController@sendReminderMailForAuthorizedPayments'           ],
        'payment_refund_authorized'               => ['post',     'payments/refund/authorized',               'PaymentController@postRefundOldAuthorizedPayments'                 ],
        'refund_fetch_by_id'                      => ['get',      'refunds/{id}',                             'PaymentController@getRefund'                                       ],
        'refund_fetch_multiple'                   => ['get',      'refunds',                                  'PaymentController@getRefunds'                                      ],
        'refund_netbanking_generate_excel'        => ['post',     'refunds/netbanking/excel',                 'PaymentController@generateNetbankingRefunds'                       ],
        'refund_generate_excel'                   => ['post',     'refunds/excel',                            'PaymentController@generateRefunds'                                 ],
        'refund_verify'                           => ['post',     'refunds/{ids}/verify',                     'PaymentController@postRefundVerify'                                ],
        'payment_capture_verify'                  => ['post',     'payments/{id}/verify/capture',             'PaymentController@postCaptureVerify'                               ],
        'card_fetch_by_id'                        => ['get',      'cards/{id}',                               'PaymentController@getCard'                                         ],
        'card_fetch_multiple'                     => ['get',      'cards',                                    'PaymentController@getCards'                                        ],
        'iin_fetch_by_iin'                        => ['get',      'iins/{id}',                                'CardController@getIin'                                             ],
        'iin_fetch_multiple'                      => ['get',      'iins',                                     'CardController@getIins'                                            ],
        'iin_add'                                 => ['post',     'iins',                                     'CardController@postIin'                                            ],
        'iin_upload'                              => ['post',     'iins/upload',                              'CardController@uploadIin'                                          ],
        'iin_edit'                                => ['put',      'iins/{id}',                                'CardController@editIin'                                            ],
        'iin_generate_post'                       => ['post',     'iins/import/generate',                     'CardController@postIinGenerate'                                    ],
        'merchant_public_get_banks'               => ['get',      'banks',                                    'MerchantController@getBanksPublic'                                 ],
        'merchant_secret'                         => ['get',      'keys/{id}/secret',                         'MerchantController@getKeySecret'                                   ],
        'merchant_get_banks'                      => ['get',      'merchants/{id}/banks',                     'MerchantController@getBanks'                                       ],
        'merchant_set_banks'                      => ['post',     'merchants/{id}/banks',                     'MerchantController@setBanks'                                       ],
        'merchant_set_all_banks'                  => ['put',      'merchants/banks',                          'MerchantController@putBanksForAllMerchants'                        ],
        'merchant_daily_report'                   => ['post',     'merchants/report',                         'MerchantController@sendDailyReport'                                ],
        'merchant_create'                         => ['post',     'merchants',                                'MerchantController@postCreateMerchant'                             ],
        'merchant_fetch'                          => ['get',      'merchants/{id}',                           'MerchantController@getMerchant'                                    ],
        'merchant_edit'                           => ['put',      'merchants/{id}',                           'MerchantController@putMerchant'                                    ],
        'merchant_edit_config'                    => ['put',      'account/config',                           'MerchantController@putMerchantConfig'                              ],
        'merchant_edit_config_logo'               => ['post',     'account/config/logo',                      'MerchantController@postMerchantConfigLogo'                         ],
        'merchant_delete_config_logo'             => ['delete',   'account/config/logo',                      'MerchantController@deleteMerchantConfigLogo'                       ],
        'submerchant_create'                      => ['post',     'submerchants',                             'MerchantController@postCreateSubMerchant'                          ],
        'account_fetch_balance'                   => ['get',      'balance',                                  'MerchantController@getAccountBalance'                              ],
        'fetch_bank_account'                      => ['get',      'account/bank_account',                     'MerchantController@getOwnBankAccount'                              ],
        'account_fetch_config'                    => ['get',      'account/config',                           'MerchantController@getAccountConfig'                               ],
        'merchant_edit_email'                     => ['put',      'merchants/{id}/email',                     'MerchantController@putMerchantEmail'                               ],
        'merchant_fetch_multiple'                 => ['get',      'merchants',                                'MerchantController@getMerchants'                                   ],
        'merchant_create_key'                     => ['post',     'merchants/{id}/keys',                      'MerchantController@postCreateKeys'                                 ],
        'merchant_fetch_keys'                     => ['get',      'merchants/{id}/keys',                      'MerchantController@getKeys'                                        ],
        'merchant_replace_key'                    => ['put',      'merchants/{merchantId}/keys/{keyId}',      'MerchantController@putKeys'                                        ],
        'merchant_fetch_webhooks'                 => ['get',      'merchants/{id}/webhooks',                  'MerchantController@getMerchantWebhooks'                            ],
        'merchant_assign_pricing'                 => ['post',     'merchants/{id}/pricing',                   'MerchantController@postAssignPricingPlan'                          ],
        'merchant_get_pricing'                    => ['get',      'merchants/{id}/pricing',                   'MerchantController@getPricingPlan'                                 ],
        'merchant_add_bank_account'               => ['post',     'merchants/{id}/bank_account',              'MerchantController@postBankAccount'                                ],
        'merchant_fetch_bank_account'             => ['get',      'merchants/{id}/bank_account',              'MerchantController@getBankAccount'                                 ],
        'merchant_generate_test_bank_acnt'        => ['post',     'merchants/bank_account/generate/test',     'MerchantController@postGenerateTestBankAccounts'                   ],
        'merchant_create_terminal'                => ['post',     'merchants/{id}/terminals',                 'MerchantController@postCreateTerminal'                             ],
        'merchant_get_terminals'                  => ['get',      'merchants/{id}/terminals',                 'MerchantController@getTerminals'                                   ],
        'merchant_get_terminal'                   => ['get',      'merchants/{mid}/terminals/{tid}',          'MerchantController@getTerminal'                                    ],
        'merchant_delete_terminal'                => ['delete',   'merchants/{mid}/terminals/{tid}',          'MerchantController@deleteTerminal'                                 ],
        'merchant_modify_terminal'                => ['put',      'merchants/{mid}/terminals/{tid}',          'MerchantController@putTerminal'                                    ],
        'merchant_put_payment_methods'            => ['put',      'merchants/{mid}/methods',                  'MerchantController@putMethods'                                     ],
        'merchant_activate'                       => ['post',     'merchants/{id}/activate',                  'MerchantController@postActivate'                                   ],
        'merchant_live_enable'                    => ['post',     'merchants/{id}/live/enable',               'MerchantController@postLiveEnable'                                 ],
        'merchant_live_disable'                   => ['post',     'merchants/{id}/live/disable',              'MerchantController@postLiveDisable'                                ],
        'merchant_fetch_balance'                  => ['get',      'merchants/{id}/balance',                   'MerchantController@getBalance'                                     ],
        'merchant_edit_free_credits'              => ['post',     'merchants/{id}/credits',                   'MerchantController@postFreeCredits',                               ],
        'merchant_beneficiary_file'               => ['get',      'merchants/beneficiary/file',               'MerchantController@getMerchantBeneficiaryFile'                     ],
        'merchant_post_beneficiary_file'          => ['post',     'merchants/beneficiary/file/bank',          'MerchantController@postMerchantBeneficiaryFile'                    ],
        'merchant_add_features'                   => ['post',     'merchants/{id}/features',                  'MerchantController@postMerchantFeatures'                           ],
        'merchant_get_features'                   => ['get',      'merchants/{id}/features',                  'MerchantController@getMerchantFeatures'                            ],
        'merchant_notify_holiday'                 => ['post',     'merchants/notify/holiday',                 'MerchantController@postMerchantsNotifyHoliday'                     ],
        'credits_create'                          => ['post',     'merchants/{id}/credits_log',               'MerchantController@postCreateCreditsLog'                           ],
        'credits_fetch_by_id'                     => ['get',      'merchants/{mid}/credits/{id}',             'MerchantController@getCreditsLog'                                  ],
        'credits_edit'                            => ['put',      'merchants/{mid}/credits/{id}',             'MerchantController@putCreditsLog'                                  ],
        'credits_delete'                          => ['delete',   'merchants/{mid}/credits/{id}',             'MerchantController@deleteCreditsLog'                               ],
        'credits_fetch_multiple'                  => ['get',      'credits',                                  'MerchantController@getCreditsLogs'                                 ],
        'key_fetch_by_id'                         => ['get',      'keys/{id}',                                'KeyController@getKey'                                              ],
        'key_fetch_multiple'                      => ['get',      'keys',                                     'KeyController@getKeys'                                             ],
        'terminal_delete'                         => ['delete',   'terminals/{id}',                           'MerchantController@deleteTerminal2'                                ],
        'terminal_edit'                           => ['put',      'terminals/{id}',                           'MerchantController@putTerminal2'                                   ],
        'terminal_restore'                        => ['put',      'terminals/{id}/restore',                   'MerchantController@restoreTerminal',                               ],
        'terminal_check_encrypted_value'          => ['post',     'terminals/{id}/secret',                    'MerchantController@postCheckTerminalEncryptedValue'                ],
        'webhook_create'                          => ['post',     'webhooks',                                 'MerchantController@postWebhook'                                    ],
        'webhook_edit'                            => ['put',      'webhooks/{id}',                            'MerchantController@putWebhook'                                     ],
        'webhook_fetch'                           => ['get',      'webhooks/{id}',                            'MerchantController@getWebhook'                                     ],
        'webhook_fetch_multiple'                  => ['get',      'webhooks',                                 'MerchantController@getWebhooks'                                    ],
        'pricing_create_plan'                     => ['post',     'pricing',                                  'PricingController@postCreatePricingPlan'                           ],
        'pricing_upload_plan'                     => ['post',     'pricing/upload',                           'PricingController@postUploadPricingPlan'                           ],
        'pricing_get_plans'                       => ['get',      'pricing',                                  'PricingController@getPricingPlans'                                 ],
        'pricing_get_merchant_plans'              => ['get',      'pricing/merchants',                        'PricingController@getMerchantPricingPlans'                         ],
        'pricing_get_gateway_plans'               => ['get',      'pricing/gateways',                         'PricingController@getGatewayPricingPlans'                          ],
        'pricing_supported_networks'              => ['get',      'pricing/networks',                         'PricingController@getSupportedNetworks'                            ],
        'pricing_get_plan'                        => ['get',      'pricing/{id}',                             'PricingController@getPricingPlan'                                  ],
        'pricing_get_plan_rule'                   => ['get',      'pricing/{planId}/rule/{ruleId}',           'PricingController@getPricingPlanRule'                              ],
        'pricing_add_plan_rule'                   => ['post',     'pricing/{id}/rule',                        'PricingController@postAddPricingPlanRule'                          ],
        'pricing_delete_plan_rule'                => ['delete',   'pricing/{planId}/rule/{ruleId}',           'PricingController@deletePricingPlanRule'                           ],
        'pricing_delete_plan_rule_force'          => ['delete',   'pricing/{planId}/rule/{ruleId}/force',     'PricingController@deletePricingPlanRuleForce'                      ],
        'refund_create_missing_txn'               => ['post',     'refunds/transaction',                      'PaymentController@postRefundsTransactions'                         ],
        'transaction_fetch_by_id'                 => ['get',      'transactions/{id}',                        'TransactionController@getTransaction'                              ],
        'transaction_fetch_multiple'              => ['get',      'transactions',                             'TransactionController@getTransactions'                             ],
        'transaction_monthly_report'              => ['get',      'transactions/report',                      'TransactionController@getMonthlyReport'                            ],
        'setl_fetch_by_id'                        => ['get',      'settlements/{id}',                         'SettlementController@getSettlement'                                ],
        'setl_fetch_multiple'                     => ['get',      'settlements',                              'SettlementController@getSettlements'                               ],
        'setl_fetch_transactions'                 => ['get',      'settlements/{id}/transactions',            'SettlementController@getSettlementTransactions'                    ],
        'setl_edit'                               => ['put',      'settlements/{id}',                         'SettlementController@putEditSettlement'                            ],
        'setl_fixer'                              => ['get',      'settlements/fixer',                        'SettlementController@getSettlementFixer'                           ],
        'hdfc_mpr_reconcile'                      => ['post',     'gateway/mpr/reconcile',                    'SettlementController@postGatewayMprReconcile'                      ],
        'hdfc_mpr_generate'                       => ['post',     'gateway/mpr/generate',                     'SettlementController@postGatewayMprGenerate'                       ],
        'setl_delete_file'                        => ['delete',   'settlements/file/{setlFileType}',          'SettlementController@deleteSettlementFile'                         ],
        'setl_initiate'                           => ['post',     'settlements/initiate/{channel?}',          'SettlementController@postSettlementInitiate'                       ],
        'setl_reconcile_generate'                 => ['post',     'settlements/reconcile/generate',           'SettlementController@postSettlementReconcileGenerate'              ],
        'setl_reconcile'                          => ['post',     'settlements/reconcile',                    'SettlementController@postSettlementReconcile'                      ],
        'setl_reconcile_h2h'                      => ['post',     'settlements/h2hreconcile',                 'SettlementController@postH2HSettlementReconcile'                   ],
        'setl_return_generate'                    => ['post',     'settlements/return/generate',              'SettlementController@postSettlementReturnGenerate'                 ],
        'setl_return'                             => ['post',     'settlements/return',                       'SettlementController@postSettlementReturn'                         ],
        'setl_calc_previous_fees'                 => ['post',     'settlements/fees/previous',                'SettlementController@postSettlementCalculateFees',                 ],
        'setl_get_details'                        => ['get',      'settlements/{id}/details',                 'SettlementController@getSettlementDetails',                        ],
        'setl_post_details_old'                   => ['post',     'settlements/details',                      'SettlementController@postSettlementDetailsForOldTxns'              ],
        'setl_combined_report'                    => ['get',      'settlements/report/combined',              'SettlementController@getSettlementCombinedReport'                  ],
        'daily_setl_calc_previous_fees'           => ['post',     'dailysettlements/fees/previous',           'SettlementController@postDailySettlementCalculatePreviousFees'     ],
        'daily_setl_fetch_by_id'                  => ['get',      'dailysettlements/{id}',                    'SettlementController@getDailySettlement'                           ],
        'daily_setl_fetch_multiple'               => ['get',      'dailysettlements',                         'SettlementController@getDailySettlements'                          ],
        'adj_fetch_by_id'                         => ['get',      'adjustments/{id}',                         'AdjustmentController@getAdjustment'                                ],
        'adj_fetch_multiple'                      => ['get',      'adjustments',                              'AdjustmentController@getAdjustments'                               ],
        'adj_add'                                 => ['post',     'adjustments',                              'AdjustmentController@postAdjustment'                               ],
        'mockhdfc_enroll'                         => ['post',     'gateway/mockhdfc/enroll',                  'MockGatewayController@enroll'                                      ],
        'mockhdfc_payment'                        => ['post',     'gateway/mockhdfc/payment',                 'MockGatewayController@payment'                                     ],
        'mockhdfc_auth_enrolled'                  => ['post',     'gateway/mockhdfc/auth_enrolled',           'MockGatewayController@authEnrolled'                                ],
        'mockhdfc_3dsecure'                       => ['post',     'gateway/3dsecure',                         'MockGatewayController@post3dSecure'                                ],
        'mockcybersource_acs'                     => ['post',     'gateway/acs/{gateway}',                    'MockGatewayController@postAcs'                                     ],
        'mockatom_init_payment'                   => ['post',     'gateway/mockanb',                          'MockGatewayController@postAtomInitPayment'                         ],
        'mockatom_choose_org'                     => ['get',      'gateway/mockanb',                          'MockGatewayController@getAtomChooseOrg'                            ],
        'mockatom_rzp_payment'                    => ['post',     'gateway/mockanb/payment',                  'MockGatewayController@postAtomRzpPayment'                          ],
        'mockatom_rzp_payment_submit'             => ['post',     'gateway/mockanb/payment/submit',           'MockGatewayController@postAtomRzpPaymentSubmit'                    ],
        'mock_axis_migs_payment'                  => ['post',     'gateway/mockaxismigs/payment',             'MockGatewayController@postAxisPayment'                             ],
        'mock_axis_genius_payment'                => ['post',     'gateway/mockaxisgenius/payment',           'MockGatewayController@postAxisGeniusPayment'                       ],
        'mock_kotak_payment'                      => ['get',      'gateway/mockkotak/payment',                'MockGatewayController@getKotakPayment'                             ],
        'mock_paytm_payment'                      => ['post',     'gateway/mockpaytm/payment',                'MockGatewayController@postPaytmPayment'                            ],
        'mock_mobikwik_payment'                   => ['post',     'gateway/mockmobikwik/payment',             'MockGatewayController@postMobikwikPayment'                         ],
        'mock_billdesk_payment'                   => ['post',     'gateway/mockbilldesk/payment',             'MockGatewayController@postBilldeskPayment'                         ],
        'mock_ebs_payment'                        => ['post',     'gateway/mockebs/payment',                  'MockGatewayController@postEbsPayment'                              ],
        'mock_sharp_payment_post'                 => ['post',     'gateway/mocksharp/payment',                'MockGatewayController@getSharpPayment'                             ],
        'mock_sharp_payment_get'                  => ['get',      'gateway/mocksharp/payment',                'MockGatewayController@getSharpPayment'                             ],
        'mock_amex_payment'                       => ['post',     'gateway/mockamex/payment',                 'MockGatewayController@postAmexPayment'                             ],
        'mock_sharp_payment_submit'               => ['post',     'gateway/mocksharp/payment/submit',         'MockGatewayController@postSharpPayment'                            ],
        'mock_netbanking_payment'                 => ['post',     'gateway/mock/netbanking/{bank}',           'MockGatewayController@postNetbankingPayment'                       ],
        'mock_sbiepay_payment'                    => ['post',     'gateway/mocksbiepay/payment',              'MockGatewayController@postSbiepayPayment'                          ],
        'mock_wallet_payment'                     => ['post',     'gateway/mock/wallet/{wallet}',             'MockGatewayController@walletPayment'                               ],
        'mock_wallet_payment_get'                 => ['get',      'gateway/mock/wallet/{wallet}',             'MockGatewayController@walletPayment'                               ],
        'mock_wallet_payment_with_paymentid'      => ['post',     'gateway/mock/wallet/{wallet}/{paymentId}', 'MockGatewayController@walletPayment'                               ],
        'admin_fetch_entity_multiple'             => ['get',      'admin/{type}',                             'AdminController@getEntityMultiple'                                 ],
        'admin_fetch_entity_by_id'                => ['get',      'admin/{type}/{id}',                        'AdminController@getEntityById'                                     ],
        'send_test_newsletter'                    => ['post',     'admin/newsletter/test',                    'AdminController@postSendTestNewsletter'                            ],
        'send_newsletter'                         => ['post',     'admin/newsletter/mail',                    'AdminController@postSendNewsletter'                                ],
        'gateway_payment_callback_axis'           => ['post',     'callback/axis',                            'GatewayController@callbackAxis'                                    ],
        'gateway_payment_callback_get'            => ['post',     'callback/{gateway}',                       'GatewayController@callbackGateway'                                 ],
        'gateway_payment_callback_post'           => ['get',      'callback/{gateway}',                       'GatewayController@callbackGateway'                                 ],
        'gateway_payment_callback_kotak'          => ['get',      'gateway/netbanking_kotak/callback',        'GatewayController@callbackKotak'                                   ],
        'gateway_payment_callback_kotak_cancel'   => ['post',     'gateway/netbanking_kotak/callback',        'GatewayController@callbackKotakCancel'                             ],
        'reconciliate'                            => ['post',     'reconciliate',                             'ReconciliatorController@postReconciliation'                        ],
        'dummy_return_callback'                   => ['post',     'return/callback',                          'PaymentController@postDummyReturnCallback'                         ],
        'dummy_critical_error'                    => ['get',      'trigger/error',                            'AdminController@getTriggerError'                                   ],
        'dummy_route'                             => ['post',     'dummy/route',                              'PaymentController@postDummyRoute'                                  ],
        'transparent_redirect_get'                => ['get',      'redirect',                                 'AdminController@getTransparentRedirect'                            ],
        'transparent_redirect_post'               => ['post',     'redirect',                                 'AdminController@postTransparentRedirect'                           ],
        'settlement_compute_tax'                  => ['post',     'settlements/compute/tax',                  'SettlementController@postComputeSettlementServiceTax'              ],
        'daily_settlement_compute_tax'            => ['post',     'dailysettlements/compute/tax',             'SettlementController@postComputeDailySettlementServiceTax'         ],
        'get_features'                            => ['get',      'features',                                 'MerchantController@getAllFeatures'                                 ],
        'dummy_feature'                           => ['get',      'features/dummy',                           'MerchantController@getDummyFeatures'                               ],
        'add_emi_plan'                            => ['post',     'emi',                                      'EmiController@addEmiPlan'                                          ],
        'get_emi_plans'                           => ['get',      'emi',                                      'EmiController@fetchAvailableEmiPlans'                              ],
        'get_emi_plan_by_id'                      => ['get',      'emi/{id}',                                 'EmiController@fetchEmiPlanById'                                    ],
        'delete_emi_plan'                         => ['delete',   'emi/{id}',                                 'EmiController@deleteEmiPlan'                                       ],
        'emi_generate_excel'                      => ['post',     'emi/generate/excel',                       'EmiController@generateEmiExcel'                                    ],
        'order_create'                            => ['post',     'orders',                                   'OrderController@createOrder'                                       ],
        'order_fetch'                             => ['get',      'orders',                                   'OrderController@getOrders'                                         ],
        'order_fetch_by_id'                       => ['get',      'orders/{id}',                              'OrderController@fetchOrderById'                                    ],
        'order_payments'                          => ['get',      'orders/{id}/payments',                     'OrderController@fetchPayments'                                     ],
        'reports_monthly_invoice'                 => ['get',      'reports/invoice',                          'MerchantController@getInvoiceReport'                               ],
        'reports_public_entity'                   => ['get',      'reports/{entity}',                         'MerchantController@getPublicEntityReport'                          ],
        'customer_create'                         => ['post',     'customers',                                'CustomerController@createLocalCustomer'                            ],
        'customer_update'                         => ['put',      'customers/{id}',                           'CustomerController@updateCustomer'                                 ],
        'customer_get'                            => ['get',      'customers/{id}',                           'CustomerController@getCustomer'                                    ],
        'customer_delete'                         => ['delete',   'customers/{id}',                           'CustomerController@deleteCustomer'                                 ],
        'customer_add_bank_account'               => ['post',     'customers/{id}/bank_account',              'CustomerController@postBankAccount'                                ],
        'customer_fetch_bank_account'             => ['get',      'customers/{id}/bank_account',              'CustomerController@getBankAccounts'                                ],
        'customer_create_token'                   => ['post',     'customers/{id}/tokens',                    'CustomerController@addToken'                                       ],
        'customer_update_token'                   => ['put',      'customers/{id}/tokens/{token}',            'CustomerController@updateToken'                                    ],
        'customer_fetch_token'                    => ['get',      'customers/{id}/tokens/{token}',            'CustomerController@fetchToken'                                     ],
        'customer_fetch_tokens'                   => ['get',      'customers/{id}/tokens',                    'CustomerController@fetchTokens'                                    ],
        'customer_delete_token'                   => ['delete',   'customers/{id}/tokens/{token}',            'CustomerController@deleteToken'                                    ],
        'customer_get_saved_status'               => ['get',      'customers/status/{contact}',               'CustomerController@fetchGlobalCustomerStatus'                      ],
        'customer_logout_global'                  => ['delete',   'apps/logout',                              'CustomerController@logoutCustomer'                                 ],
        'app_delete_token'                        => ['delete',   'apps/tokens/{token}',                      'CustomerController@deleteTokenForGlobalCustomer'                   ],
        'app_fetch_tokens'                        => ['get',      'apps/tokens',                              'CustomerController@fetchTokensForGlobalCustomer'                   ],
        'app_fetch_payments'                      => ['get',      'apps/payments',                            'CustomerController@fetchPaymentsForGlobalCustomer'                 ],
        'device_verify_token'                     => ['post',     'devices/{deviceToken}/verify',             'CustomerController@validateDeviceToken'                            ],
        'otp_post'                                => ['post',     'otp/create',                               'CustomerController@postOtp'                                        ],
        'otp_verify'                              => ['post',     'otp/verify',                               'CustomerController@verifyOtp'                                      ],
        'sms_callback'                            => ['post',     'sms/{id}/callback',                        'CustomerController@updateSmsStatus'                                ],
        'es_migrate_entity'                       => ['post',     'es/migrate/{entityName}',                  'EsController@migrateEntity'                                        ],
        'refund_gateway_manual'                   => ['post',     'refunds/{ids}/gateway',                    'PaymentController@postManualGatewayRefund'                         ],
        'order_refund_multiple_authorized'        => ['post',     'payments/orders/refund',                   'PaymentController@postRefundMultipleAuthorizedPaymentsForOrders'   ],
    );

    public static $public = array(
        'checkout',
        'payment_create',
        'payment_create_checkout',
        'payment_create_jsonp',
        'payment_create_ajax',
        'payment_create_fees',
        'payment_otp_submit',
        'payment_otp_resend',
        'payment_topup_ajax',
        'payment_topup_post',
        'payment_redirect',
        'payment_cancel',
        'payment_add_metadata',
        'merchant_public_get_banks',
        'merchant_methods',
        'merchant_checkout_preferences',
        'mockatom_init_payment',
        'mockatom_choose_org',
        'mockatom_rzp_payment',
        'mockatom_rzp_payment_submit',
        'mock_amex_payment',
        'mock_axis_migs_payment',
        'mock_axis_genius_payment',
        'mock_kotak_payment',
        'mock_paytm_payment',
        'mock_mobikwik_payment',
        'mock_netbanking_payment',
        'mock_billdesk_payment',
        'mock_ebs_payment',
        'mock_sharp_payment_post',
        'mock_sharp_payment_get',
        'mock_sharp_payment_submit',
        'mock_sbiepay_payment',
        'mock_wallet_payment',
        'mock_wallet_payment_get',
        'mock_wallet_payment_with_paymentid',
        'dummy_return_callback',
        'get_emi_plans',
        'customer_get_saved_status',
        'app_delete_token',
        'app_fetch_payments',
        'customer_logout_global',
        'otp_post',
        'otp_verify'
    );

    public static $publicCallback = array(
        'payment_callback_with_key_post',
        'payment_callback_with_key_get',
    );

    public static $private = array(
        'payment_create_wallet',
        'payment_refund',
        'payment_capture',
        'payment_fetch_by_id',
        'payment_fetch_multiple',
        'payment_fetch_refunds',
        'payment_fetch_refund_by_id',
        'payment_create_private',
        'refund_fetch_by_id',
        'refund_fetch_multiple',
        'card_fetch_by_id',
        'order_create',
        'order_fetch',
        'order_fetch_by_id',
        'order_payments',
        'dummy_feature',
        'customer_create',
        'customer_update',
        'customer_get',
        'customer_delete_token',
        'customer_fetch_token',
        'customer_fetch_tokens',
        'customer_add_bank_account',
        'customer_fetch_bank_account',
        'setl_combined_report',
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
        'merchant_generate_test_bank_acnt',
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
        'merchant_edit_free_credits',
        'merchant_beneficiary_file',
        'merchant_fetch_webhooks',
        'merchant_post_beneficiary_file',
        'merchant_notify_holiday',
        'terminal_delete',
        'terminal_edit',
        'terminal_restore',
        'terminal_check_encrypted_value',
        'key_fetch_by_id',
        'key_fetch_multiple',
        'pricing_create_plan',
        'pricing_upload_plan',
        'pricing_get_plans',
        'pricing_get_merchant_plans',
        'pricing_get_gateway_plans',
        'pricing_supported_networks',
        'pricing_add_plan_rule',
        'pricing_get_plan',
        'pricing_get_plan_rule',
        'pricing_delete_plan_rule',
        'pricing_delete_plan_rule_force',
        'setl_initiate',
        'setl_reconcile',
        'setl_reconcile_h2h',
        'setl_reconcile_generate',
        'setl_return_generate',
        'setl_return',
        'setl_edit',
        'setl_delete_file',
        'setl_calc_previous_fees',
        'setl_post_details_old',
        'setl_fixer',
        'daily_setl_fetch_by_id',
        'daily_setl_fetch_multiple',
        'daily_setl_calc_previous_fees',
        'payment_verify',
        'payment_authorize_failed',
        'payment_timeout',
        'payment_auth_notify',
        'payment_auto_capture',
        'payment_auto_capture_email',
        'payment_force_authorize',
        'payment_capture_reminder',
        'payment_refund_authorized',
        'payment_verify_multiple',
        'refund_create_missing_txn',
        'settlement_compute_tax',
        'daily_settlement_compute_tax',
        'refund_netbanking_generate_excel',
        'refund_generate_excel',
        'hdfc_mpr_reconcile',
        'hdfc_mpr_generate',
        'mockhdfc_enroll',
        'mockhdfc_auth_enrolled',
        'mockhdfc_payment',
        'iin_fetch_by_iin',
        'iin_fetch_multiple',
        'iin_add',
        'iin_upload',
        'iin_edit',
        'iin_generate_post',
        'send_test_newsletter',
        'send_newsletter',
        'merchant_add_features',
        'merchant_get_features',
        'get_features',
        'add_emi_plan',
        'delete_emi_plan',
        'get_emi_plan_by_id',
        'emi_generate_excel',
        'refund_verify',
        'payment_capture_verify',
        'es_migrate_entity',
        'dummy_critical_error',
        'reconciliate',
        'credits_create',
        'credits_edit',
        'credits_delete',
        'refund_gateway_manual',
        'order_refund_multiple_authorized',
    );

    public static $proxy = array(
        'payment_fetch_card_details',
        'transaction_monthly_report',
        'transaction_fetch_by_id',
        'transaction_fetch_multiple',
        'setl_fetch_by_id',
        'setl_fetch_multiple',
        'setl_fetch_transactions',
        'setl_get_details',
        'adj_fetch_by_id',
        'adj_fetch_multiple',
        'adj_add',
        'card_fetch_multiple',
        'payment_authorize_refund',
        'webhook_create',
        'webhook_edit',
        'webhook_fetch',
        'webhook_fetch_multiple',
        'merchant_fetch_balance',
        'reports_monthly_invoice',
        'fetch_bank_account',
        'reports_public_entity',
        'merchant_edit_config',
        'merchant_edit_config_logo',
        'merchant_delete_config_logo',
        'account_fetch_balance',
        'account_fetch_config',
        'submerchant_create',
        'customer_delete',
        'customer_create_token',
        'customer_update_token',
        'device_verify_token',
        'app_fetch_tokens',
        'credits_fetch_multiple',
        'credits_fetch_by_id',
    );

    public static $direct = array(
        'account',
        'dummy_route',
        'checkout_public',
        'mockhdfc_3dsecure',
        'mockcybersource_acs',
        'transparent_redirect_get',
        'transparent_redirect_post',
        'gateway_payment_callback_kotak',
        'gateway_payment_callback_kotak_cancel',
        'gateway_payment_callback_get',
        'gateway_payment_callback_post',
        'sms_callback',
    );

    public static $internalApps = array(
        'dashboard' => array('*'),

        'mock_gateways' => array(
            'mockhdfc_enroll',
            'mockhdfc_auth_enrolled',
            'mockhdfc_payment',
        ),

        'cron' => array(
            'hdfc_mpr_generate',
            'setl_initiate',
            'setl_reconcile_generate',
            'setl_return_generate',
            'payment_auth_notify',
            'payment_timeout',
            'merchant_daily_report',
            'merchant_post_beneficiary_file',
            'merchant_notify_holiday',
            'payment_auto_capture',
            'payment_verify_multiple',
            'refund_netbanking_generate_excel',
            'refund_generate_excel',
            'payment_refund_authorized',
            'payment_capture_reminder',
            'emi_generate_excel',
            'es_migrate_entity',
            'setl_post_details_old',
            'order_refund_multiple_authorized',
        ),

        'mailgun' => array(
            'hdfc_mpr_reconcile',
            'reconciliate',
        ),

        'hosted' => array(
            'merchant_secret',
        ),

        'h2h' => array(
            'setl_reconcile_h2h',
        ),
    );

    public static $slaveRoutes = [
        // TODO: Uncomment this when slave variables issue is fixed.
        //'es_migrate_entity',
    ];

    protected static $jsonpRoutes = array(
        'checkout',
        'payment_create_jsonp',
        'merchant_public_get_banks',
        'merchant_methods',
    );

    public static $routeNameToFeatureMap = array(
        'dummy_feature'             => 'dummy',
        'submerchant_create'        => 'aggregator',
        'customer_create'           => 'tokens',
        'customer_update'           => 'tokens',
        'customer_get'              => 'tokens',
        'customer_delete'           => 'tokens',
        'customer_delete_token'     => 'tokens',
        'customer_fetch_tokens'     => 'tokens',
        'payment_create_wallet'     => 's2swallet',
        'setl_combined_report'      => 'setl_report',
        'customer_get_saved_status' => 'cardsaving',
        'customer_logout_global'    => 'cardsaving',
        'app_delete_token'          => 'cardsaving',
        'otp_post'                  => 'cardsaving',
        'otp_verify'                => 'cardsaving',
    );

    protected static $router;

    public static function setRouter($router)
    {
        self::$router = $router;
    }

    public static function getCurrentRouteName()
    {
        $router = self::$router;

        return $router->currentRouteName();
    }

    public static function getSlaveRoutes()
    {
        return self::$slaveRoutes;
    }

    public static function getUrl($routeName, array $parameters = array(), $key = '', $secret = '')
    {
        if (($secret === '') and
            ($key !== ''))
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

    public static function getUrlWithPublicCallbackAuth(array $parameters = array(), $key = '')
    {
        if ($key === '')
        {
            $key = \BasicAuth::getPublicKey();
        }

        return self::getUrl('payment_callback_with_key_post', $parameters, $key);
    }

    public static function getUrlWithAuth($relativeUrl, $key = '', $secret = '')
    {
        return self::getSchemaHostAndAuth($key, $secret) . $relativeUrl;
    }

    protected static function getSchemaHostAndAuth($key = '', $secret = '')
    {
        $request = \Request::getFacadeRoot();

        $schema = $request->getScheme() . '://';
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

        return in_array($route, $jsonpRoutes);
    }

    public static function addRoutes($type)
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

    public static function defineApiRoutes()
    {
        $router = self::$router;

        $router->group(array('prefix' => 'v1'), function () use ($router)
        {
            //
            // First define internal routes and then private and finally public
            // If by mistake a route is defined twice in say internal and public,
            // then it will go into internal app auth and will not expose the route.
            // This must not happen though.
            //
            self::addRoutes('internal');
            self::addRoutes('private');
            self::addRoutes('public');
            self::addRoutes('publicCallback');
            self::addRoutes('proxy');
            self::addRoutes('direct');
        });

    }

    public static function defineAllExtraRoutes()
    {
        $router = self::$router;

        $router->any('{all}', function ($uri)
        {
            return ApiResponse::routeNotFound();
        })->where('all', '.*');
    }

    public static function defineRootApiRoute()
    {
        $router = self::$router;

        $router->get('/', function ()
        {
            $response['message'] = "Welcome to Razorpay API.";

            return ApiResponse::json($response);
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
