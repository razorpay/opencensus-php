<?php

namespace RZP\Http;

use ApiResponse;

final class Route
{
    /*
     | The order in which routes are defined is very important.
     | Whenever the order of routes is changed,
     | make sure to run the full test suite
     */

    protected static $apiRoutes = array(
        'account'                                 => ['get',      'account',                                        'PublicController@getAccount'                                       ],
        'checkout'                                => ['get',      'checkout',                                       'MerchantController@getCheckout'                                    ],
        'checkout_public'                         => ['get',      'checkout/public',                                'MerchantController@getCheckoutPublic'                              ],
        'merchant_methods'                        => ['get',      'methods',                                        'MerchantController@getPaymentMethods'                              ],
        'merchant_checkout_preferences'           => ['get',      'preferences',                                    'MerchantController@getCheckoutPreferences'                         ],
        'payment_create'                          => ['post',     'payments',                                       'PaymentCreateController@postCreatePayment'                         ],
        'payment_create_private'                  => ['post',     'payments/create',                                'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_create_recurring'                => ['post',     'payments/create/recurring',                      'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_create_private_old'              => ['post',     'payments/create/redirect',                       'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_create_checkout'                 => ['post',     'payments/create/checkout',                       'PaymentCreateController@postCreatePaymentCheckoutCallback'         ],
        'payment_create_jsonp'                    => ['get',      'payments/create/jsonp',                          'PaymentCreateController@getCreatePaymentJsonp'                     ],
        'payment_create_ajax'                     => ['post',     'payments/create/ajax',                           'PaymentCreateController@postAJAX'                                  ],
        'payment_create_fees'                     => ['post',     'payments/create/fees',                           'PaymentCreateController@postCreatePaymentFees'                     ],
        'payment_create_wallet'                   => ['post',     'payments/create/wallet',                         'PaymentCreateController@postCreateWalletPayment'                   ],
        'payment_callback_post'                   => ['post',     'payments/{id}/callback/{hash}',                  'PaymentCreateController@postCallback'                              ],
        'payment_callback_get'                    => ['get',      'payments/{id}/callback/{hash}',                  'PaymentCreateController@postCallback'                              ],
        'payment_callback_with_key_post'          => ['post',     'payments/{id}/callback/{hash}/{key}',            'PaymentCreateController@postCallback'                              ],
        'payment_callback_with_key_get'           => ['get',      'payments/{id}/callback/{hash}/{key}',            'PaymentCreateController@postCallback'                              ],
        'payment_get_status'                      => ['get',      'payments/{id}/status',                           'PaymentController@getPaymentStatusForAsyncPayments'                ],
        'payment_otp_submit'                      => ['post',     'payments/{id}/otp_submit/{hash}',                'PaymentCreateController@postOtpSubmit'                             ],
        'payment_otp_resend'                      => ['post',     'payments/{id}/otp_resend',                       'PaymentCreateController@postOtpResend'                             ],
        'payment_topup_ajax'                      => ['post',     'payments/{id}/topup/ajax',                       'PaymentCreateController@postTopupAjax'                             ],
        'payment_topup_post'                      => ['post',     'payments/{id}/topup',                            'PaymentCreateController@postTopup'                                 ],
        'payment_redirect_callback'               => ['post',     'payments/{id}/redirect_callback',                'PaymentCreateController@postRedirectCallback'                      ],
        'payment_refund'                          => ['post',     'payments/{id}/refund',                           'PaymentController@postRefund'                                      ],
        'batch_create'                            => ['post',     'batches',                                        'BatchController@createBatch'                                       ],
        'batch_fetch_multiple'                    => ['get',      'batches',                                        'BatchController@getBatches'                                        ],
        'batch_fetch_by_id'                       => ['get',      'batches/{id}',                                   'BatchController@getBatchById'                                      ],
        'batch_process_file'                      => ['post',     'batches/process',                                'BatchController@processBatches'                                    ],
        'batch_retry'                             => ['post',     'batches/{id}/retry',                             'BatchController@retryBatch'                                        ],
        'batch_download_file'                     => ['get',      'batches/{id}/download',                          'BatchController@downloadBatch'                                     ],
        'payment_capture'                         => ['post',     'payments/{id}/capture',                          'PaymentController@postCapture'                                     ],
        'payment_verify'                          => ['get',      'payments/{id}/verify',                           'PaymentController@getVerify'                                       ],
        'payment_force_authorize'                 => ['post',     'payments/{id}/force_authorize',                  'PaymentController@postForceAuthorize'                              ],
        'payment_cancel'                          => ['get',      'payments/{id}/cancel',                           'PaymentController@postCancel'                                      ],
        'payment_authorize_failed'                => ['post',     'payments/{id}/authorize_failed',                 'PaymentController@postAuthorizeFailedPayment'                      ],
        'payment_authorize_refund'                => ['post',     'payments/{id}/authorize_refund',                 'PaymentController@postRefundAuthorized'                            ],
        'payment_add_metadata'                    => ['post',     'payments/{id}/metadata',                         'PaymentController@postPaymentMetadata'                             ],
        'payment_fetch_by_id'                     => ['get',      'payments/{id}',                                  'PaymentController@getPayment'                                      ],
        'payment_fetch_multiple'                  => ['get',      'payments',                                       'PaymentController@getPayments'                                     ],
        'payment_fetch_card_details'              => ['get',      'payments/{id}/card',                             'PaymentController@getCardForPayment'                               ],
        'payment_fetch_refunds'                   => ['get',      'payments/{id}/refunds',                          'PaymentController@getRefundsForPayment'                            ],
        'payment_fetch_refund_by_id'              => ['get',      'payments/{paymentId}/refunds/{rfndId}',          'PaymentController@getRefundByRefundAndPaymentId'                   ],
        'payment_auth_notify'                     => ['get',      'payments/auth/notify',                           'PaymentController@getAuthNotify',                                  ],
        'payment_timeout'                         => ['post',     'payments/timeout',                               'PaymentController@postTimeout'                                     ],
        'payment_auto_capture'                    => ['post',     'payments/autocapture',                           'PaymentController@postAutoCapture'                                 ],
        'payment_auto_capture_email'              => ['get',      'payments/autocapture/email',                     'PaymentController@getAutoCaptureEmail'                             ],
        'payment_verify_multiple'                 => ['post',     'payments/verify/{filter}',                       'PaymentController@postVerifyPayments'                              ],
        'payment_capture_reminder'                => ['get',      'payments/all/reminder',                          'PaymentController@sendReminderMailForAuthorizedPayments'           ],
        'payment_refund_authorized'               => ['post',     'payments/refund/authorized',                     'PaymentController@postRefundOldAuthorizedPayments'                 ],
        'payment_capture_verify'                  => ['post',     'payments/{id}/verify/capture',                   'PaymentController@postCaptureVerify'                               ],
        'payment_authorize_time_out'              => ['post',     'payments/authorize/timeout/{ids}',               'PaymentController@postAuthorizeLockTimeOut'                        ],
        'refund_fetch_by_id'                      => ['get',      'refunds/{id}',                                   'RefundController@getRefund'                                        ],
        'refund_fetch_multiple'                   => ['get',      'refunds',                                        'RefundController@getRefunds'                                       ],
        'refund_netbanking_generate_excel'        => ['post',     'refunds/netbanking/excel',                       'RefundController@generateNetbankingRefunds'                        ],
        'refund_generate_excel'                   => ['post',     'refunds/excel',                                  'RefundController@generateRefunds'                                  ],
        'refund_verify'                           => ['post',     'refunds/{ids}/verify',                           'RefundController@postRefundVerify'                                 ],
        'refund_create_missing_txn'               => ['post',     'refunds/transaction',                            'RefundController@postRefundsTransactions'                          ],
        'refund_gateway_manual'                   => ['post',     'refunds/{ids}/gateway',                          'RefundController@postManualGatewayRefund'                          ],
        'card_fetch_by_id'                        => ['get',      'cards/{id}',                                     'PaymentController@getCard'                                         ],
        'card_fetch_multiple'                     => ['get',      'cards',                                          'PaymentController@getCards'                                        ],
        'iin_fetch_by_iin'                        => ['get',      'iins/{id}',                                      'CardController@getIin'                                             ],
        'iin_fetch_multiple'                      => ['get',      'iins',                                           'CardController@getIins'                                            ],
        'iin_add'                                 => ['post',     'iins',                                           'CardController@postIin'                                            ],
        'iin_upload'                              => ['post',     'iins/upload',                                    'CardController@uploadIin'                                          ],
        'iin_edit'                                => ['put',      'iins/{id}',                                      'CardController@editIin'                                            ],
        'iin_generate_post'                       => ['post',     'iins/import/generate',                           'CardController@postIinGenerate'                                    ],
        'merchant_public_get_banks'               => ['get',      'banks',                                          'MerchantController@getBanksPublic'                                 ],
        'merchant_secret'                         => ['get',      'keys/{id}/secret',                               'MerchantController@getKeySecret'                                   ],
        'merchant_get_banks'                      => ['get',      'merchants/{id}/banks',                           'MerchantController@getBanks'                                       ],
        'merchant_set_banks'                      => ['post',     'merchants/{id}/banks',                           'MerchantController@setBanks'                                       ],
        'merchant_daily_report'                   => ['post',     'merchants/report',                               'MerchantController@sendDailyReport'                                ],
        'merchant_create'                         => ['post',     'merchants',                                      'MerchantController@postCreateMerchant'                             ],
        'merchant_fetch'                          => ['get',      'merchants/{id}',                                 'MerchantController@getMerchant'                                    ],
        'merchant_edit'                           => ['put',      'merchants/{id}',                                 'MerchantController@putMerchant'                                    ],
        'merchant_edit_config'                    => ['put',      'account/config',                                 'MerchantController@putMerchantConfig'                              ],
        'merchant_edit_config_logo'               => ['post',     'account/config/logo',                            'MerchantController@postMerchantConfigLogo'                         ],
        'merchant_delete_config_logo'             => ['delete',   'account/config/logo',                            'MerchantController@deleteMerchantConfigLogo'                       ],
        'merchant_sub_create'                     => ['post',     'submerchants',                                   'MerchantController@postCreateSubMerchant'                          ],
        'merchant_fetch_config'                   => ['get',      'account/config',                                 'MerchantController@getAccountConfig'                               ],
        'merchant_edit_email'                     => ['put',      'merchants/{id}/email',                           'MerchantController@putMerchantEmail'                               ],
        'merchant_fetch_multiple'                 => ['get',      'merchants',                                      'MerchantController@getMerchants'                                   ],
        'merchant_create_key'                     => ['post',     'merchants/{id}/keys',                            'MerchantController@postCreateKeys'                                 ],
        'merchant_fetch_keys'                     => ['get',      'merchants/{id}/keys',                            'MerchantController@getKeys'                                        ],
        'merchant_replace_key'                    => ['put',      'merchants/{merchantId}/keys/{keyId}',            'MerchantController@putKeys'                                        ],
        'merchant_fetch_webhooks'                 => ['get',      'merchants/{id}/webhooks',                        'MerchantController@getMerchantWebhooks'                            ],
        'merchant_assign_pricing'                 => ['post',     'merchants/{id}/pricing',                         'MerchantController@postAssignPricingPlan'                          ],
        'merchant_get_pricing'                    => ['get',      'merchants/{id}/pricing',                         'MerchantController@getPricingPlan'                                 ],
        'merchant_add_bank_account'               => ['post',     'merchants/{id}/bank_account',                    'MerchantController@postBankAccount'                                ],
        'merchant_fetch_bank_account'             => ['get',      'merchants/{id}/bank_account',                    'MerchantController@getBankAccount'                                 ],
        'merchant_generate_test_bank_acnt'        => ['post',     'merchants/bank_account/generate/test',           'MerchantController@postGenerateTestBankAccounts'                   ],
        'merchant_create_terminal'                => ['post',     'merchants/{id}/terminals',                       'MerchantController@postCreateTerminal'                             ],
        'merchant_get_terminals'                  => ['get',      'merchants/{id}/terminals',                       'MerchantController@getTerminals'                                   ],
        'merchant_get_terminal'                   => ['get',      'merchants/{mid}/terminals/{tid}',                'MerchantController@getTerminal'                                    ],
        'merchant_delete_terminal'                => ['delete',   'merchants/{mid}/terminals/{tid}',                'MerchantController@deleteTerminal'                                 ],
        'merchant_modify_terminal'                => ['put',      'merchants/{mid}/terminals/{tid}',                'MerchantController@putTerminal'                                    ],
        'merchant_copy_terminal'                  => ['post',     'merchants/{mid}/terminals/{tid}/copy',           'MerchantController@postCopyTerminal'                               ],
        'merchant_put_payment_methods'            => ['put',      'merchants/{mid}/methods',                        'MerchantController@putMethods'                                     ],
        'merchant_activate'                       => ['post',     'merchants/{id}/activate',                        'MerchantController@postActivate'                                   ],
        'merchant_live_enable'                    => ['post',     'merchants/{id}/live/enable',                     'MerchantController@postLiveEnable'                                 ],
        'merchant_live_disable'                   => ['post',     'merchants/{id}/live/disable',                    'MerchantController@postLiveDisable'                                ],
        'merchant_fetch_balance'                  => ['get',      'merchants/{id}/balance',                         'MerchantController@getBalance'                                     ],
        'merchant_edit_free_credits'              => ['post',     'merchants/{id}/credits',                         'MerchantController@postAmountCredits',                             ],
        'merchant_beneficiary_file'               => ['get',      'merchants/beneficiary/file',                     'MerchantController@getMerchantBeneficiaryFile'                     ],
        'merchant_post_beneficiary_file'          => ['post',     'merchants/beneficiary/file/bank',                'MerchantController@postMerchantBeneficiaryFile'                    ],
        'merchant_notify_holiday'                 => ['post',     'merchants/notify/holiday',                       'MerchantController@postMerchantsNotifyHoliday'                     ],
        'balance_fetch'                           => ['get',      'balance',                                        'MerchantController@getAccountBalance'                              ],
        'credits_create'                          => ['post',     'merchants/{id}/credits_log',                     'MerchantController@postCreateCreditsLog'                           ],
        'credits_fetch_by_id'                     => ['get',      'merchants/{mid}/credits/{id}',                   'MerchantController@getCreditsLog'                                  ],
        'credits_edit'                            => ['put',      'merchants/{mid}/credits/{id}',                   'MerchantController@putCreditsLog'                                  ],
        'credits_delete'                          => ['delete',   'merchants/{mid}/credits/{id}',                   'MerchantController@deleteCreditsLog'                               ],
        'credits_fetch_multiple'                  => ['get',      'credits',                                        'MerchantController@getCreditsLogs'                                 ],
        'key_fetch_by_id'                         => ['get',      'keys/{id}',                                      'KeyController@getKey'                                              ],
        'key_fetch_multiple'                      => ['get',      'keys',                                           'KeyController@getKeys'                                             ],
        'terminal_delete'                         => ['delete',   'terminals/{id}',                                 'TerminalController@deleteTerminal'                                 ],
        'terminal_edit'                           => ['put',      'terminals/{id}',                                 'TerminalController@putTerminal'                                    ],
        'terminal_restore'                        => ['put',      'terminals/{id}/restore',                         'TerminalController@restoreTerminal',                               ],
        'terminal_toggle'                         => ['put',      'terminals/{id}/toggle',                          'TerminalController@toggleTerminal'                                 ],
        'terminal_check_encrypted_value'          => ['post',     'terminals/{id}/secret',                          'TerminalController@postCheckTerminalEncryptedValue'                ],
        'webhook_create'                          => ['post',     'webhooks',                                       'MerchantController@postWebhook'                                    ],
        'webhook_edit'                            => ['put',      'webhooks/{id}',                                  'MerchantController@putWebhook'                                     ],
        'webhook_fetch'                           => ['get',      'webhooks/{id}',                                  'MerchantController@getWebhook'                                     ],
        'webhook_fetch_multiple'                  => ['get',      'webhooks',                                       'MerchantController@getWebhooks'                                    ],
        'pricing_create_plan'                     => ['post',     'pricing',                                        'PricingController@postCreatePricingPlan'                           ],
        'pricing_upload_plan'                     => ['post',     'pricing/upload',                                 'PricingController@postUploadPricingPlan'                           ],
        'pricing_get_plans'                       => ['get',      'pricing',                                        'PricingController@getPricingPlans'                                 ],
        'pricing_get_merchant_plans'              => ['get',      'pricing/merchants',                              'PricingController@getMerchantPricingPlans'                         ],
        'pricing_get_gateway_plans'               => ['get',      'pricing/gateways',                               'PricingController@getGatewayPricingPlans'                          ],
        'pricing_supported_networks'              => ['get',      'pricing/networks',                               'PricingController@getSupportedNetworks'                            ],
        'pricing_get_plan'                        => ['get',      'pricing/{id}',                                   'PricingController@getPricingPlan'                                  ],
        'pricing_get_plan_rule'                   => ['get',      'pricing/{planId}/rule/{ruleId}',                 'PricingController@getPricingPlanRule'                              ],
        'pricing_add_plan_rule'                   => ['post',     'pricing/{id}/rule',                              'PricingController@postAddPricingPlanRule'                          ],
        'pricing_delete_plan_rule'                => ['delete',   'pricing/{planId}/rule/{ruleId}',                 'PricingController@deletePricingPlanRule'                           ],
        'pricing_delete_plan_rule_force'          => ['delete',   'pricing/{planId}/rule/{ruleId}/force',           'PricingController@deletePricingPlanRuleForce'                      ],
        'schedule_create'                         => ['post',     'schedules',                                      'ScheduleController@postSchedule'                                   ],
        'schedule_fetch'                          => ['get',      'schedules/{id}',                                 'ScheduleController@getSchedule'                                    ],
        'schedule_fetch_multiple'                 => ['get',      'schedules',                                      'ScheduleController@getSchedules'                                   ],
        'schedule_delete'                         => ['delete',   'schedules/{id}',                                 'ScheduleController@deleteSchedule'                                 ],
        'schedule_update'                         => ['put',      'schedules/{id}',                                 'ScheduleController@putSchedule'                                    ],
        'schedule_assign'                         => ['post',     'merchants/{id}/schedules',                       'MerchantController@assignSettlementSchedule'                       ],
        'transaction_fetch_by_id'                 => ['get',      'transactions/{id}',                              'TransactionController@getTransaction'                              ],
        'transaction_fetch_multiple'              => ['get',      'transactions',                                   'TransactionController@getTransactions'                             ],
        'transaction_monthly_report'              => ['get',      'transactions/report',                            'TransactionController@getMonthlyReport'                            ],
        'migrate_transactions'                    => ['post',     'transactions/migrate',                           'TransactionController@postMigrateOlderTransactions'                ],
        'setl_fetch_by_id'                        => ['get',      'settlements/{id}',                               'SettlementController@getSettlement'                                ],
        'setl_fetch_multiple'                     => ['get',      'settlements',                                    'SettlementController@getSettlements'                               ],
        'setl_fetch_transactions'                 => ['get',      'settlements/{id}/transactions',                  'SettlementController@getSettlementTransactions'                    ],
        'setl_edit'                               => ['put',      'settlements/{id}',                               'SettlementController@putEditSettlement'                            ],
        'setl_fixer'                              => ['get',      'settlements/fixer',                              'SettlementController@getSettlementFixer'                           ],
        'setl_delete_file'                        => ['delete',   'settlements/file/{setlFileType}',                'SettlementController@deleteSettlementFile'                         ],
        'setl_initiate'                           => ['post',     'settlements/initiate/{channel?}',                'SettlementController@postSettlementInitiate'                       ],
        'setl_initiate_schedule'                  => ['post',     'settlements/initiate2/{channel?}',               'SettlementController@postSettlementInitiateV2'                     ],
        'setl_file_generate'                      => ['post',     'settlements/file/generate',                      'SettlementController@postSettlementFileGenerate'                   ],
        'setl_reconcile_generate'                 => ['post',     'settlements/reconcile/generate',                 'SettlementController@postSettlementReconcileGenerate'              ],
        'setl_reconcile'                          => ['post',     'settlements/reconcile',                          'SettlementController@postSettlementReconcile'                      ],
        'setl_reconcile_h2h'                      => ['post',     'settlements/h2hreconcile',                       'SettlementController@postH2HSettlementReconcile'                   ],
        'setl_return_generate'                    => ['post',     'settlements/return/generate',                    'SettlementController@postSettlementReturnGenerate'                 ],
        'setl_return'                             => ['post',     'settlements/return',                             'SettlementController@postSettlementReturn'                         ],
        'setl_calc_previous_fees'                 => ['post',     'settlements/fees/previous',                      'SettlementController@postSettlementCalculateFees',                 ],
        'setl_get_details'                        => ['get',      'settlements/{id}/details',                       'SettlementController@getSettlementDetails',                        ],
        'setl_post_details_old'                   => ['post',     'settlements/details',                            'SettlementController@postSettlementDetailsForOldTxns'              ],
        'setl_combined_report'                    => ['get',      'settlements/report/combined',                    'SettlementController@getSettlementCombinedReport'                  ],
        'daily_setl_calc_previous_fees'           => ['post',     'dailysettlements/fees/previous',                 'SettlementController@postDailySettlementCalculatePreviousFees'     ],
        'daily_setl_fetch_by_id'                  => ['get',      'dailysettlements/{id}',                          'SettlementController@getDailySettlement'                           ],
        'daily_setl_fetch_multiple'               => ['get',      'dailysettlements',                               'SettlementController@getDailySettlements'                          ],
        'adj_fetch_by_id'                         => ['get',      'adjustments/{id}',                               'AdjustmentController@getAdjustment'                                ],
        'adj_fetch_multiple'                      => ['get',      'adjustments',                                    'AdjustmentController@getAdjustments'                               ],
        'adj_add'                                 => ['post',     'adjustments',                                    'AdjustmentController@postAdjustment'                               ],
        'mock_hdfc_enroll'                        => ['post',     'gateway/mock_hdfc/enroll',                       'MockGatewayController@enroll'                                      ],
        'mock_hdfc_payment'                       => ['post',     'gateway/mock_hdfc/payment',                      'MockGatewayController@payment'                                     ],
        'mock_hdfc_auth_enrolled'                 => ['post',     'gateway/mock_hdfc/auth_enrolled',                'MockGatewayController@authEnrolled'                                ],
        'mock_hdfc_3dsecure'                      => ['post',     'gateway/3dsecure',                               'MockGatewayController@post3dSecure'                                ],
        'mock_cybersource_acs'                    => ['post',     'gateway/acs/{gateway}',                          'MockGatewayController@postAcs'                                     ],
        'mock_atom_init_payment'                  => ['post',     'gateway/mockanb',                                'MockGatewayController@postAtomInitPayment'                         ],
        'mock_atom_choose_org'                    => ['get',      'gateway/mockanb',                                'MockGatewayController@getAtomChooseOrg'                            ],
        'mock_atom_rzp_payment'                   => ['post',     'gateway/mockanb/payment',                        'MockGatewayController@postAtomRzpPayment'                          ],
        'mock_atom_rzp_payment_submit'            => ['post',     'gateway/mockanb/payment/submit',                 'MockGatewayController@postAtomRzpPaymentSubmit'                    ],
        'mock_axis_migs_payment'                  => ['post',     'gateway/mockaxismigs/payment',                   'MockGatewayController@postAxisPayment'                             ],
        'mock_first_data_payment'                 => ['post',     'gateway/mockfirstdata/payment',                  'MockGatewayController@postFirstDataPayment'                        ],
        'mock_axis_genius_payment'                => ['post',     'gateway/mockaxisgenius/payment',                 'MockGatewayController@postAxisGeniusPayment'                       ],
        'mock_paytm_payment'                      => ['post',     'gateway/mockpaytm/payment',                      'MockGatewayController@postPaytmPayment'                            ],
        'mock_mobikwik_payment'                   => ['post',     'gateway/mockmobikwik/payment',                   'MockGatewayController@postMobikwikPayment'                         ],
        'mock_billdesk_payment'                   => ['post',     'gateway/mockbilldesk/payment',                   'MockGatewayController@postBilldeskPayment'                         ],
        'mock_ebs_payment'                        => ['post',     'gateway/mockebs/payment',                        'MockGatewayController@postEbsPayment'                              ],
        'mock_sharp_payment_post'                 => ['post',     'gateway/mocksharp/payment',                      'MockGatewayController@getSharpPayment'                             ],
        'mock_sharp_payment_get'                  => ['get',      'gateway/mocksharp/payment',                      'MockGatewayController@getSharpPayment'                             ],
        'mock_amex_payment'                       => ['post',     'gateway/mockamex/payment',                       'MockGatewayController@postAmexPayment'                             ],
        'mock_sharp_payment_submit'               => ['post',     'gateway/mocksharp/payment/submit',               'MockGatewayController@postSharpPayment'                            ],
        'mock_netbanking_payment'                 => ['post',     'gateway/mock/netbanking/{bank}',                 'MockGatewayController@postNetbankingPayment'                       ],
        'mock_wallet_payment'                     => ['post',     'gateway/mock/wallet/{wallet}',                   'MockGatewayController@walletPayment'                               ],
        'mock_wallet_payment_get'                 => ['get',      'gateway/mock/wallet/{wallet}',                   'MockGatewayController@walletPayment'                               ],
        'mock_wallet_payment_with_paymentid'      => ['post',     'gateway/mock/wallet/{wallet}/{paymentId}',       'MockGatewayController@walletPayment'                               ],
        'mock_upi_icici_payment'                  => ['post',     'gateway/mock/upi/{bank}',                        'MockGatewayController@postUpiPayment'                              ],
        'admin_fetch_entity_multiple'             => ['get',      'admin/{type}',                                   'AdminController@getEntityMultiple'                                 ],
        'admin_fetch_entity_by_id'                => ['get',      'admin/{type}/{id}',                              'AdminController@getEntityById'                                     ],
        'send_test_newsletter'                    => ['post',     'admin/newsletter/test',                          'AdminController@postSendTestNewsletter'                            ],
        'send_newsletter'                         => ['post',     'admin/newsletter/mail',                          'AdminController@postSendNewsletter'                                ],
        'gateway_payment_callback_axis'           => ['post',     'callback/axis',                                  'GatewayController@callbackAxis'                                    ],
        'gateway_payment_callback_get'            => ['post',     'callback/{gateway}',                             'GatewayController@callbackGateway'                                 ],
        'gateway_payment_callback_post'           => ['get',      'callback/{gateway}',                             'GatewayController@callbackGateway'                                 ],
        'gateway_payment_callback_kotak'          => ['get',      'gateway/netbanking_kotak/callback',              'GatewayController@callbackKotak'                                   ],
        'gateway_payment_callback_kotak_cancel'   => ['post',     'gateway/netbanking_kotak/callback',              'GatewayController@callbackKotakCancel'                             ],
        'reconciliate'                            => ['post',     'reconciliate',                                   'ReconciliatorController@postReconciliation'                        ],
        'dummy_return_callback'                   => ['post',     'return/callback',                                'PaymentController@postDummyReturnCallback'                         ],
        'dummy_critical_error'                    => ['get',      'trigger/error',                                  'AdminController@getTriggerError'                                   ],
        'dummy_route'                             => ['post',     'dummy/route',                                    'PaymentController@postDummyRoute'                                  ],
        'transparent_redirect_get'                => ['get',      'redirect',                                       'AdminController@getTransparentRedirect'                            ],
        'transparent_redirect_post'               => ['post',     'redirect',                                       'AdminController@postTransparentRedirect'                           ],
        'settlement_compute_tax'                  => ['post',     'settlements/compute/tax',                        'SettlementController@postComputeSettlementServiceTax'              ],
        'daily_settlement_compute_tax'            => ['post',     'dailysettlements/compute/tax',                   'SettlementController@postComputeDailySettlementServiceTax'         ],
        'feature_dummy'                           => ['get',      'dummy',                                          'MerchantController@getDummyFeatures'                               ],
        'emi_plan_add'                            => ['post',     'emi',                                            'EmiController@addEmiPlan'                                          ],
        'emi_plans_fetch_multiple'                => ['get',      'emi',                                            'EmiController@fetchEmiPlans'                                       ],
        'emi_plan_fetch_by_id'                    => ['get',      'emi/{id}',                                       'EmiController@fetchEmiPlanById'                                    ],
        'emi_plan_delete'                         => ['delete',   'emi/{id}',                                       'EmiController@deleteEmiPlan'                                       ],
        'emi_generate_excel'                      => ['post',     'emi/generate/excel',                             'EmiController@generateEmiExcel'                                    ],
        'order_create'                            => ['post',     'orders',                                         'OrderController@createOrder'                                       ],
        'order_fetch'                             => ['get',      'orders',                                         'OrderController@getOrders'                                         ],
        'order_fetch_by_id'                       => ['get',      'orders/{id}',                                    'OrderController@fetchOrderById'                                    ],
        'order_payments'                          => ['get',      'orders/{id}/payments',                           'OrderController@fetchPayments'                                     ],
        'order_refund_multiple_authorized'        => ['post',     'orders/payments/refund',                         'PaymentController@postRefundMultipleAuthorizedPaymentsForOrders'   ],
        'reports_monthly_invoice'                 => ['get',      'reports/invoice',                                'MerchantController@getInvoiceReport'                               ],
        'reports_monthly_invoice_v2'              => ['get',      'reports/invoice/v2',                             'MerchantController@getInvoiceReportV2'                             ],
        'reports_public_entity'                   => ['get',      'reports/{entity}',                               'MerchantController@getPublicEntityReport'                          ],
        'customer_create'                         => ['post',     'customers',                                      'CustomerController@createLocalCustomer'                            ],
        'customer_update'                         => ['put',      'customers/{id}',                                 'CustomerController@updateCustomer'                                 ],
        'customer_fetch_by_id'                    => ['get',      'customers/{id}',                                 'CustomerController@getCustomer'                                    ],
        'customer_fetch_multiple'                 => ['get',      'customers',                                      'CustomerController@getCustomers'                                   ],
        'customer_delete'                         => ['delete',   'customers/{id}',                                 'CustomerController@deleteCustomer'                                 ],
        'customer_add_bank_account'               => ['post',     'customers/{id}/bank_account',                    'CustomerController@postBankAccount'                                ],
        'customer_fetch_bank_account'             => ['get',      'customers/{id}/bank_account',                    'CustomerController@getBankAccounts'                                ],
        'customer_create_token'                   => ['post',     'customers/{id}/tokens',                          'CustomerController@addToken'                                       ],
        'customer_update_token'                   => ['put',      'customers/{id}/tokens/{token}',                  'CustomerController@updateToken'                                    ],
        'customer_fetch_token'                    => ['get',      'customers/{id}/tokens/{token}',                  'CustomerController@fetchToken'                                     ],
        'customer_fetch_tokens'                   => ['get',      'customers/{id}/tokens',                          'CustomerController@fetchTokens'                                    ],
        'customer_delete_token'                   => ['delete',   'customers/{id}/tokens/{token}',                  'CustomerController@deleteToken'                                    ],
        'customer_get_saved_status'               => ['get',      'customers/status/{contact}',                     'CustomerController@fetchGlobalCustomerStatus'                      ],
        'customer_logout_global'                  => ['delete',   'apps/logout',                                    'CustomerController@logoutCustomer'                                 ],
        'customer_create_address'                 => ['post',     'customers/{id}/addresses',                       'CustomerController@postCreateAddress'                              ],
        'customer_delete_address'                 => ['delete',   'customers/{id}/addresses/{address_id}',          'CustomerController@deleteAddress'                                  ],
        'customer_fetch_addresses'                => ['get',      'customers/{id}/addresses',                       'CustomerController@getAddresses'                                   ],
        'customer_set_primary_address'            => ['put',      'customers/{id}/addresses/{address_id}/primary',  'CustomerController@putPrimaryAddress'                              ],
        'invoice_create'                          => ['post',     'invoices',                                       'InvoiceController@createInvoice'                                   ],
        'invoice_fetch'                           => ['get',      'invoices/{id}',                                  'InvoiceController@getInvoice'                                      ],
        'invoice_fetch_multiple'                  => ['get',      'invoices',                                       'InvoiceController@getInvoices'                                     ],
        'invoice_send_notifications'              => ['post',     'invoices/notify',                                'InvoiceController@sendNotifications'                               ],
        'invoice_send_notification'               => ['post',     'invoices/{id}/notify/{medium}',                  'InvoiceController@sendNotification'                                ],
        'invoice_notification_update'             => ['put',      'invoices/{medium}',                              'InvoiceController@updateInvoiceNotificationStatus'                 ],
        'invoice_get_status'                      => ['get',      'invoices/{id}/status',                           'InvoiceController@getInvoiceStatus'                                ],
        'invoice_view'                            => ['get',      'i/{id}',                                         'InvoiceController@getInvoiceView'                                  ],
        'invoice_expire'                          => ['post',     'invoices/expire',                                'InvoiceController@expireInvoices'                                  ],
        // 'line_item_create'                        => ['post',     'line_items',                                     'LineItemController@createLineItem'                                 ],
        // 'line_item_fetch'                         => ['get',      'line_items/{id}',                                'LineItemController@getLineItem'                                    ],
        // 'line_item_fetch_multiple'                => ['get',      'line_items',                                     'LineItemController@getLineItems'                                   ],
        'app_delete_token'                        => ['delete',   'apps/tokens/{token}',                            'CustomerController@deleteTokenForGlobalCustomer'                   ],
        'app_fetch_tokens'                        => ['get',      'apps/tokens',                                    'CustomerController@fetchTokensForGlobalCustomer'                   ],
        'app_fetch_payments'                      => ['get',      'apps/payments',                                  'CustomerController@fetchPaymentsForGlobalCustomer'                 ],
        'bank_account_fetch'                      => ['get',      'account/bank_account',                           'MerchantController@getOwnBankAccount'                              ],
        'device_verify_token'                     => ['post',     'devices/{deviceToken}/verify',                   'CustomerController@validateDeviceToken'                            ],
        'otp_post'                                => ['post',     'otp/create',                                     'CustomerController@postOtp'                                        ],
        'otp_verify'                              => ['post',     'otp/verify',                                     'CustomerController@verifyOtp'                                      ],
        'sms_callback'                            => ['post',     'sms/{id}/callback',                              'CustomerController@updateSmsStatus'                                ],
        'es_migrate_entity'                       => ['post',     'es/migrate/{entityName}',                        'EsController@migrateEntity'                                        ],
        'gateway_create_absence'                  => ['post',     'gateway/absence',                                'GatewayController@postCreateGatewayAbsence'                        ],
        'gateway_update_absence'                  => ['put',      'gateway/absence/{id}',                           'GatewayController@putUpdateGatewayAbsence'                         ],
        'gateway_delete_absence'                  => ['delete',   'gateway/absence/{id}',                           'GatewayController@deleteGatewayAbsence'                            ],
        'gateway_fetch_absence'                   => ['get',      'gateway/absence',                                'GatewayController@getAbsentGateways'                               ],
        'scorecard'                               => ['get',      'scorecard',                                      'AdminController@getScorecard'                                      ],
        'billdesk_reconcile_cancelled'            => ['post',     'reconciliate/{gateway}/cancelled',               'ReconciliatorController@postReconciliateCancelledTransactions'     ],
        'feature_add'                             => ['post',     'features',                                       'FeatureController@addFeatures'                                     ],
        'feature_delete'                          => ['delete',   'features/{entityId}/{featureName}',              'FeatureController@deleteFeature'                                   ],
        'feature_get_multiple'                    => ['get',      'features/{entityId}',                            'FeatureController@getFeatures'                                     ],
        'feature_bulk_assign'                     => ['post',     'features/assign',                                'FeatureController@multiAssignFeature'                              ],
        'feature_bulk_remove'                     => ['post',     'features/remove',                                'FeatureController@multiRemoveFeature'                              ],
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
        'payment_redirect_callback',
        'payment_cancel',
        'payment_add_metadata',
        'payment_get_status',
        'invoice_get_status',
        'invoice_send_notification',
        'invoice_view',
        'merchant_public_get_banks',
        'merchant_methods',
        'merchant_checkout_preferences',
        'mock_atom_init_payment',
        'mock_atom_choose_org',
        'mock_atom_rzp_payment',
        'mock_atom_rzp_payment_submit',
        'mock_amex_payment',
        'mock_axis_migs_payment',
        'mock_first_data_payment',
        'mock_axis_genius_payment',
        'mock_paytm_payment',
        'mock_mobikwik_payment',
        'mock_netbanking_payment',
        'mock_billdesk_payment',
        'mock_ebs_payment',
        'mock_sharp_payment_post',
        'mock_sharp_payment_get',
        'mock_sharp_payment_submit',
        'mock_wallet_payment',
        'mock_wallet_payment_get',
        'mock_upi_icici_payment',
        'mock_wallet_payment_with_paymentid',
        'dummy_return_callback',
        'emi_plans_fetch_multiple',
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
        'payment_create_private',
        'payment_create_private_old',
        'payment_create_recurring',
        'payment_create_wallet',
        'payment_refund',
        'payment_capture',
        'payment_fetch_by_id',
        'payment_fetch_multiple',
        'payment_fetch_refunds',
        'payment_fetch_refund_by_id',
        'refund_fetch_by_id',
        'refund_fetch_multiple',
        'card_fetch_by_id',
        'order_create',
        'order_fetch',
        'order_fetch_by_id',
        'order_payments',
        'feature_dummy',
        'setl_combined_report',
        'customer_create',
        'customer_update',
        'customer_fetch_by_id',
        'customer_fetch_multiple',
        'customer_delete_token',
        'customer_fetch_token',
        'customer_fetch_tokens',
        'customer_add_bank_account',
        'customer_fetch_bank_account',
        'invoice_create',
        'invoice_fetch',
        'invoice_fetch_multiple',
        // 'invoice_send_notification',
        // 'line_item_create',
        // 'line_item_fetch',
        // 'line_item_fetch_multiple',
        'customer_create_address',
        'customer_delete_address',
        'customer_fetch_addresses',
        'customer_set_primary_address',
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
        'merchant_copy_terminal',
        'merchant_activate',
        'merchant_live_enable',
        'merchant_live_disable',
        'merchant_put_payment_methods',
        'merchant_get_banks',
        'merchant_set_banks',
        'merchant_edit_free_credits',
        'merchant_beneficiary_file',
        'merchant_fetch_webhooks',
        'merchant_post_beneficiary_file',
        'merchant_notify_holiday',
        'terminal_delete',
        'terminal_edit',
        'terminal_restore',
        'terminal_toggle',
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
        'setl_initiate_schedule',
        'setl_file_generate',
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
        'payment_authorize_time_out',
        'refund_create_missing_txn',
        'refund_gateway_manual',
        'refund_netbanking_generate_excel',
        'refund_generate_excel',
        'settlement_compute_tax',
        'daily_settlement_compute_tax',
        'mock_hdfc_enroll',
        'mock_hdfc_auth_enrolled',
        'mock_hdfc_payment',
        'iin_fetch_by_iin',
        'iin_fetch_multiple',
        'iin_add',
        'iin_upload',
        'iin_edit',
        'iin_generate_post',
        'send_test_newsletter',
        'send_newsletter',
        'emi_plan_add',
        'emi_plan_delete',
        'emi_plan_fetch_by_id',
        'emi_generate_excel',
        'refund_verify',
        'payment_capture_verify',
        'es_migrate_entity',
        'dummy_critical_error',
        'reconciliate',
        'credits_create',
        'credits_edit',
        'credits_delete',
        'invoice_send_notifications',
        'invoice_expire',
        'batch_process_file',
        'gateway_create_absence',
        'gateway_update_absence',
        'gateway_delete_absence',
        'gateway_fetch_absence',
        'order_refund_multiple_authorized',
        'scorecard',
        'billdesk_reconcile_cancelled',
        'migrate_transactions',
        'schedule_create',
        'schedule_fetch',
        'schedule_fetch_multiple',
        'schedule_delete',
        'schedule_update',
        'schedule_assign',
        'feature_get_multiple',
        'feature_add',
        'feature_delete',
        'feature_bulk_assign',
        'feature_bulk_remove'
    );

    public static $proxy = array(
        'payment_fetch_card_details',
        'payment_authorize_refund',
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
        'webhook_create',
        'webhook_edit',
        'webhook_fetch',
        'webhook_fetch_multiple',
        'balance_fetch',
        'reports_monthly_invoice',
        'reports_monthly_invoice_v2',
        'reports_public_entity',
        'bank_account_fetch',
        'merchant_edit_config',
        'merchant_edit_config_logo',
        'merchant_delete_config_logo',
        'merchant_fetch_balance',
        'merchant_fetch_config',
        'merchant_sub_create',
        'customer_delete',
        'customer_create_token',
        'customer_update_token',
        'device_verify_token',
        'app_fetch_tokens',
        'credits_fetch_multiple',
        'credits_fetch_by_id',
        'batch_create',
        'batch_fetch_multiple',
        'batch_fetch_by_id',
        'batch_retry',
        'batch_download_file',
    );

    public static $direct = array(
        'account',
        'dummy_route',
        'sms_callback',
        'reconciliate',
        'checkout_public',
        'mock_hdfc_3dsecure',
        'mock_cybersource_acs',
        'transparent_redirect_get',
        'transparent_redirect_post',
        'gateway_payment_callback_get',
        'gateway_payment_callback_post',
        'gateway_payment_callback_kotak',
        'gateway_payment_callback_kotak_cancel',
    );

    public static $internalApps = array(
        'dashboard' => array('*'),

        'mock_gateways' => array(
            'mock_hdfc_enroll',
            'mock_hdfc_auth_enrolled',
            'mock_hdfc_payment',
        ),

        'cron' => array(
            'setl_initiate',
            'setl_initiate_schedule',
            'setl_reconcile_generate',
            'setl_return_generate',
            'payment_auth_notify',
            'payment_timeout',
            'scorecard',
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
            'invoice_send_notifications',
            'invoice_expire',
            'batch_process_file',
            'order_refund_multiple_authorized',
            'migrate_transactions',
            'merchant_migrate_features',
        ),

        'mailgun' => array(
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
        'feature_dummy'                 => 'dummy',
        'merchant_sub_create'           => 'aggregator',
        'customer_delete'               => 'tokens',
        'customer_delete_token'         => 'tokens',
        'customer_fetch_tokens'         => 'tokens',
        'payment_create_wallet'         => 's2swallet',
        'payment_create_recurring'      => 'recurring',
        'payment_create_private_old'    => 's2s',
        'setl_combined_report'          => 'setl_report',
        'customer_get_saved_status'     => 'cardsaving',
        'customer_logout_global'        => 'cardsaving',
        'app_delete_token'              => 'cardsaving',
        'otp_post'                      => 'cardsaving',
        'otp_verify'                    => 'cardsaving',
        'invoice_create'                => 'invoice',
        'invoice_fetch'                 => 'invoice',
        'invoice_fetch_multiple'        => 'invoice',
        'invoice_send_notification'     => 'invoice',
        'invoice_notification_update'   => 'invoice',
        'invoice_get_status'            => 'invoice',
    );

    const RAZORPAYJS_ROUTES = array(
        'payment_cancel',
        'payment_create_ajax',
        'payment_otp_submit',
        'payment_otp_resend',
        'payment_topup_ajax');

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

    public static function getSlaveRoutes()
    {
        return self::$slaveRoutes;
    }

    public function getUrl($routeName, array $parameters = array(), $key = '', $secret = '')
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

    public function getUrlWithPublicAuth($routeName, array $parameters = array(), $key = '')
    {
        if ($key === '')
        {
            $key = $this->ba->getPublicKey();
        }

        return $this->getUrl($routeName, $parameters, $key);
    }

    public function getUrlWithPublicAuthInQueryParam($routeName, array $parameters = array())
    {
        $key = $this->ba->getPublicKey();

        list($schema, $host) = $this->getSchemaAndHost();

        $parameters['key_id'] = $key;

        $urlSegment = \URL::route($routeName, $parameters, false);

        return $schema . $host . $urlSegment;
    }

    public function getUrlWithPublicCallbackAuth(array $parameters = array(), $key = '')
    {
        if ($key === '')
        {
            $key = $this->ba->getPublicKey();
        }

        return $this->getUrl('payment_callback_with_key_post', $parameters, $key);
    }

    public function getPublicCallbackUrlWithHash($pid , $key = '')
    {
        if ($key === '')
        {
            $key = $this->ba->getPublicKey();
        }

        $secret = $this->app->config->get('app.key');

        $hash = hash_hmac('sha1', $pid, $secret);

        $parameters = ['id' => $pid, 'hash' => $hash];

        return $this->getUrl('payment_callback_with_key_post', $parameters, $key);
    }

    public function getUrlWithAuth($relativeUrl, $key = '', $secret = '')
    {
        return $this->getSchemaHostAndAuth($key, $secret) . $relativeUrl;
    }

    protected function getSchemaHostAndAuth($key = '', $secret = '')
    {
        list($schema, $host) = $this->getSchemaAndHost();

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

    protected function getSchemaAndHost()
    {
        $request = \Request::getFacadeRoot();

        $schema = $request->getScheme() . '://';
        $host = $request->getHost();

        return [$schema, $host];
    }

    public function getDoNotLogURLs()
    {
        $doNotLogUrls = array(
            'v1/payments/create/jsonp',
            'payments/create/jsonp',
            self::$apiRoutes['payment_create_jsonp'][1],
            'v1/payments',
            'v1/payments/create',
            'v1/payments/create/recurring',
            'v1/payments/create/redirect',
            'v1/payments/create/checkout',
            'v1/payments/create/jsonp',
            'v1/payments/create/ajax',
            'v1/payments/create/fees',
            'v1/payments/create/wallet'
        );

        return $doNotLogUrls;
    }

    public static function isJsonpRoute($route)
    {
        $jsonpRoutes = self::$jsonpRoutes;

        return in_array($route, $jsonpRoutes);
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
        $info = self::$apiRoutes[$name];

        $method = $info[0];
        $uri = $info[1];
        $action = $info[2];

        $this->router->$method($uri, array('as' => $name, 'uses' => $action));
    }

    public function defineAllExtraRoutes()
    {
        $this->router->any('{all}', function ($uri)
        {
            return ApiResponse::routeNotFound();
        })->where('all', '.*');
    }

    public function defineRootApiRoute()
    {
        $this->router->get('/', function ()
        {
            $response['message'] = "Welcome to Razorpay API.";

            return ApiResponse::json($response);
        });
    }

    public function getApiRouteInCategory($category)
    {
        return array_intersect_key(self::$apiRoutes, array_flip(self::$$category));
    }

    public static function getApiRoute($name)
    {
        return self::$apiRoutes[$name];
    }

    public function isCurrentRouteInFeatureMap()
    {
        $route = $this->getCurrentRouteName();

        return (array_key_exists($route, self::$routeNameToFeatureMap));
    }
}
