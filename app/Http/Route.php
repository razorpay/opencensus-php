<?php

namespace RZP\Http;

use ApiResponse;
use Illuminate\Routing\Router;
use RZP\Foundation\Application;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Feature\Constants as Feature;

use RZP\Models\Admin\Permission\Name as Permission;

final class Route
{
    /*
     | The order in which routes are defined is very important.
     | Whenever the order of routes is changed,
     | make sure to run the full test suite
     */

    protected static $apiRoutes = [
        'account'                                  => ['get',      'account',                                        'PublicController@getAccount'                                       ],
        'checkout'                                 => ['get',      'checkout',                                       'MerchantController@getCheckout'                                    ],
        'checkout_public'                          => ['get',      'checkout/public',                                'MerchantController@getCheckoutPublic'                              ],

        // callback_url case handler for automatic checkout
        'checkout_onyx'                            => ['post',     'checkout/onyx',                                  'PublicController@postCallbackUrlWithParams'                        ],

        // hosted checkout for IRCTC and Bescom
        'checkout_embedded'                        => ['post',     'checkout/embedded',                              'PublicController@renderEmbedded'                                   ],
        'checkout_hosted'                          => ['post',     'checkout/hosted',                                'PublicController@renderCheckoutHosted'                             ],
        'checkout_hosted_get'                      => ['get',      'checkout/hosted',                                'PublicController@renderCheckoutHosted'                             ],
        // TODO: Check Splunk and remove the write here
        'merchant_methods'                         => ['get',      'methods',                                        'MerchantController@getPaymentMethods'                              ],
        'merchant_methods_downtime'                => ['get',      'methods/downtime',                               'MerchantController@getPublicGatewayDowntimeData'                   ],
        'merchant_checkout_preferences'            => ['get',      'preferences',                                    'MerchantController@getCheckoutPreferences'                         ],
        'payment_create'                           => ['post',     'payments',                                       'PaymentCreateController@postCreatePayment'                         ],
        // @todo: Require feature S2S for payment_create_private route.
        'payment_create_private'                   => ['post',     'payments/create',                                'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_create_aeps'                      => ['post',     'payments/create/aeps',                           'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_create_recurring'                 => ['post',     'payments/create/recurring',                      'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_create_private_old'               => ['post',     'payments/create/redirect',                       'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_create_checkout'                  => ['post',     'payments/create/checkout',                       'PaymentCreateController@postCreatePaymentCheckoutCallback'         ],
        'payment_create_checkout_get'              => ['get',      'payments/create/checkout/{payment_id}',          'PaymentCreateController@getCreatePaymentCheckoutCallback'          ],
        'payment_create_jsonp'                     => ['get',      'payments/create/jsonp',                          'PaymentCreateController@getCreatePaymentJsonp'                     ],
        'payment_create_ajax'                      => ['post',     'payments/create/ajax',                           'PaymentCreateController@postAJAX'                                  ],
        'payment_create_fees'                      => ['post',     'payments/create/fees',                           'PaymentCreateController@postCreatePaymentFees'                     ],
        'payment_create_wallet'                    => ['post',     'payments/create/wallet',                         'PaymentCreateController@postCreateWalletPayment'                   ],
        'payment_create_upi'                       => ['post',     'payments/create/upi',                            'PaymentCreateController@postCreateUpiPayment'                      ],
        'payment_create_openwallet'                => ['post',     'payments/create/openwallet',                     'PaymentCreateController@postCreateS2SPayment'                      ],
        'payment_callback_post'                    => ['post',     'payments/{x_entity_id}/callback/{hash}',         'PaymentCreateController@postCallback'                              ],
        'payment_callback_get'                     => ['get',      'payments/{x_entity_id}/callback/{hash}',         'PaymentCreateController@postCallback'                              ],
        'payment_callback_with_key_post'           => ['post',     'payments/{id}/callback/{hash}/{key}',            'PaymentCreateController@postCallback'                              ],
        'payment_callback_with_key_get'            => ['get',      'payments/{id}/callback/{hash}/{key}',            'PaymentCreateController@postCallback'                              ],
        'payment_get_status'                       => ['get',      'payments/{x_entity_id}/status',                  'PaymentController@getPaymentStatusForAsyncPayments'                ],
        'payment_otp_submit'                       => ['post',     'payments/{x_entity_id}/otp_submit/{hash}',       'PaymentCreateController@postOtpSubmit'                             ],
        'payment_otp_resend'                       => ['post',     'payments/{x_entity_id}/otp_resend',              'PaymentCreateController@postOtpResend'                             ],
        'payment_topup_ajax'                       => ['post',     'payments/{x_entity_id}/topup/ajax',              'PaymentCreateController@postTopupAjax'                             ],
        'payment_topup_post'                       => ['post',     'payments/{x_entity_id}/topup',                   'PaymentCreateController@postTopup'                                 ],
        'payment_redirect_callback'                => ['post',     'payments/{x_entity_id}/redirect_callback',       'PaymentCreateController@postRedirectCallback'                      ],
        'payment_refund'                           => ['post',     'payments/{id}/refund',                           'PaymentController@postRefund'                                      ],
        'payment_payout'                           => ['post',     'payments/{id}/payouts',                          'PaymentController@postPayout'                                      ],
        'payment_bank_transfer_fetch'              => ['get',      'payments/{id}/bank_transfer',                    'BankTransferController@fetchBankTransferForPayment'                ],
        'batch_create'                             => ['post',     'batches',                                        'BatchController@createBatch'                                       ],
        'batch_validate_file'                      => ['post',     'batches/validate',                               'BatchController@validateFile'                                      ],
        'batch_fetch_multiple'                     => ['get',      'batches',                                        'BatchController@getBatches'                                        ],
        'batch_fetch_by_id'                        => ['get',      'batches/{id}',                                   'BatchController@getBatchById'                                      ],
        'batch_process_file'                       => ['post',     'batches/process',                                'BatchController@processBatches'                                    ],
        'batch_process_by_id'                      => ['post',     'batches/{id}/process',                           'BatchController@processBatch'                                      ],
        'batch_retry_output_file'                  => ['post',     'batches/{id}/retry_output_file',                 'BatchController@retryBatchOutputFile'                              ],
        'batch_download_file'                      => ['get',      'batches/{id}/download',                          'BatchController@downloadBatch'                                     ],
        'batch_stats'                              => ['get',      'batches/{id}/stats',                             'BatchController@getStats'                                          ],
        'payment_capture'                          => ['post',     'payments/{id}/capture',                          'PaymentController@postCapture'                                     ],
        'payment_bulk_capture'                     => ['post',     'payments/capture/bulk',                          'PaymentController@postBulkCapture'                                 ],
        'payment_fetch_transfers'                  => ['get',      'payments/{id}/transfers',                        'PaymentController@getTransfers'                                    ],
        'payment_transfer'                         => ['post',     'payments/{id}/transfers',                        'PaymentController@postTransfer'                                    ],
        'payment_verify'                           => ['get',      'payments/{id}/verify',                           'PaymentController@getVerify'                                       ],
        'payment_force_authorize'                  => ['post',     'payments/{id}/force_authorize',                  'PaymentController@postForceAuthorize'                              ],
        'payment_cancel'                           => ['get',      'payments/{x_entity_id}/cancel',                  'PaymentController@postCancel'                                      ],
        'payment_authorize_failed'                 => ['post',     'payments/{id}/authorize_failed',                 'PaymentController@postAuthorizeFailedPayment'                      ],
        'payment_fix_authorize_at'                 => ['post',     'payments/fix_authorized_at',                     'PaymentController@postFixAuthorizedAt'                             ],
        'payment_authorize_refund'                 => ['post',     'payments/{id}/authorize_refund',                 'PaymentController@postRefundAuthorized'                            ],
        'payments_multiple_authorize_refund'       => ['post',     'payments/authorize_refund/bulk',                 'PaymentController@postRefundAuthorizedInBulk'                      ],
        'payment_add_metadata'                     => ['post',     'payments/{x_entity_id}/metadata',                'PaymentController@postPaymentMetadata'                             ],
        'payment_fetch_by_id'                      => ['get',      'payments/{id}',                                  'PaymentController@getPayment'                                      ],
        'payment_fetch_multiple'                   => ['get',      'payments',                                       'PaymentController@getPayments'                                     ],
        'payment_fetch_card_details'               => ['get',      'payments/{id}/card',                             'PaymentController@getCardForPayment'                               ],
        'payment_fetch_refunds'                    => ['get',      'payments/{id}/refunds',                          'PaymentController@getRefundsForPayment'                            ],
        'payment_fetch_refund_by_id'               => ['get',      'payments/{paymentId}/refunds/{rfndId}',          'PaymentController@getRefundByRefundAndPaymentId'                   ],
        'payment_fetch_transaction'                => ['get',      'payments/{id}/transaction',                      'PaymentController@getTransactionForPayment'                        ],
        'payment_auth_notify'                      => ['get',      'payments/auth/notify',                           'PaymentController@getAuthNotify',                                  ],
        'payment_timeout'                          => ['post',     'payments/timeout',                               'PaymentController@postTimeout'                                     ],
        'payment_auto_capture'                     => ['post',     'payments/autocapture',                           'PaymentController@postAutoCapture'                                 ],
        'payment_auto_capture_email'               => ['get',      'payments/autocapture/email',                     'PaymentController@getAutoCaptureEmail'                             ],
        'payment_verify_multiple'                  => ['post',     'payments/verify/{filter}',                       'PaymentController@postVerifyPayments'                              ],
        'payment_capture_reminder'                 => ['get',      'payments/all/reminder',                          'PaymentController@sendReminderMailForAuthorizedPayments'           ],
        'payment_refund_authorized'                => ['post',     'payments/refund/authorized',                     'PaymentController@postRefundOldAuthorizedPayments'                 ],
        'payment_capture_verify'                   => ['post',     'payments/{id}/verify/capture',                   'PaymentController@postCaptureVerify'                               ],
        'payment_capture_gateway_manual'           => ['post',     'payments/{id}/gateway/capture',                  'PaymentController@postManualGatewayCapture'                        ],
        'payment_acknowledge'                      => ['post',     'payments/{id}/acknowledge',                      'PaymentController@postAcknowledge'                                 ],
        'payment_authorize_time_out'               => ['post',     'payments/authorize/timeout/{ids}',               'PaymentController@postAuthorizeLockTimeOut'                        ],
        'refund_create'                            => ['post',     'refunds',                                        'RefundController@postRefundCreate'                                 ],
        'refund_edit_status'                       => ['put',      'refunds/{id}/status',                            'RefundController@putRefundStatus'                                  ],
        'refund_fetch_by_id'                       => ['get',      'refunds/{id}',                                   'RefundController@getRefund'                                        ],
        'refund_fetch_multiple'                    => ['get',      'refunds',                                        'RefundController@getRefunds'                                       ],
        'refund_generate_excel'                    => ['post',     'refunds/excel',                                  'RefundController@generateRefunds'                                  ],
        'refund_verify_multiple'                   => ['post',     'refunds/{ids}/verify',                           'RefundController@postRefundVerifyMultiple'                         ],
        'refund_create_missing_txn'                => ['post',     'refunds/transaction',                            'RefundController@postRefundsTransactions'                          ],
        'refund_gateway_refunded_txns'             => ['post',     'refunds/gateway_refunded/transaction',           'RefundController@postGatewayRefundedTransactions'                  ],
        'refund_gateway_manual'                    => ['post',     'refunds/{ids}/gateway',                          'RefundController@postManualGatewayRefund'                          ],
        'refund_retry_failed'                      => ['post',     'refunds/retry/failed',                           'RefundController@postRetryFailedRefunds'                           ],
        'refund_verify_failed'                     => ['post',     'refunds/{id}/retry',                             'RefundController@postRefundRetry'                                  ],
        'refund_verify'                            => ['get',      'refunds/{id}/verify',                            'RefundController@postRefundVerify'                                 ],
        'card_check_recurring'                     => ['get',      'cards/recurring',                                'PaymentController@getCardRecurring'                                ],
        'card_fetch_by_id'                         => ['get',      'cards/{id}',                                     'PaymentController@getCard'                                         ],
        'card_fetch_multiple'                      => ['get',      'cards',                                          'PaymentController@getCards'                                        ],
        'card_update_saved'                        => ['put',      'cards/saved',                                    'CardController@updateSavedCards'                                   ],
        'iin_fetch_by_iin'                         => ['get',      'iins/{id}',                                      'CardController@getIin'                                             ],
        'iin_fetch_multiple'                       => ['get',      'iins',                                           'CardController@getIins'                                            ],
        'iin_add'                                  => ['post',     'iins',                                           'CardController@postIin'                                            ],
        'iin_upload'                               => ['post',     'iins/upload',                                    'CardController@uploadIin'                                          ],
        'iin_range_upload'                         => ['post',     'iins/range/upload',                              'CardController@rangeUploadIin'                                     ],
        'iin_edit'                                 => ['put',      'iins/{id}',                                      'CardController@editIin'                                            ],
        'iin_generate_post'                        => ['post',     'iins/import/generate',                           'CardController@postIinGenerate'                                    ],
        'merchant_public_get_banks'                => ['get',      'banks',                                          'MerchantController@getBanksPublic'                                 ],
        'merchant_secret'                          => ['get',      'keys/{id}/secret',                               'MerchantController@getKeySecret'                                   ],
        'merchant_get_banks'                       => ['get',      'merchants/{id}/banks',                           'MerchantController@getBanks'                                       ],
        'merchant_set_banks'                       => ['post',     'merchants/{id}/banks',                           'MerchantController@setBanks'                                       ],
        'merchant_daily_report'                    => ['post',     'merchants/report',                               'MerchantController@sendDailyReport'                                ],
        'merchant_create'                          => ['post',     'merchants',                                      'MerchantController@postCreateMerchant'                             ],
        'merchant_fetch'                           => ['get',      'merchants/{id}',                                 'MerchantController@getMerchant'                                    ],
        'merchant_edit'                            => ['put',      'merchants/{id}',                                 'MerchantController@putMerchant'                                    ],
        'merchant_edit_config'                     => ['put',      'account/config',                                 'MerchantController@putMerchantConfig'                              ],
        'merchant_edit_config_logo'                => ['post',     'account/config/logo',                            'MerchantController@postMerchantConfigLogo'                         ],
        'merchant_delete_config_logo'              => ['delete',   'account/config/logo',                            'MerchantController@deleteMerchantConfigLogo'                       ],
        'merchant_sub_create'                      => ['post',     'submerchants',                                   'MerchantController@postCreateSubMerchant'                          ],
        'merchant_pre_signup_details'              => ['get',      'pre_signup',                                     'MerchantController@getPreSignupDetails'                            ],
        'merchant_edit_pre_signup_details'         => ['put',      'pre_signup',                                     'MerchantController@putPreSignupDetails'                            ],
        'merchant_fetch_config'                    => ['get',      'account/config',                                 'MerchantController@getAccountConfig'                               ],
        'merchant_edit_email'                      => ['put',      'merchants/{id}/email',                           'MerchantController@putMerchantEmail'                               ],
        'merchant_fetch_multiple'                  => ['get',      'merchants',                                      'MerchantController@getMerchants'                                   ],
        'merchant_fetch_webhooks'                  => ['get',      'merchants/{id}/webhooks',                        'MerchantController@getMerchantWebhooks'                            ],
        'merchant_assign_pricing'                  => ['post',     'merchants/{id}/pricing',                         'MerchantController@postAssignPricingPlan'                          ],
        'merchant_get_pricing'                     => ['get',      'merchants/{id}/pricing',                         'MerchantController@getPricingPlan'                                 ],
        'merchant_add_bank_account'                => ['post',     'merchants/{id}/bank_account',                    'MerchantController@postBankAccount'                                ],
        'merchant_bank_account_change_status'      => ['get',      'merchants/{id}/bank_account_change/status',      'MerchantController@getBankAccountChangeStatus'                     ],
        'merchant_fetch_bank_account'              => ['get',      'merchants/{id}/bank_account',                    'MerchantController@getBankAccount'                                 ],
        'merchant_generate_test_bank_acnt'         => ['post',     'merchants/bank_account/generate/test',           'MerchantController@postGenerateTestBankAccounts'                   ],
        'merchant_create_terminal'                 => ['post',     'merchants/{id}/terminals',                       'MerchantController@postCreateTerminal'                             ],
        'merchant_get_terminals'                   => ['get',      'merchants/{id}/terminals',                       'MerchantController@getTerminals'                                   ],
        'merchant_get_terminal'                    => ['get',      'merchants/{mid}/terminals/{tid}',                'MerchantController@getTerminal'                                    ],
        'merchant_delete_terminal'                 => ['delete',   'merchants/{mid}/terminals/{tid}',                'MerchantController@deleteTerminal'                                 ],
        'merchant_modify_terminal'                 => ['put',      'merchants/{mid}/terminals/{tid}',                'MerchantController@putTerminal'                                    ],
        'merchant_put_payment_methods'             => ['put',      'merchants/{mid}/methods',                        'MerchantController@putMethods'                                     ],
        'merchant_activate'                        => ['post',     'merchants/{id}/activate',                        'MerchantController@postActivate'                                   ],
        'merchant_send_activation_mail'            => ['post',     'merchants/activation_mail',                      'MerchantController@postSendActivationMail'                         ],
        'merchant_live_enable'                     => ['post',     'merchants/{id}/live/enable',                     'MerchantController@postLiveEnable'                                 ],
        'merchant_live_disable'                    => ['post',     'merchants/{id}/live/disable',                    'MerchantController@postLiveDisable'                                ],
        'merchant_actions'                         => ['put',      'merchants/{id}/action',                          'MerchantController@putAction'                                      ],
        'merchant_fetch_referrals'                 => ['get',      'referrals',                                      'MerchantController@getReferredMerchants'                           ],
        'merchant_get_tags'                        => ['get',      'merchants/{id}/tags',                            'MerchantController@getTags'                                        ],
        'merchant_tag_add'                         => ['post',     'merchants/{id}/tags',                            'MerchantController@addTags'                                        ],
        'merchant_tag_delete'                      => ['delete',   'merchants/{id}/tags/{tagName}',                  'MerchantController@deleteTag'                                      ],
        'merchant_update_key_access'               => ['put',      'merchants/{id}/update_key_access',               'MerchantController@updateKeyAccess'                                ],
        'merchant_edit_free_credits'               => ['post',     'merchants/{id}/credits',                         'MerchantController@postAmountCredits',                             ],
        'merchant_fetch_users'                     => ['get',      'merchants-users',                                'MerchantController@getUsers',                                      ],
        'merchant_patch_beneficiary_code'          => ['patch',    'merchants/beneficiary/code',                     'MerchantController@patchMerchantBeneficiaryCode'                   ],
        'merchant_beneficiary_file'                => ['get',      'merchants/beneficiary/file/{channel}',           'MerchantController@getMerchantBeneficiaryFile'                     ],
        'merchant_post_beneficiary_file'           => ['post',     'merchants/beneficiary/file/bank/{channel}',      'MerchantController@postMerchantBeneficiaryFile'                    ],
        'merchant_notify_holiday'                  => ['post',     'merchants/notify/holiday',                       'MerchantController@postMerchantsNotifyHoliday'                     ],
        'merchant_invoice_update_gstin'            => ['put',      'merchants/{id}/invoice/gstin',                   'MerchantInvoiceController@updateGstin'                             ],
        'merchant_create_invoice_entities'         => ['post',     'merchants/invoice/create',                       'MerchantInvoiceController@postCreateInvoiceEntities'               ],
        // TODO: Should be removed once the correction has run for all the merchant
        'merchant_invoice_correction'              => ['post',     'merchants/invoice/correction',                    'MerchantInvoiceController@createCorrectionInvoice'                  ],
        'merchant_details_fetch'                   => ['get',      'merchants/details',                              'MerchantController@getMerchantDetails'                             ],
        'merchant_invoice_add_bulk'                => ['post',     'merchants/invoice/bulk',                         'MerchantInvoiceController@postMultipleEntities'                    ],
        'merchant_create_app_access_mapping'       => ['post',     'merchants/{id}/applications',                    'MerchantController@postMapOAuthApplication'                        ],
        'merchant_delete_app_access_mapping'       => ['delete',   'merchants/{id}/applications/{appId}',            'MerchantController@deleteMapOAuthApplication'                      ],
        'merchant_tags_bulk'                       => ['post',     'merchants/tags/bulk',                            'MerchantController@bulkTagMerchants'                               ],
        'create_submerchant_user'                  => ['post',     'submerchant/user/{id}',                          'MerchantController@postSubMerchantUser'                            ],
        'balance_fetch'                            => ['get',      'balance',                                        'MerchantController@getAccountBalance'                              ],
        'credits_create'                           => ['post',     'merchants/{id}/credits_log',                     'MerchantController@postCreateCreditsLog'                           ],
        'credits_edit'                             => ['put',      'merchants/{mid}/credits/{id}',                   'MerchantController@putCreditsLog'                                  ],
        'credits_delete'                           => ['delete',   'merchants/{mid}/credits/{id}',                   'MerchantController@deleteCreditsLog'                               ],
        'credits_fetch_by_id'                      => ['get',      'credits/{id}',                                   'MerchantController@getCreditsLog'                                  ],
        'credits_fetch_multiple'                   => ['get',      'credits',                                        'MerchantController@getCreditsLogs'                                 ],
        'merchant_get_features'                    => ['get',      'merchants/{id}/features',                        'MerchantController@getMerchantFeatures'                            ],
        'merchant_update_features'                 => ['post',     'merchants/{id}/features',                        'MerchantController@updateMerchantFeatures'                         ],
        'merchants_update_bulk'                    => ['put',      'merchants/bulk',                                 'MerchantController@updateMerchantsBulk'                            ],
        'merchants_update_channel'                 => ['put',      'merchants/channel/bulk',                         'MerchantController@updateChannelForMultipleMerchants'              ],
        'merchants_update_bank_account'            => ['put',      'merchants/bank_account/bulk',                    'MerchantController@updateBankAccountForMultipleMerchants'          ],
        'methods_update_merchants'                 => ['put',      'methods/bulkupdate',                             'MerchantController@updateMethodsForMultipleMerchants'              ],
        'gratis_postpaid_transactions'             => ['post',     'merchants/gratis/postpaid',                      'MerchantController@markGratisTransactionPostpaid'                  ],
        'terminal_delete'                          => ['delete',   'terminals/{id}',                                 'TerminalController@deleteTerminal'                                 ],
        'terminal_edit'                            => ['put',      'terminals/{id}',                                 'TerminalController@putTerminal'                                    ],
        'terminal_restore'                         => ['put',      'terminals/{id}/restore',                         'TerminalController@restoreTerminal',                               ],
        'terminal_toggle'                          => ['put',      'terminals/{id}/toggle',                          'TerminalController@toggleTerminal'                                 ],
        'terminal_add_merchant'                    => ['put',      'terminals/{id}/merchants/{mid}',                 'TerminalController@addMerchant'                                    ],
        'terminal_remove_merchant'                 => ['delete',   'terminals/{id}/merchants/{mid}',                 'TerminalController@removeMerchant'                                 ],
        'terminal_reassign_merchant'               => ['put',      'terminals/{id}/reassign',                        'TerminalController@reassignMerchant'                               ],
        'terminal_check_encrypted_value'           => ['post',     'terminals/{id}/secret',                          'TerminalController@postCheckTerminalEncryptedValue'                ],
        'bank_transfer_process'                    => ['post',     'ecollect/validate',                              'BankTransferController@processBankTransfer'                        ],
        'bank_transfer_process_test'               => ['post',     'ecollect/validate/test',                         'BankTransferController@processBankTransfer'                        ],
        'bank_transfer_notify'                     => ['post',     'ecollect/pay',                                   'BankTransferController@notifyBankTransfer'                         ],
        'bank_transfer_refund_retry'               => ['post',     'bank_transfers/refunds/retry',                   'BankTransferController@retryBankTransferRefund'                    ],
        'bank_transfer_edit_payer_account'         => ['put',      'bank_transfers/{id}/payer_bank_account',         'BankTransferController@editPayerBankAccount'                       ],
        'bank_transfer_strip_payer_accounts'       => ['put',      'bank_transfers/payer_bank_account/strip',        'BankTransferController@stripPayerBankAccounts'                     ],
        'bank_transfer_insert'                     => ['post',     'bank_transfers/{provider}',                      'BankTransferController@insertBankTransfer'                         ],
        'fund_transfer_attempt_bulk_update'        => ['patch',    'fund_transfer_attempts',                         'FundTransferAttemptController@bulkUpdate'                          ],
        'fund_transfer_attempt_recon_report'       => ['get',      'fund_transfer_attempts/recon_report',            'FundTransferAttemptController@sendFTAReconReport'                  ],
        'fund_transfer_attempt_reconcile'          => ['post',     'fund_transfer_attempts/reconcile/{channel}',     'FundTransferAttemptController@reconcileFundTransfers',             ],
        'fund_transfer_attempt_process'            => ['post',     'fund_transfer_attempts/initiate/{channel}',      'FundTransferAttemptController@initiateFundTransfers',              ],
        'gateway_payment_callback_bharatqr'        => ['post',     'payment/callback/bharatqr/{gateway}',            'BharatQrController@processBharatQrPayment'                         ],
        'qr_code_download_live'                    => ['get',      'l/qrcode/{id}',                                  'QrCodeController@fetchLiveQrCode'                                  ],
        'qr_code_download_test'                    => ['get',      't/qrcode/{id}',                                  'QrCodeController@fetchTestQrCode'                                  ],
        'virtual_account_create'                   => ['post',     'virtual_accounts',                               'VirtualAccountController@create'                                   ],
        'virtual_account_order_create'             => ['post',     'orders/{id}/virtual_accounts',                   'VirtualAccountController@createForOrder'                           ],
        'virtual_account_edit'                     => ['patch',    'virtual_accounts/{id}',                          'VirtualAccountController@update'                                   ],
        'virtual_account_fetch'                    => ['get',      'virtual_accounts/{id}',                          'VirtualAccountController@get'                                      ],
        'virtual_account_fetch_multiple'           => ['get',      'virtual_accounts',                               'VirtualAccountController@list'                                     ],
        'virtual_account_fetch_payments'           => ['get',      'virtual_accounts/{id}/payments',                 'VirtualAccountController@getPayments'                              ],
        'virtual_account_refund_excess'            => ['post',     'virtual_accounts/refund/excess',                 'VirtualAccountController@refundExcessPayments'                     ],
        'webhook_create'                           => ['post',     'webhooks',                                       'MerchantController@postWebhook'                                    ],
        'webhook_edit'                             => ['put',      'webhooks/{id}',                                  'MerchantController@putWebhook'                                     ],
        'webhook_fetch'                            => ['get',      'webhooks/{id}',                                  'MerchantController@getWebhook'                                     ],
        'webhook_fetch_events'                     => ['get',      'webhooks/events/all',                            'MerchantController@getWebhookEvents'                               ],
        'webhook_fetch_multiple'                   => ['get',      'webhooks',                                       'MerchantController@getWebhooks'                                    ],
        'oauth_app_webhook_create'                 => ['post',     'oauth/applications/{id}/webhooks',               'MerchantController@postOAuthApplicationWebhook'                    ],
        'merchant_create_key'                      => ['post',     'keys',                                           'KeyController@postCreateKeys'                                      ],
        'merchant_fetch_keys'                      => ['get',      'keys',                                           'KeyController@getKeys'                                             ],
        'merchant_replace_key'                     => ['put',      'keys/{id}',                                      'KeyController@putKeys'                                             ],
        'merchant_gst_fetch'                       => ['get',      'merchant/gst',                                   'MerchantController@getGSTDetails'                                  ],
        'merchant_gst_edit'                        => ['patch',    'merchant/gst',                                   'MerchantController@editGSTDetails'                                 ],
        'merchant_activation_details'              => ['get',      'merchant/activation',                            'MerchantController@getActivationDetails'                           ],
        'merchant_activation_save'                 => ['post',     'merchant/activation',                            'MerchantController@postSaveActivationDetails'                      ],
        'merchant_activation_upload_file'          => ['post',     'merchant/activation/upload',                     'MerchantController@postUploadActivationFile'                       ],
        'merchant_activation_reviewers'            => ['get',      'merchant/activation/reviewers',                  'MerchantController@getMerchantActivationReviewers'                 ],
        'merchant_activation_bulk_assign_reviewer' => ['post',     'merchant/activation/bulk_assign_reviewer',       'MerchantController@bulkAssignReviewer'                             ],
        'merchant_activation_update_website'       => ['put',      'merchant/activation/update_website_details',     'MerchantController@updateWebsiteDetails'                           ],
        'merchant_activation_business_categories'  => ['get',      'merchant/activation/business_categories',        'MerchantController@getBusinessCategories'                          ],
        'merchant_activation_files'                => ['get',      'merchant/activation/{id}/files',                 'MerchantController@getActivationFiles'                             ],
        'merchant_activation_upload_file_admin'    => ['post',     'merchant/activation/{id}/files',                 'MerchantController@postUploadActivationFileAdmin'                  ],
        'merchant_activation_update'               => ['put',      'merchant/activation/{id}/update',                'MerchantController@putEditMerchantDetailsAfterLock'                ],
        'merchant_activation_migrate'              => ['post',     'merchant/activation/migrate',                    'MerchantController@postMerchantDetailMigrate'                      ],
        'merchant_activation_archive'              => ['patch',    'merchant/activation/{id}/archive',               'MerchantController@updateActivationArchive'                        ],
        'merchant_activation_status'               => ['patch',    'merchant/activation/{id}/activation_status',     'MerchantController@updateActivationStatus'                         ],
        'merchant_activation_status_change_log'    => ['get',      'merchant/activation/{id}/status_change_log',     'MerchantController@getActivationStatusChangeLog'                   ],
        'merchant_get_rejection_reasons'           => ['get',      'merchant/activation/rejection_reasons',          'MerchantController@getRejectionReasons'                            ],
        'merchant_batches'                         => ['post',     'merchant/{id}/batches',                          'MerchantController@createBatches'                                  ],
        'merchant_payout_mail'                     => ['post',     'merchant/payout/mail',                           'MerchantController@sendPayoutMail'                                 ],
        'pricing_create_plan'                      => ['post',     'pricing',                                        'PricingController@postCreatePricingPlan'                           ],
        'pricing_get_plans'                        => ['get',      'pricing',                                        'PricingController@getPricingPlans'                                 ],
        'pricing_get_merchant_plans'               => ['get',      'pricing/merchants',                              'PricingController@getMerchantPricingPlans'                         ],
        'pricing_get_gateway_plans'                => ['get',      'pricing/gateways',                               'PricingController@getGatewayPricingPlans'                          ],
        'pricing_supported_networks'               => ['get',      'pricing/networks',                               'PricingController@getSupportedNetworks'                            ],
        'pricing_get_plan'                         => ['get',      'pricing/{id}',                                   'PricingController@getPricingPlan'                                  ],
        'pricing_add_plan_rule'                    => ['post',     'pricing/{id}/rule',                              'PricingController@postAddPricingPlanRule'                          ],
        'pricing_delete_plan_rule'                 => ['delete',   'pricing/{planId}/rule/{ruleId}',                 'PricingController@deletePricingPlanRule'                           ],
        'pricing_delete_plan_rule_force'           => ['delete',   'pricing/{planId}/rule/{ruleId}/force',           'PricingController@deletePricingPlanRuleForce'                      ],
        'schedule_create'                          => ['post',     'schedules',                                      'ScheduleController@postSchedule'                                   ],
        'schedule_fetch'                           => ['get',      'schedules/{id}',                                 'ScheduleController@getSchedule'                                    ],
        'schedule_fetch_multiple'                  => ['get',      'schedules',                                      'ScheduleController@getSchedules'                                   ],
        'schedule_delete'                          => ['delete',   'schedules/{id}',                                 'ScheduleController@deleteSchedule'                                 ],
        'schedule_update'                          => ['put',      'schedules/{id}',                                 'ScheduleController@putSchedule'                                    ],
        'schedule_update_next_run'                 => ['post',     'schedules/update_next_run',                      'ScheduleController@updateNextRun'                                  ],
        'schedule_migration'                       => ['post',     'merchants/schedules/migrate',                    'MerchantController@migrateToSchedules'                             ],
        'schedule_assign'                          => ['post',     'merchants/{id}/schedules',                       'MerchantController@assignSettlementSchedule'                       ],
        'schedule_process_tasks'                   => ['post',     'schedules/process_tasks',                        'ScheduleController@processTasks'                                   ],
        'transaction_fetch_by_id'                  => ['get',      'transactions/{id}',                              'TransactionController@getTransaction'                              ],
        'transaction_fetch_multiple'               => ['get',      'transactions',                                   'TransactionController@getTransactions'                             ],
        'transaction_monthly_report'               => ['get',      'transactions/report',                            'TransactionController@getMonthlyReport'                            ],
        'transaction_create_fees_breakup'          => ['post',     'transactions/fees_breakup',                      'TransactionController@postCreateFeeBreakup'                        ],
        'transaction_bulk_update'                  => ['put',      'transactions/bulk',                              'TransactionController@updateMultipleTransactions'                  ],
        'setl_fetch_schedule'                      => ['get',      'settlements/schedules',                          'ScheduleController@getSettlementSchedules'                         ],
        'setl_fetch_by_id'                         => ['get',      'settlements/{id}',                               'SettlementController@getSettlement'                                ],
        'setl_fetch_multiple'                      => ['get',      'settlements',                                    'SettlementController@getSettlements'                               ],
        'setl_fetch_transactions'                  => ['get',      'settlements/{id}/transactions',                  'SettlementController@getSettlementTransactions'                    ],
        'setl_edit'                                => ['put',      'settlements/{id}',                               'SettlementController@putEditSettlement'                            ],
        'setl_fixer'                               => ['get',      'settlements/fixer',                              'SettlementController@getSettlementFixer'                           ],
        'setl_delete_file'                         => ['delete',   'settlements/file/{setlFileType}',                'SettlementController@deleteSettlementFile'                         ],
        'setl_initiate'                            => ['post',     'settlements/initiate/{channel?}',                'SettlementController@postSettlementInitiate'                       ],
        'setl_initiate_daily'                      => ['post',     'settlements/initiate_daily',                     'SettlementController@processDailySettlements'                      ],
        'setl_retry'                               => ['post',     'settlements/retry',                              'SettlementController@postSettlementRetry'                          ],
        'setl_file_generate'                       => ['post',     'settlements/file/generate',                      'SettlementController@postSettlementFileGenerate'                   ],
        'setl_reconcile_generate'                  => ['post',     'settlements/reconcile/generate/{channel}',       'SettlementController@postSettlementReconcileGenerate'              ],
        'setl_reconcile_test'                      => ['post',     'settlements/reconcile/test/all',                 'SettlementController@postReconcileInTestMode'                      ],
        'setl_reconcile'                           => ['post',     'settlements/reconcile/{channel}',                'SettlementController@postSettlementReconcile'                      ],
        'setl_reconcile_h2h'                       => ['post',     'settlements/h2hreconcile/{channel}',             'SettlementController@postH2HSettlementReconcile'                   ],
        'setl_calc_previous_fees'                  => ['post',     'settlements/fees/previous',                      'SettlementController@postSettlementCalculateFees',                 ],
        'setl_get_details'                         => ['get',      'settlements/{id}/details',                       'SettlementController@getSettlementDetails',                        ],
        'setl_post_details_old'                    => ['post',     'settlements/details',                            'SettlementController@postSettlementDetailsForOldTxns'              ],
        'setl_combined_report'                     => ['get',      'settlements/report/combined',                    'SettlementController@getSettlementCombinedReport'                  ],
        'setl_update_channel_bulk'                 => ['put',      'settlements/channel/bulk',                       'SettlementController@updateChannelForMultipleSettlements'          ],
        'nodal_get_account_balance'                => ['get',      'nodal/balance/{channel}',                        'SettlementController@getAccountBalance'                            ],
        'nodal_initiate_transfer'                  => ['post',     'nodal/transfer',                                 'SettlementController@postInitiateTransfer'                         ],
        'nodal_add_beneficiary'                    => ['post',     'nodal/beneficiary/{channel}',                    'SettlementController@addBeneficiary'                               ],
        'adj_fetch_by_id'                          => ['get',      'adjustments/{id}',                               'AdjustmentController@getAdjustment'                                ],
        'adj_fetch_multiple'                       => ['get',      'adjustments',                                    'AdjustmentController@getAdjustments'                               ],
        'adj_add'                                  => ['post',     'adjustments',                                    'AdjustmentController@postAdjustment'                               ],
        'adj_add_reverse'                          => ['post',     'adjustments/reversal',                           'AdjustmentController@postReverseAdjustments'                       ],
        'adj_add_bulk'                             => ['post',     'adjustments/bulk',                               'AdjustmentController@postMultipleAdjustments'                      ],
        'adjustments_split_for_dispute'            => ['post',     'adjustments/split_adjustments',                  'AdjustmentController@splitAdjustments'                             ],
        'mock_hdfc_enroll'                         => ['post',     'gateway/mock_hdfc/enroll',                       'MockGatewayController@enroll'                                      ],
        'mock_hdfc_payment'                        => ['post',     'gateway/mock_hdfc/payment',                      'MockGatewayController@payment'                                     ],
        'mock_hdfc_auth_enrolled'                  => ['post',     'gateway/mock_hdfc/auth_enrolled',                'MockGatewayController@authEnrolled'                                ],
        'mock_hdfc_3dsecure'                       => ['post',     'gateway/3dsecure',                               'MockGatewayController@post3dSecure'                                ],
        'mock_acs'                                 => ['post',     'gateway/acs/{gateway}',                          'MockGatewayController@postAcs'                                     ],
        'mock_atom_payment'                        => ['post',     'gateway/mockanb/payment',                        'MockGatewayController@postAtomPayment'                             ],
        'mock_axis_migs_payment'                   => ['post',     'gateway/mockaxismigs/payment',                   'MockGatewayController@postAxisPayment'                             ],
        'mock_first_data_payment'                  => ['post',     'gateway/mockfirstdata/payment',                  'MockGatewayController@postFirstDataPayment'                        ],
        'mock_axis_genius_payment'                 => ['post',     'gateway/mockaxisgenius/payment',                 'MockGatewayController@postAxisGeniusPayment'                       ],
        'mock_paytm_payment'                       => ['post',     'gateway/mockpaytm/payment',                      'MockGatewayController@postPaytmPayment'                            ],
        'mock_mobikwik_payment'                    => ['post',     'gateway/mockmobikwik/payment',                   'MockGatewayController@postMobikwikPayment'                         ],
        'mock_billdesk_payment'                    => ['post',     'gateway/mockbilldesk/payment',                   'MockGatewayController@postBilldeskPayment'                         ],
        'mock_ebs_payment'                         => ['post',     'gateway/mockebs/payment',                        'MockGatewayController@postEbsPayment'                              ],
        'mock_esigner_payment'                     => ['get',      'gateway/mock/esigner/{signer}',                  'MockGatewayController@postEsignerPayment'                          ],
        'mock_sharp_payment_post'                  => ['post',     'gateway/mocksharp/payment',                      'MockGatewayController@getSharpPayment'                             ],
        'mock_sharp_payment_get'                   => ['get',      'gateway/mocksharp/payment',                      'MockGatewayController@getSharpPayment'                             ],
        'mock_amex_payment'                        => ['post',     'gateway/mockamex/payment',                       'MockGatewayController@postAmexPayment'                             ],
        'mock_card_fss_payment'                    => ['get',      'gateway/mockfss/payment',                        'MockGatewayController@getFssPayment'                               ],
        'mock_sharp_payment_submit'                => ['post',     'gateway/mocksharp/payment/submit',               'MockGatewayController@postSharpPayment'                            ],
        'mock_netbanking_payment'                  => ['post',     'gateway/mock/netbanking/{bank}',                 'MockGatewayController@postNetbankingPayment'                       ],
        'mock_netbanking_payment_get'              => ['get',      'gateway/mock/netbanking/{bank}',                 'MockGatewayController@postNetbankingPayment'                       ],
        'mock_wallet_payment'                      => ['post',     'gateway/mock/wallet/{wallet}',                   'MockGatewayController@walletPayment'                               ],
        'mock_wallet_payment_get'                  => ['get',      'gateway/mock/wallet/{wallet}',                   'MockGatewayController@walletPayment'                               ],
        'mock_wallet_payment_with_paymentid'       => ['post',     'gateway/mock/wallet/{wallet}/{paymentId}',       'MockGatewayController@walletPayment'                               ],
        'mock_generate_reconciliation'             => ['post',     'gateway/mock/reconciliation/{gateway}',          'MockGatewayController@generateGatewayReconciliationFile'           ],
        'mock_upi_payment'                         => ['post',     'gateway/mock/upi/{bank}',                        'MockGatewayController@postUpiPayment'                              ],
        'mock_aeps_payment'                        => ['post',     'gateway/mock/aeps/{bank}',                       'MockGatewayController@postAepsPayment'                             ],
        'admin_fetch_all_entities'                 => ['get',      'admin/entities/all',                             'AdminController@getEntities'                                       ],
        'admin_fetch_entity_multiple'              => ['get',      'admin/{type}',                                   'AdminController@getEntityMultiple'                                 ],
        'admin_fetch_terminal_by_id'               => ['get',      'admin/terminal/{id}',                            'AdminController@getTerminalById'                                   ],
        'admin_fetch_entity_by_id'                 => ['get',      'admin/{type}/{id}',                              'AdminController@getEntityById'                                     ],
        'entity_tax_update'                        => ['put',      'admin/{entity}/tax_update',                      'AdminController@updateEntityTax'                                   ],
        'admin_get_file'                           => ['get',      'files/{fileId}/signed-url',                      'FileStoreController@getFile'                                       ],
        'send_test_newsletter'                     => ['post',     'admin/newsletter/test',                          'AdminController@postSendTestNewsletter'                            ],
        'send_newsletter'                          => ['post',     'admin/newsletter/mail',                          'AdminController@postSendNewsletter'                                ],
        'gateway_payment_callback_axis'            => ['post',     'callback/axis',                                  'GatewayController@callbackAxis'                                    ],
        'gateway_payment_callback_get'             => ['get',      'callback/{gateway}',                             'GatewayController@callbackGateway'                                 ],
        'gateway_payment_callback_post'            => ['post',     'callback/{gateway}',                             'GatewayController@callbackGateway'                                 ],
        'gateway_payment_callback_kotak'           => ['get',      'gateway/netbanking_kotak/callback',              'GatewayController@callbackKotak'                                   ],
        'gateway_payment_callback_kotak_cancel'    => ['post',     'gateway/netbanking_kotak/callback',              'GatewayController@callbackKotakCancel'                             ],
        'gateway_payment_callback_corporation'     => ['post',     'gateway/netbanking_corporation/callback',        'GatewayController@callbackCorporation'                             ],

        'geoip_update'                             => ['post',     'geoip/update',                                   'AdminController@updateGeoIps'                                      ],

        // File-based Emandate Routes
        'emandate_debit_reconcile'                 => ['post',     'emandate/debit/reconcile/{gateway}',             'EMandateController@postReconcileDebitFile'                         ],

        'reconciliate'                             => ['post',     'reconciliate',                                   'ReconciliatorController@postReconciliation'                        ],
        'dummy_return_callback'                    => ['post',     'return/callback',                                'PaymentController@postDummyReturnCallback'                         ],
        'dummy_critical_error'                     => ['get',      'trigger/error',                                  'AdminController@getTriggerError'                                   ],
        'set_config_keys'                          => ['put',      'config/keys',                                    'AdminController@setConfigKeys'                                     ],
        'get_config_keys'                          => ['get',      'config/keys',                                    'AdminController@getConfigKeys'                                     ],
        'get_cache_counts'                         => ['get',      'cache/counts',                                   'AdminController@getQueryCacheCounts'                               ],
        'dummy_route'                              => ['post',     'dummy/route',                                    'PaymentController@postDummyRoute'                                  ],
        'transparent_redirect_get'                 => ['get',      'redirect',                                       'AdminController@getTransparentRedirect'                            ],
        'transparent_redirect_post'                => ['post',     'redirect',                                       'AdminController@postTransparentRedirect'                           ],
        'feature_dummy'                            => ['get',      'dummy',                                          'MerchantController@getDummyFeatures'                               ],
        'razorx_dummy'                             => ['get',      'dummy/razorx',                                   'MerchantController@getDummyRazorX'                                 ],
        'emi_plan_add'                             => ['post',     'emi',                                            'EmiController@addEmiPlan'                                          ],
        'emi_plans_fetch_multiple'                 => ['get',      'emi',                                            'EmiController@fetchEmiPlans'                                       ],
        'emi_plan_fetch_by_id'                     => ['get',      'emi/{id}',                                       'EmiController@fetchEmiPlanById'                                    ],
        'emi_plan_delete'                          => ['delete',   'emi/{id}',                                       'EmiController@deleteEmiPlan'                                       ],
        'emi_generate_excel'                       => ['post',     'emi/generate/excel',                             'EmiController@generateEmiExcel'                                    ],
        'enable_emi_merchant_sub'                  => ['post',     'merchant/{id}/emi_plan/{emiPlanId}',             'MerchantController@enableEmiMerchantSubvention'                    ],
        'order_create'                             => ['post',     'orders',                                         'OrderController@createOrder'                                       ],
        'order_fetch'                              => ['get',      'orders',                                         'OrderController@getOrders'                                         ],
        'order_fetch_by_id'                        => ['get',      'orders/{id}',                                    'OrderController@fetchOrderById'                                    ],
        'order_payments'                           => ['get',      'orders/{id}/payments',                           'OrderController@fetchPayments'                                     ],
        'order_refund_multiple_authorized'         => ['post',     'orders/payments/refund',                         'PaymentController@postRefundAuthorizedPaymentsOfPaidOrders'        ],
        'reports_transaction_broking'              => ['get',      'reports/transaction/broking',                    'MerchantController@getBrokerTransactionReport'                     ],
        'reports_transaction_dsp'                  => ['get',      'reports/transaction/dsp',                        'MerchantController@getDSPTransactionReport'                        ],
        'reports_order_rpp'                        => ['get',      'reports/order/rpp',                              'MerchantController@getRPPOrderReport'                              ],
        'reports_monthly_invoice'                  => ['get',      'reports/invoice',                                'MerchantController@getInvoiceReport'                               ],
        'reports_public_entity'                    => ['get',      'reports/{entity}',                               'MerchantController@getPublicEntityReport'                          ],
        'reports_public_entity_file'               => ['get',      'reports/{entity}/file',                          'MerchantController@getPublicEntityReportUrl'                       ],
        'reports_refund_irctc'                     => ['get',      'reports/refund/irctc',                           'MerchantController@getIrctcRefundReport'                           ],
        'customer_create'                          => ['post',     'customers',                                      'CustomerController@createLocalCustomer'                            ],
        'customer_update'                          => ['put',      'customers/{id}',                                 'CustomerController@updateCustomer'                                 ],
        'customer_fetch_by_id'                     => ['get',      'customers/{id}',                                 'CustomerController@getCustomer'                                    ],
        'customer_fetch_multiple'                  => ['get',      'customers',                                      'CustomerController@getCustomers'                                   ],
        'customer_delete'                          => ['delete',   'customers/{id}',                                 'CustomerController@deleteCustomer'                                 ],
        'customer_add_bank_account'                => ['post',     'customers/{id}/bank_account',                    'CustomerController@postBankAccount'                                ],
        'customer_fetch_bank_account'              => ['get',      'customers/{id}/bank_account',                    'CustomerController@getBankAccounts'                                ],
        'customer_create_token'                    => ['post',     'customers/{id}/tokens',                          'CustomerController@addToken'                                       ],
        'customer_create_token_public'             => ['post',     'customers/{x_entity_id}/tokens/public',          'CustomerController@addToken'                                       ],
        'customer_update_token'                    => ['put',      'customers/{id}/tokens/{token}',                  'CustomerController@updateToken'                                    ],
        'customer_fetch_token'                     => ['get',      'customers/{id}/tokens/{token}',                  'CustomerController@fetchToken'                                     ],
        'customer_fetch_tokens'                    => ['get',      'customers/{id}/tokens',                          'CustomerController@fetchTokens'                                    ],
        'customer_delete_token'                    => ['delete',   'customers/{id}/tokens/{token}',                  'CustomerController@deleteToken'                                    ],
        'customer_get_saved_status'                => ['get',      'customers/status/{contact}',                     'CustomerController@fetchGlobalCustomerStatus'                      ],
        'customer_logout_global'                   => ['delete',   'apps/logout',                                    'CustomerController@logoutCustomer'                                 ],
        'customer_create_address'                  => ['post',     'customers/{id}/addresses',                       'CustomerController@postCreateAddress'                              ],
        'customer_delete_address'                  => ['delete',   'customers/{id}/addresses/{address_id}',          'CustomerController@deleteAddress'                                  ],
        'customer_fetch_addresses'                 => ['get',      'customers/{id}/addresses',                       'CustomerController@getAddresses'                                   ],
        'customer_set_primary_address'             => ['put',      'customers/{id}/addresses/{address_id}/primary',  'CustomerController@putPrimaryAddress'                              ],
        'customer_get_wallet_balance'              => ['get',      'customers/{id}/balance',                         'CustomerController@getCustomerWalletBalance'                       ],
        'customer_get_wallet_statement'            => ['get',      'customers/{id}/statement',                       'CustomerController@getCustomerWalletStatement'                     ],
        'invoice_create'                           => ['post',     'invoices',                                       'InvoiceController@createInvoice'                                   ],
        'invoice_fetch'                            => ['get',      'invoices/{id}',                                  'InvoiceController@getInvoice'                                      ],
        'invoice_fetch_multiple'                   => ['get',      'invoices',                                       'InvoiceController@getInvoices'                                     ],
        'invoice_update'                           => ['patch',    'invoices/{id}',                                  'InvoiceController@updateInvoice'                                   ],
        'invoice_issue'                            => ['post',     'invoices/{id}/issue',                            'InvoiceController@issueInvoice'                                    ],
        'invoice_delete'                           => ['delete',   'invoices/{id}',                                  'InvoiceController@deleteInvoice'                                   ],
        'invoice_add_line_items'                   => ['post',     'invoices/{id}/line_items',                       'InvoiceController@addLineItems'                                    ],
        'invoice_update_line_item'                 => ['patch',    'invoices/{id}/line_items/{lineItemId}',          'InvoiceController@updateLineItem'                                  ],
        'invoice_remove_line_item_bulk'            => ['delete',   'invoices/{id}/line_items/bulk',                  'InvoiceController@removeManyLineItems'                             ],
        'invoice_remove_line_item'                 => ['delete',   'invoices/{id}/line_items/{lineItemId}',          'InvoiceController@removeLineItem'                                  ],
        'invoice_send_notifications'               => ['post',     'invoices/notify',                                'InvoiceController@sendNotifications'                               ],
        'invoice_send_notification'                => ['post',     'invoices/{x_entity_id}/notify/{medium}',         'InvoiceController@sendNotification'                                ],
        'invoice_send_notification_private'        => ['post',     'invoices/{id}/notify_by/{medium}',               'InvoiceController@sendNotification'                                ],
        'invoice_notification_update'              => ['put',      'invoices/{medium}',                              'InvoiceController@updateInvoiceNotificationStatus'                 ],
        'invoice_get_status'                       => ['get',      'invoices/{x_entity_id}/status',                  'InvoiceController@getInvoiceStatus'                                ],
        'invoice_view_live'                        => ['get',      'l/{id}',                                         'InvoiceController@getInvoiceView'                                  ],
        'invoice_view_test'                        => ['get',      't/{id}',                                         'InvoiceController@getInvoiceView'                                  ],
        'invoice_cancel'                           => ['post',     'invoices/{id}/cancel',                           'InvoiceController@cancelInvoice'                                   ],
        'invoice_expire_bulk'                      => ['post',     'invoices/expire',                                'InvoiceController@expireInvoices'                                  ],
        'invoice_issue_by_batch'                   => ['post',     'invoices/batch/{batchId}/issue',                 'InvoiceController@issueInvoicesOfBatch'                            ],
        'invoice_notify_by_batch'                  => ['put',      'invoices/batch/{batchId}/notify',                'InvoiceController@notifyInvoicesOfBatch'                           ],
        'invoice_get_stats_by_batch_ids'           => ['get',      'invoices/batches/issuable',                      'InvoiceController@getIssuableByBatchIds'                           ],
        'invoice_view_live_post'                   => ['post',     'l/{id}',                                         'InvoiceController@getInvoiceView'                                  ],
        'invoice_view_test_post'                   => ['post',     't/{id}',                                         'InvoiceController@getInvoiceView'                                  ],
        'invoice_get_pdf'                          => ['get',      'invoices/{x_entity_id}/pdf',                     'InvoiceController@getInvoicePdf'                                   ],
        'item_create'                              => ['post',     'items',                                          'ItemController@createItem'                                         ],
        'item_fetch'                               => ['get',      'items/{id}',                                     'ItemController@getItem'                                            ],
        'item_fetch_multiple'                      => ['get',      'items',                                          'ItemController@getItems'                                           ],
        'item_update'                              => ['patch',    'items/{id}',                                     'ItemController@updateItem'                                         ],
        'item_delete'                              => ['delete',   'items/{id}',                                     'ItemController@deleteItem'                                         ],
        'app_delete_token'                         => ['delete',   'apps/tokens/{token}',                            'CustomerController@deleteTokenForGlobalCustomer'                   ],
        'app_fetch_tokens'                         => ['get',      'apps/tokens',                                    'CustomerController@fetchTokensForGlobalCustomer'                   ],
        'app_fetch_payments'                       => ['get',      'apps/payments',                                  'CustomerController@fetchPaymentsForGlobalCustomer'                 ],
        'bank_account_fetch'                       => ['get',      'account/bank_account',                           'MerchantController@getOwnBankAccount'                              ],
        'device_verify_token'                      => ['post',     'devices/{deviceToken}/verify',                   'CustomerController@validateDeviceToken'                            ],
        'otp_post'                                 => ['post',     'otp/create',                                     'CustomerController@postOtp'                                        ],
        'otp_verify'                               => ['post',     'otp/verify',                                     'CustomerController@verifyOtp'                                      ],
        'otp_verify_app'                           => ['post',     'otp/verify/app',                                 'CustomerController@verifyOtpApp'                                   ],
        'sms_callback'                             => ['post',     'sms/{gateway}/callback',                         'CustomerController@updateSmsStatus'                                ],
        'es_debug_get'                             => ['post',     'es/debug/{method}',                              'EsController@debug'                                                ],
        'es_aliases_post'                          => ['post',     'es/aliases',                                     'EsController@postAliases'                                          ],
        'gateway_add_priorities'                   => ['post',     'gateway/priorities/{method}',                    'GatewayController@createGatewayPriority'                           ],
        'gateway_fetch_priorities'                 => ['get',      'gateway/priorities',                             'GatewayController@getGatewayPriority'                              ],
        'gateway_update_priorities'                => ['patch',    'gateway/priorities/{method}/add',                'GatewayController@addOrUpdateGatewayPriority'                      ],
        'gateway_remove_priorities'                => ['patch',    'gateway/priorities/{method}/remove',             'GatewayController@removeGatewayPriority'                           ],
        'gateway_create_downtime'                  => ['post',     'gateway/downtimes',                              'GatewayController@postGatewayDowntime'                             ],
        'gateway_update_downtime'                  => ['put',      'gateway/downtimes/{id}',                         'GatewayController@putGatewayDowntime'                              ],
        'gateway_downtime_source_webhook'          => ['post',     'gateway/downtimes/{source}/webhook',             'GatewayController@postGatewayDowntimeWebhook'                      ],
        'gateway_create_rule'                      => ['post',     'gateway/rules',                                  'GatewayController@createGatewayRule'                               ],
        'gateway_update_rule'                      => ['patch',    'gateway/rules/{id}',                             'GatewayController@updateGatewayRule'                               ],
        'gateway_delete_rule'                      => ['delete',   'gateway/rules/{id}',                             'GatewayController@deleteGatewayRule'                               ],
        'gateway_file_create'                      => ['post',     'gateway/files',                                  'GatewayFileController@createGatewayFile'                           ],
        'gateway_file_retry'                       => ['post',     'gateway/files/{id}/retry',                       'GatewayFileController@retryGatewayFile'                            ],
        'gateway_file_acknowledge'                 => ['post',     'gateway/files/{id}/acknowledge',                 'GatewayFileController@acknowledgeGatewayFile'                      ],
        'scorecard'                                => ['get',      'scorecard',                                      'AdminController@getScorecard'                                      ],
        'billdesk_reconcile_cancelled'             => ['post',     'reconciliate/{gateway}/cancelled',               'ReconciliatorController@postReconciliateCancelledTransactions'     ],
        'plan_create'                              => ['post',     'plans',                                          'SubscriptionController@postCreatePlan'                             ],
        'plan_fetch'                               => ['get',      'plans/{id}',                                     'SubscriptionController@getPlan'                                    ],
        'plan_fetch_multiple'                      => ['get',      'plans',                                          'SubscriptionController@getPlans'                                   ],
        'subscription_create'                      => ['post',     'subscriptions',                                  'SubscriptionController@postCreateSubscription'                     ],
        'subscription_fetch'                       => ['get',      'subscriptions/{id}',                             'SubscriptionController@getSubscription'                            ],
        'subscription_fetch_multiple'              => ['get',      'subscriptions',                                  'SubscriptionController@getSubscriptions'                           ],
        'subscriptions_charge_invoices'            => ['post',     'subscriptions/charge/invoices',                  'SubscriptionController@postCreateAndChargeSubscriptionInvoices'    ],
        'subscription_test_charge'                 => ['post',     'subscriptions/{id}/charge',                      'SubscriptionController@postTestChargeSubscription'                 ],
        'subscriptions_retry'                      => ['post',     'subscriptions/retry',                            'SubscriptionController@postRetrySubscriptions'                     ],
        'subscriptions_expire'                     => ['post',     'subscriptions/expire',                           'SubscriptionController@postExpireSubscriptions'                    ],
        'subscription_manual_retry'                => ['post',     'invoices/{invoice_id}/charge',                   'SubscriptionController@postChargeSubscriptionInvoiceManually'      ],
        'subscription_cancel'                      => ['post',     'subscriptions/{subscription_id}/cancel',         'SubscriptionController@postCancelSubscription'                     ],
        'subscription_cancel_due'                  => ['post',     'subscriptions/cancel/due',                       'SubscriptionController@postCancelDueSubscriptions'                 ],
        'subscription_create_addon'                => ['post',     'subscriptions/{subscriptionId}/addons',          'SubscriptionController@postAddonForSubscription'                   ],
        'subscription_fetch_due_addons'            => ['get',      'subscriptions/{subscriptionId}/addons/due',      'SubscriptionController@getDueAddonsForSubscription'                ],
        'subscription_view_live'                   => ['get',      'l/subscriptions/{id}',                           'SubscriptionController@getSubscriptionView'                        ],
        'subscription_view_test'                   => ['get',      't/subscriptions/{id}',                           'SubscriptionController@getSubscriptionView'                        ],
        'subscription_view_live_post'              => ['post',     'l/subscriptions/{id}',                           'SubscriptionController@getSubscriptionView'                        ],
        'subscription_view_test_post'              => ['post',     't/subscriptions/{id}',                           'SubscriptionController@getSubscriptionView'                        ],
        'addon_fetch'                              => ['get',      'addons/{addonId}',                               'SubscriptionController@getAddon'                                   ],
        'addon_fetch_multiple'                     => ['get',      'addons',                                         'SubscriptionController@getAddons'                                  ],
        'addon_delete'                             => ['delete',   'addons/{addonId}',                               'SubscriptionController@deleteAddon'                                ],
        'billdesk_create_cancelled_refunds'        => ['post',     'refunds/billdesk/cancelled',                     'RefundController@postCreateBilldeskCancelledRefunds'               ],
        'upi_fill_bank'                            => ['patch',    'gateway/upi_fill_bank',                          'GatewayController@fillUpiBank'                                     ],
        'mailgun_webhook'                          => ['post',     'mailgun/callback/{type}',                        'AdminController@postMailgunCallback'                               ],
        'setcronjob_webhook'                       => ['post',     'setcronjob/callback',                            'AdminController@postSetCronJobCallback'                            ],
        'offer_create'                             => ['post',     'offers',                                         'OfferController@createOffer'                                       ],
        'offer_update'                             => ['patch',    'offers/{id}',                                    'OfferController@updateOffer'                                       ],
        'offer_fetch_multiple'                     => ['get',      'offers',                                         'OfferController@fetchOffers'                                       ],
        'offer_fetch_by_id'                        => ['get',      'offers/{id}',                                    'OfferController@fetchOfferById'                                    ],
        'offer_deactivate'                         => ['patch',    'offers/deactivate',                              'OfferController@deactivateOffers'                                  ],
        'refund_create_gateway_record'             => ['post',     'refunds/{gateway}/create_record',                'RefundController@postGatewayRefundRecord'                          ],
        'currency_update_rates'                    => ['post',     'currency/{currency}/rates',                      'CurrencyController@postCurrencyRates'                              ],
        'currency_fetch_rates'                     => ['get',      'currency/{currency}/rates',                      'CurrencyController@getCurrencyRates'                               ],
        'gateway_validate_unknown_refund'          => ['post',     'refunds/{gateway}/validate',                     'RefundController@postGatewayValidateRefund'                        ],
        'reports_fetch_multiple'                   => ['get',      'reports',                                        'ReportController@getReports'                                       ],
        'reports_generate'                         => ['post',     'reports/{entity}/generate',                      'ReportController@generateReport'                                   ],
        'file_get_signed_url'                      => ['get',      '{entity}/{entityId}/signed-url',                 'FileStoreController@getSignedUrlForEntity'                         ],

        // Routes for the admin roles project
        'org_create'                               => ['post',     'orgs',                                           'OrganizationController@postOrganization'                           ],
        'admin_get_by_attr'                        => ['get',      'admins/get-multiple-app-auth',                   'OrganizationController@getAdminMultipleOnAppAuth'                  ],
        'org_get'                                  => ['get',      'orgs/{orgId}',                                   'OrganizationController@getOrganization'                            ],
        'org_get_self'                             => ['get',      'orgs/{id}/self',                                 'OrganizationController@getOrganization'                            ],
        'org_get_by_hostname'                      => ['get',      'orgs/hostname/{hostname}',                       'OrganizationController@getOrganizationByHostname'                  ],
        'org_get_multiple'                         => ['get',      'orgs',                                           'OrganizationController@getOrganizations'                           ],
        'org_edit'                                 => ['put',      'orgs/{orgId}',                                   'OrganizationController@putOrganization'                            ],
        'org_delete'                               => ['delete',   'orgs/{orgId}',                                   'OrganizationController@deleteOrganization'                         ],
        'org_fieldmap_create'                      => ['post',     'field-map',                                      'OrganizationController@postOrgFieldMap'                            ],
        'org_fieldmap_get_multiple'                => ['get',      'field-map',                                      'OrganizationController@getOrgFieldMapMultiple'                     ],
        'org_fieldmap_get'                         => ['get',      'field-map/{id}',                                 'OrganizationController@getOrgFieldMap'                             ],
        'org_fieldmap_get_by_entity'               => ['get',      'field-map/entity/{entity}',                      'OrganizationController@getOrgFieldMapByEntity'                     ],
        'org_fieldmap_edit'                        => ['put',      'field-map/{id}',                                 'OrganizationController@putOrgFieldMap'                             ],
        'org_fieldmap_delete'                      => ['delete',   'field-map/{id}',                                 'OrganizationController@deleteOrgFieldMap'                          ],
        'role_create'                              => ['post',     'roles',                                          'OrganizationController@createRole'                                 ],
        'role_get_multiple'                        => ['get',      'roles',                                          'OrganizationController@getMultipleRoles'                           ],
        'role_get'                                 => ['get',      'roles/{id}',                                     'OrganizationController@getRole'                                    ],
        'role_edit'                                => ['put',      'roles/{id}',                                     'OrganizationController@putRole'                                    ],
        'role_delete'                              => ['delete',   'roles/{id}',                                     'OrganizationController@deleteRole'                                 ],
        'admin_create'                             => ['post',     'admins',                                         'OrganizationController@createAdmin'                                ],
        'admin_get_multiple'                       => ['get',      'admins',                                         'OrganizationController@fetchAdminMultiple'                         ],
        'admin_get_app_auth'                       => ['post',     'current_admin',                                  'OrganizationController@getAdminByAppAuth'                          ],
        'admin_get'                                => ['get',      'admin/{id}/fetch',                               'OrganizationController@getAdmin'                                   ],
        'admin_edit'                               => ['put',      'admin/{id}',                                     'OrganizationController@editAdmin'                                  ],
        'admin_edit_app_auth'                      => ['put',      'admin-app-auth/{id}',                            'OrganizationController@editAdmin'                                  ],
        'admin_fetch_merchant_ids_new'             => ['get',      'admins/merchant_ids',                            'OrganizationController@getMerchantIdsFromEs'                       ],
        'admin_fetch_merchants_new'                => ['get',      'admins/merchants',                               'OrganizationController@getMerchantsFromEs'                         ],
        'admin_delete'                             => ['delete',   'admin/{id}',                                     'OrganizationController@deleteAdmin'                                ],
        'admin_lead_create'                        => ['post',     'admin-lead',                                     'OrganizationController@postAdminLead'                              ],
        'admin_lead_get_multiple'                  => ['get',      'admin-lead-multiple',                            'OrganizationController@getAdminLeadMultiple'                       ],
        'admin_lead_verify'                        => ['get',      'admin-lead/verify/{token}',                      'OrganizationController@verifyAdminLead'                            ],
        'admin_lead_put'                           => ['put',      'admin-lead/{id}',                                'OrganizationController@putAdminLead'                               ],
        'merchant_admin_lead_put'                  => ['put',      'admin-lead-merchant/{id}',                       'OrganizationController@putAdminLead'                               ],
        'admin_authentication'                     => ['post',     'admin/authenticate',                             'OrganizationController@postAuthenticate'                           ],
        'admin_oauth_authenticate'                 => ['post',     'admin/oauth_login',                              'OrganizationController@oAuthLogin'                                 ],
        'admin_forgot_password'                    => ['post',     'admin/forgot_password',                          'OrganizationController@postForgotPassword'                         ],
        'admin_reset_password'                     => ['post',     'admin/reset_password',                           'OrganizationController@postResetPassword'                          ],
        'admin_change_password'                    => ['post',     'admin/change_password',                          'OrganizationController@postChangePassword'                         ],
        'group_create'                             => ['post',     'groups',                                         'OrganizationController@createGroup'                                ],
        'group_get_multiple'                       => ['get',      'groups',                                         'OrganizationController@getGroupsMultiple'                          ],
        'group_get_allowed_groups'                 => ['get',      'groups/{id}/allowed_groups',                     'OrganizationController@getAllowedGroups'                           ],
        'group_get'                                => ['get',      'groups/{id}',                                    'OrganizationController@getGroup'                                   ],
        'group_edit'                               => ['put',      'groups/{id}',                                    'OrganizationController@putGroup'                                   ],
        'group_delete'                             => ['delete',   'groups/{id}',                                    'OrganizationController@deleteGroup'                                ],
        'admin_lock_old_accounts'                  => ['post',     'admins/lock_accounts',                           'OrganizationController@postLockBulkAccounts'                       ],

        // Permission can only be created by certain organizations.
        'permission_create'                        => ['post',     'permissions',                                    'OrganizationController@createPermission'                           ],
        'permission_get_by_type'                   => ['get',      'permissions/get/{type}',                         'OrganizationController@getPermissionsByType'                       ],
        'permission_get'                           => ['get',      'permissions/{id}',                               'OrganizationController@getPermission'                              ],
        'permission_get_multiple'                  => ['get',      'permissions-multiple',                           'OrganizationController@getMultiplePermissions'                     ],
        'permission_delete'                        => ['delete',   'permissions/{id}',                               'OrganizationController@deletePermission'                           ],
        'permission_edit'                          => ['put',      'permissions/{id}',                               'OrganizationController@putPermission',                             ],
        'permission_get_roles'                     => ['get',      'permissions/{id}/roles',                         'OrganizationController@getRolesForPermission'                      ],
        'auditlog_search'                          => ['get',      'auditlog/search',                                'OrganizationController@auditLogSearch'                             ],
        'admin_logout'                             => ['post',     'admin/logout',                                   'OrganizationController@logoutAdmin'                                ],

        // Workflows API
        'workflow_create'                          => ['post',     'workflows',                                      'WorkflowController@createWorkflow'                                 ],
        'workflow_get'                             => ['get',      'workflows/{id}',                                 'WorkflowController@getWorkflow'                                    ],
        'workflow_get_multiple'                    => ['get',      'workflows',                                      'WorkflowController@getWorkflowMultiple'                            ],
        'workflow_update'                          => ['put',      'workflows/{id}',                                 'WorkflowController@updateWorkflow'                                 ],
        'workflow_delete'                          => ['delete',   'workflows/{id}',                                 'WorkflowController@deleteWorkflow'                                 ],
        'workflow_action_get_multiple'             => ['get',      'w-actions',                                      'WorkflowController@getActionMultiple'                              ],
        'workflow_action_update'                   => ['put',      'w-actions/{id}',                                 'WorkflowController@updateWorkflowAction'                           ],
        'action_checker_create'                    => ['post',     'w-actions/{id}/checkers',                        'WorkflowController@postActionChecker'                              ],
        'workflow_action_details'                  => ['get',      'w-actions/{id}/details',                         'WorkflowController@getActionDetails'                               ],
        'workflow_action_close'                    => ['put',      'w-actions/close/{id}',                           'WorkflowController@closeWorkflowAction'                            ],
        'action_diff_get'                          => ['get',      'w-actions/{id}/diff',                            'WorkflowController@getActionDiff'                                  ],
        'action_request_execute'                   => ['post',     'w-actions/{id}/execute',                         'WorkflowController@postExecuteAction'                              ],
        'action_comment_create'                    => ['post',     'w-actions/{id}/comments',                        'WorkflowController@postActionComment'                              ],

        // UPI
        'p2p_fetch_private'                        => ['get',      'p2p/{id}',                                       'P2pController@getP2p'                                              ],
        'vpa_fetch_private'                        => ['get',      'vpa/{id}',                                       'UpiController@getVpaPrivate'                                       ],
        'customer_collect_request_fetch_private'   => ['get',      'customers/{customer_id}/requests/collect',       'P2pController@fetchCollectRequestsPrivate'                         ],
        'device_create'                            => ['post',     'upi/devices',                                    'DeviceController@createDevice'                                     ],
        'device_refresh_token'                     => ['put',      'upi/device/upi_token',                           'DeviceController@refreshUpiToken'                                  ],
        'device_verify'                            => ['post',     'upi/devices/verify',                             'DeviceController@verifyDevice'                                     ],
        'device_fetch'                             => ['get',      'upi/devices/{id}',                               'DeviceController@getDevice'                                        ],
        'upi_customer_bank_accounts_fetch'         => ['get',      'upi/bank_accounts/ifsc/{ifsc}',                  'CustomerController@fetchUpiBankAccounts'                           ],
        'customer_balance_fetch'                   => ['post',     'upi/bank_accounts/{id}/balance',                 'CustomerController@fetchBalance'                                   ],
        'customer_bank_account_fetch'              => ['get',      'upi/bank_accounts/{id}',                         'CustomerController@fetchBankAccount'                               ],
        'reset_mpin'                               => ['put',      'upi/bank_accounts/{id}/mpin',                    'CustomerController@resetMpin'                                      ],
        'set_mpin'                                 => ['post',     'upi/bank_accounts/{id}/mpin',                    'CustomerController@setMpin'                                        ],
        'upi_customer_razor_accounts_fetch'        => ['get',      'upi/bank_accounts',                              'CustomerController@fetchUpiBankAccounts'                           ],
        'vpa_fetch_multiple'                       => ['get',      'upi/vpa/',                                       'UpiController@getVpas'                                             ],
        'vpa_available'                            => ['get',      'upi/vpa/available/{vpa}',                        'UpiController@isAvailableVpa'                                      ],
        'vpa_valid'                                => ['get',      'upi/vpa/valid/{vpa}',                            'UpiController@isValidVpa'                                          ],
        'vpa_create'                               => ['post',     'upi/vpa/',                                       'CustomerController@createVpa'                                      ],
        'vpa_delete'                               => ['delete',   'upi/vpa/{id}',                                   'UpiController@deleteVpa'                                           ],
        'vpa_edit'                                 => ['put',      'upi/vpa/{id}',                                   'UpiController@editVpa'                                             ],
        'vpa_fetch'                                => ['get',      'upi/vpa/{id}',                                   'UpiController@getVpa'                                              ],
        'customer_collect_request_fetch'           => ['get',      'upi/customers/requests/collect',                 'P2pController@fetchCollectRequests'                                ],
        'upi_get_key_list'                         => ['get',      'upi/keyList',                                    'UpiController@getPublicKeyList'                                    ],
        'upi_npci_request'                         => ['post',     'upi_npci/{api}/1.0/urn:txnid:{id}',              'UpiController@newHandle'                                           ],
        'upi_get_bank_list'                        => ['get',      'upi/banks',                                      'UpiController@getBankList'                                         ],
        'upi_zero_call'                            => ['any',      'upi_npci/call/{api}',                            'UpiController@zeroCall'                                            ],
        'upi_read_async'                           => ['get',      'upi/status/{msgId}',                             'UpiController@getStatus'                                           ],
        'p2p_create'                               => ['post',     'upi/p2p',                                        'P2pController@createP2p'                                           ],
        'p2p_fetch'                                => ['get',      'upi/p2p/{id}',                                   'P2pController@getP2p'                                              ],
        'p2p_fetch_multiple'                       => ['get',      'upi/p2p',                                        'P2pController@getP2ps'                                             ],
        'p2p_reject'                               => ['put',      'upi/p2p/{id}/reject',                            'P2pController@rejectP2p'                                           ],
        'p2p_authorize'                            => ['post',     'upi/p2p/{id}/authorize',                         'P2pController@postAuthorize'                                       ],
        'device_customer_fetch'                    => ['get',      'upi/profile',                                    'CustomerController@getDeviceCustomer'                              ],
        'upi_psp_disallow'                         => ['post',     'upi/psp/disallow',                               'UpiController@postPspDisallow'                                     ],
        'upi_psp_allow'                            => ['post',     'upi/psp/allow',                                  'UpiController@postPspAllow'                                        ],
        'mock_event_tracker'                       => ['post',     'mock/track',                                     'MockLumberjackController@mockEventTrack'                           ],
        'payout_fetch_by_id'                       => ['get',      'payouts/{id}',                                   'PayoutController@getPayout'                                        ],
        'payout_fetch_multiple'                    => ['get',      'payouts',                                        'PayoutController@getPayouts'                                       ],
        'payout_create'                            => ['post',     'payouts',                                        'PayoutController@postPayout'                                       ],
        'transfer_fetch'                           => ['get',      'transfers/{id}',                                 'TransferController@getTransfer'                                    ],
        'transfer_fetch_multiple'                  => ['get',      'transfers/',                                     'TransferController@getTransfers'                                   ],
        'transfer_edit'                            => ['patch',    'transfers/{id}',                                 'TransferController@patchTransfer'                                  ],
        'transfer_create'                          => ['post',     'transfers',                                      'TransferController@postTransfer'                                   ],
        'transfer_create_reversal'                 => ['post',     'transfers/{id}/reversals',                       'TransferController@postTransferReversal'                           ],
        'transfer_fetch_reversals'                 => ['get',      'transfers/{id}/reversals',                       'TransferController@getTransferReversals'                           ],
        'reversal_fetch'                           => ['get',      'reversals/{id}',                                 'ReversalController@getReversal'                                    ],
        'reversal_fetch_multiple'                  => ['get',      'reversals',                                      'ReversalController@getReversals'                                   ],
        'payment_update_on_hold'                   => ['post',     'payments/on_hold/update',                        'PaymentController@updateOnHold'                                    ],

        // Dummy routes to test Account Auth
        'internal_dummy_account_test'              => ['get',      '/dummy/internal',                                'MerchantController@getDummyAccount'                                ],
        'admin_dummy_account_test'                 => ['get',      '/dummy/admin',                                   'MerchantController@getDummyAccount'                                ],

        'user_register'                            => ['post',     'users/register',                                 'UserController@registerUser'                                       ],
        'user_merchant_upgrade'                    => ['post',     'users/upgrade-merchant',                         'UserController@postUpgradeUserToMerchant'                          ],
        'user_resend_verification'                 => ['post',     'users/resend-verification',                      'UserController@postResendVerificationMail'                         ],
        'user_reset_password_create'               => ['post',     'users/reset-password',                           'UserController@postResetPasswordByEmail'                           ],
        'user_reset_password_token'                => ['post',     'users/reset-password-token',                     'UserController@postChangePasswordByToken'                          ],
        'user_create'                              => ['post',     'users',                                          'UserController@createUser'                                         ],
        'user_login'                               => ['post',     'users/login',                                    'UserController@loginUser'                                          ],
        'user_confirm_by_data'                     => ['put',      'users/confirm_user_by_data',                     'UserController@confirmUserByData'                                  ],
        'user_fetch_email'                         => ['get',      'users/email/{email}',                            'UserController@getUserByEmail'                                     ],
        'user_edit'                                => ['put',      'users/{id}',                                     'UserController@editUser'                                           ],
        'user_fetch'                               => ['get',      'users/{id}',                                     'UserController@getUser'                                            ],
        // Same as user_fetch but for admin
        'user_fetch_admin'                         => ['get',      'users-admin/{id}',                               'UserController@getUser'                                            ],
        // The order of the following routes is important. The one with action should be last
        'user_confirm'                             => ['put',      'users/{id}/confirm',                             'UserController@confirmUser'                                        ],
        'user_change_password'                     => ['put',      'users/{id}/password',                            'UserController@changeUserPassword'                                 ],
        'user_merchant_mapping_action'             => ['put',      'users/{id}/{action}',                            'UserController@updateUserMaping'                                   ],

        // Tax groups and taxes
        'tax_get_meta_gst_taxes'                   => ['get',      'taxes/meta/gst_taxes',                           'TaxController@getMetaGstTaxes'                                     ],
        'tax_get_meta_states'                      => ['get',      'taxes/meta/states',                              'TaxController@getMetaStates'                                       ],
        'tax_get'                                  => ['get',      'taxes/{id}',                                     'TaxController@get'                                                 ],
        'tax_list'                                 => ['get',      'taxes',                                          'TaxController@list'                                                ],
        'tax_create'                               => ['post',     'taxes',                                          'TaxController@create'                                              ],
        'tax_update'                               => ['patch',    'taxes/{id}',                                     'TaxController@update'                                              ],
        'tax_delete'                               => ['delete',   'taxes/{id}',                                     'TaxController@delete'                                              ],
        'tax_group_get'                            => ['get',      'tax_groups/{id}',                                'TaxGroupController@get'                                            ],
        'tax_group_list'                           => ['get',      'tax_groups',                                     'TaxGroupController@list'                                           ],
        'tax_group_create'                         => ['post',     'tax_groups',                                     'TaxGroupController@create'                                         ],
        'tax_group_update'                         => ['patch',    'tax_groups/{id}',                                'TaxGroupController@update'                                         ],
        'tax_group_delete'                         => ['delete',   'tax_groups/{id}',                                'TaxGroupController@delete'                                         ],

        // Promotion routes
        'promotion_create'                         => ['post',     'promotions',                                     'PromotionController@create'                                        ],
        'promotion_update'                         => ['patch',    'promotions/{id}',                                'PromotionController@update'                                        ],

        // Coupon routes
        'coupon_create'                            => ['post',     'coupons',                                        'CouponController@create'                                           ],
        'coupon_apply'                             => ['post',     'coupons/apply',                                  'CouponController@apply'                                            ],
        'coupon_delete'                            => ['delete',   'coupons/{id}',                                   'CouponController@delete'                                           ],

        // Merchant invitation routes
        'invitation_create'                        => ['post',     'invitations',                                    'InvitationController@create'                                       ],
        'invitation_fetch_by_token'                => ['get',      'invitations/token/{token}',                      'InvitationController@fetchByToken'                                 ],
        'invitation_fetch'                         => ['get',      'invitations',                                    'InvitationController@list'                                         ],
        'invitation_resend'                        => ['put',      'invitations/{id}/resend',                        'InvitationController@postResend'                                   ],
        'invitation_edit'                          => ['patch',    'invitations/{id}',                               'InvitationController@edit'                                         ],
        'invitation_delete'                        => ['delete',   'invitations/{id}',                               'InvitationController@delete'                                       ],
        'invitation_action'                        => ['post',     'invitations/{id}/{action}',                      'InvitationController@postAction'                                   ],
        'migrate_tokens_to_gateway_tokens'         => ['post',     'tokens/migrate/gateway_tokens',                  'CustomerController@postMigrateToGatewayTokens'                     ],

        // Risk Routes
        'risk_create'                              => ['post',     'risk',                                           'RiskController@create'                                             ],
        'risk_update'                              => ['patch',    'risk/{id}',                                      'RiskController@update'                                             ],
        'risk_fetch_multiple'                      => ['get',      'risk',                                           'RiskController@list'                                               ],
        'risk_get'                                 => ['get',      'risk/{id}',                                      'RiskController@get'                                                ],

        // Shield Routes
        'shield_rules_get_multiple'                => ['get',       'shield/rules',                                  'ShieldController@list'                                             ],
        'shield_rules_get'                         => ['get',       'shield/rules/{id}',                             'ShieldController@get'                                              ],
        'shield_rules_update'                      => ['put',       'shield/rules/{id}',                             'ShieldController@update'                                           ],
        'shield_rules_delete'                      => ['delete',    'shield/rules/{id}',                             'ShieldController@delete'                                           ],
        'shield_rules_create'                      => ['post',      'shield/rules',                                  'ShieldController@create'                                           ],
        'shield_rules_evaluate'                    => ['post',      'shield/rules/evaluate',                         'ShieldController@evaluate'                                         ],

        // Dispute routes
        'payment_dispute_create'                   => ['post',     'payments/{paymentId}/disputes',                  'DisputeController@create'                                          ],
        'dispute_edit'                             => ['post',     'disputes/{id}',                                  'DisputeController@update'                                          ],
        'dispute_migrate_adjustments'              => ['post',     'disputes/migrate_old_adjustments',               'DisputeController@migrateOldAdjustments'                           ],
        'dispute_reason_create'                    => ['post',     'disputes/reasons',                               'DisputeController@createReason'                                    ],
        'dispute_fetch_multiple'                   => ['get',      'disputes',                                       'DisputeController@fetchMultiple'                                   ],
        'dispute_fetch'                            => ['get',      'disputes/{id}',                                  'DisputeController@get'                                             ],

        'merchant_payout'                          => ['post',     'merchant/payout',                                'PayoutController@postMerchantPayout'                               ],

        // Settings routes
        'settings_delete'                          => ['delete',   'settings/{module}/{key}',                        'SettingsController@delete'                                         ],
        'settings_fetch_defined'                   => ['get',      'settings/{module}/defined_keys',                 'SettingsController@getDefined'                                     ],
        'settings_fetch'                           => ['get',      'settings/{module}/{key?}',                       'SettingsController@get'                                            ],
        'settings_upsert'                          => ['post',     'settings/{module}',                              'SettingsController@upsert'                                         ],

        // OAuth routes
        'oauth_token_fetch_multiple'               => ['get',      'oauth/tokens',                                   'OAuthTokenController@getAll'                                       ],
        'oauth_token_fetch'                        => ['get',      'oauth/tokens/{id}',                              'OAuthTokenController@get'                                          ],
        'oauth_token_revoke'                       => ['put',      'oauth/tokens/{id}/revoke',                       'OAuthTokenController@revoke'                                       ],
        'oauth_application_create'                 => ['post',     'oauth/applications',                             'OAuthApplicationController@create'                                 ],
        'oauth_application_fetch_multiple'         => ['get',      'oauth/applications',                             'OAuthApplicationController@getMultiple'                            ],
        'oauth_application_fetch'                  => ['get',      'oauth/applications/{id}',                        'OAuthApplicationController@get'                                    ],
        'oauth_application_delete'                 => ['delete',   'oauth/applications/{id}',                        'OAuthApplicationController@delete'                                 ],
        'oauth_merchant_notify'                    => ['post',     'oauth/notify/{type}',                            'MerchantController@sendOAuthNotification'                          ],
        'oauth_application_update'                 => ['post',     'oauth/applications/{id}',                        'OAuthApplicationController@update'                                 ],
        'oauth_sync_merchant_map'                  => ['post',     'oauth/update_merchant_map',                      'MerchantController@updateMerchantAccessMapFromTokens'              ],

        'merchant_analytics'                       => ['post',     'merchant/analytics',                             'MerchantController@postAnalytics'                                  ],

        // Merchant Requests Routes
        'merchant_requests_get'                    => ['get',      'merchant/requests/{id}',                         'MerchantRequestController@get'                                     ],
        'merchant_requests_status_log'             => ['get',      'merchant/requests/{id}/status_log',              'MerchantRequestController@getStatusLog'                            ],
        'merchant_requests_get_feature'            => ['get',      'merchant/requests/{type}/{name}',                'MerchantRequestController@getForFeatureTypeAndName'                ],
        'merchant_requests_list'                   => ['get',      'merchant/requests',                              'MerchantRequestController@getAll'                                  ],
        'merchant_requests_create'                 => ['post',     'merchant/requests',                              'MerchantRequestController@create'                                  ],
        'merchant_requests_update'                 => ['patch',    'merchant/requests/{id}',                         'MerchantRequestController@update'                                  ],
        'merchant_requests_bulk_update'            => ['put',      'merchant/requests/bulk',                         'MerchantRequestController@bulkUpdate'                              ],
        'merchant_requests_rejection_reasons'      => ['get',      'merchant/requests/rejection_reasons',            'MerchantRequestController@getRejectionReasons'                     ],

        'onboarding_features_fetch_details'        => ['get',      'onboarding/features',                            'FeatureController@getOnboardingDetails'                            ],
        'onboarding_features_fetch_submission'     => ['get',      'onboarding/features/{feature}',                  'FeatureController@getOnboardingSubmissions'                        ],
        'onboarding_features_create'               => ['post',     'onboarding/features/{feature}',                  'FeatureController@postOnboardingSubmissions'                       ],
        'onboarding_features_update'               => ['post',     'onboarding/features/{feature}/update',           'FeatureController@updateOnboardingSubmissions'                     ],
        'onboarding_features_get_submissions'      => ['get',      'onboarding/features/submissions/fetch',          'FeatureController@getFeatureOnboardingRequests'                    ],
        'onboarding_features_update_status'        => ['put',      'onboarding/features/{feature}/status',           'FeatureController@updateFeatureActivationStatus'                   ],
        'onboarding_features_fetch_status'         => ['get',      'onboarding/features/{feature}/status',           'FeatureController@getFeatureActivationStatus'                      ],
        'onboarding_features_bulk_update_status'   => ['put',      'onboarding/features/status/bulk',                'FeatureController@bulkUpdateFeatureActivationStatus'               ],

        // Deprecated routes - maintaining for BC - Remove after dashboard changes
        'onboarding_features_fetch_submissions'    => ['get',      'onboarding/features/submissions',                'FeatureController@getFeatureOnboardingRequestsByStatus'            ],

        // Deprecated routes - maintaining for BC - Remove after dashboard changes
        'feature_onboarding_create'                => ['post',     'feature/onboarding/{feature}',                   'FeatureController@postOnboardingSubmissions'                       ],
        'feature_onboarding_fetch_responses'       => ['get',      'feature/onboarding/{feature}/responses',         'FeatureController@getOnboardingSubmissionsDeprecated'              ],
        'feature_onboarding_fetch_all_responses'   => ['get',      'feature/onboarding/responses',                   'FeatureController@getOnboardingSubmissionsDeprecated'              ],

        // Reporting Service
        'reporting_config_get'                     => ['get',      'reporting/configs/{id}',                         'ReportingController@getConfig'                                     ],
        'reporting_config_list'                    => ['get',      'reporting/configs',                              'ReportingController@listConfig'                                    ],
        'reporting_config_create'                  => ['post',     'reporting/configs',                              'ReportingController@createConfig'                                  ],
        'reporting_config_edit'                    => ['patch',    'reporting/configs/{id}',                         'ReportingController@updateConfig'                                  ],
        'reporting_config_delete'                  => ['delete',   'reporting/configs/{id}',                         'ReportingController@deleteConfig'                                  ],
        'reporting_log_get'                        => ['get',      'reporting/logs/{id}',                            'ReportingController@getLog'                                        ],
        'reporting_log_list'                       => ['get',      'reporting/logs',                                 'ReportingController@listLog'                                       ],
        'reporting_log_create'                     => ['post',     'reporting/logs',                                 'ReportingController@createLog'                                     ],
        'reporting_schedule_get'                   => ['get',      'reporting/schedules/{id}',                       'ReportingController@getSchedule'                                   ],
        'reporting_schedule_list'                  => ['get',      'reporting/schedules',                            'ReportingController@listSchedule'                                  ],
        'reporting_schedule_create'                => ['post',     'reporting/schedules',                            'ReportingController@createSchedule'                                ],
        'reporting_schedule_delete'                => ['delete',   'reporting/schedules/{id}',                       'ReportingController@deleteSchedule'                                ],

        // UFH Service
        // TODO: Should change to just /signed_url (No 'get' and underscore)
        'ufh_get_file_signed_url'                  => ['get',      'ufh/file/{fileId}/get-signed-url',               'UfhController@getSignedUrl'                                        ],

        // Account API routes
        'beta_account_create'                      => ['post',     'beta/accounts',                                  'AccountController@create'                                          ],
        'beta_account_fetch'                       => ['get',      'beta/accounts/{id}',                             'AccountController@get'                                             ],
        'beta_account_fetch_multiple'              => ['get',      'beta/accounts',                                  'AccountController@list'                                            ],
        'beta_account_post_bank_account'           => ['post',     'beta/accounts/{id}/bank_accounts',               'AccountController@createOrChangeBankAccount'                       ],
        'beta_account_fetch_setl_destinations'     => ['get',      'beta/accounts/{id}/settlement_destinations',     'AccountController@fetchSettlementDestinations'                     ],
        'account_features_add'                     => ['post',     'accounts/me/features',                           'FeatureController@addAccountFeatures'                              ],
        'account_features_get'                     => ['get',      'accounts/me/features',                           'FeatureController@getAccountFeatures'                              ],

        'account_fetch'                            => ['get',      'accounts',                                       'AccountController@listLinkedAccounts'                              ],

        // Pincode Service
        'pincode_get'                              => ['get',      'pincodes/{id}',                                  'PincodeSearchController@get'                                       ],
        'db_meta_query'                            => ['post',     'db_meta_query',                                  'AdminController@dbMetaDataQuery'                                   ],

        // Deprecated feature routes - maintaining for BC - Remove after dashboard changes
        'feature_get_multiple'                     => ['get',      'features/{entityId}',                            'FeatureController@getMerchantFeatures'                             ],
        'feature_delete'                           => ['delete',   'features/{entityId}/{featureName}',              'FeatureController@deleteFeature'                                   ],

        // Features
        'feature_add'                              => ['post',     'features',                                       'FeatureController@addFeatures'                                     ],
        'feature_get'                              => ['get',      'features/{entityType}/{entityId}',               'FeatureController@getFeatures'                                     ],
        'feature_bulk_assign'                      => ['post',     'features/assign',                                'FeatureController@multiAssignFeature'                              ],
        'feature_bulk_remove'                      => ['post',     'features/remove',                                'FeatureController@multiRemoveFeature'                              ],
        'feature_delete_entity'                    => ['delete',   '{entityType}/{entityId}/features/{featureName}', 'FeatureController@deleteEntityFeature'                             ],

        //Recon summary
        'daily_reconciliation_summary_fetch'       => ['get',      'daily_recon_summary',                            'AdminController@getDailyReconciliationStatusSummary'               ],

        // Generic Lambda handler
        'lambda_post_h2h'                          => ['post',     'lambda/{type}',                                  'LambdaController@processLambda'                                    ],

    ];

    public static $public = [
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
        'payment_callback_post',
        'payment_callback_get',
        'invoice_get_status',
        'invoice_send_notification',
        'invoice_get_pdf',
        // 'invoice_view',
        'merchant_public_get_banks',
        'merchant_methods',
        'merchant_checkout_preferences',
        'mock_acs',
        'mock_atom_payment',
        'mock_amex_payment',
        'mock_axis_migs_payment',
        'mock_first_data_payment',
        'mock_axis_genius_payment',
        'mock_paytm_payment',
        'mock_mobikwik_payment',
        'mock_netbanking_payment',
        'mock_netbanking_payment_get',
        'mock_card_fss_payment',
        'mock_ebs_payment',
        'mock_sharp_payment_post',
        'mock_sharp_payment_get',
        'mock_sharp_payment_submit',
        'mock_wallet_payment',
        'mock_wallet_payment_get',
        'mock_upi_payment',
        'mock_aeps_payment',
        'mock_wallet_payment_with_paymentid',
        'dummy_return_callback',
        'emi_plans_fetch_multiple',
        'customer_get_saved_status',
        'app_delete_token',
        'app_fetch_payments',
        'customer_logout_global',
        'customer_create_token_public',
        'otp_post',
        'otp_verify',
        'otp_verify_app',
        'device_create',
        'merchant_methods_downtime',
        'virtual_account_order_create',
    ];

    public static $device = [
        'set_mpin',
        'reset_mpin',
        'vpa_create',
        'vpa_delete',
        'vpa_edit',
        'customer_balance_fetch',
        'customer_bank_account_fetch',
        'vpa_available',
        'vpa_valid',
        'vpa_fetch_multiple',
        'upi_customer_bank_accounts_fetch',
        'upi_customer_razor_accounts_fetch',
        'device_fetch',
        'device_refresh_token',
        'vpa_fetch',
        'p2p_create',
        'p2p_fetch_multiple',
        'p2p_fetch',
        'p2p_authorize',
        'p2p_reject',
        'customer_collect_request_fetch',
        'device_customer_fetch',
    ];

    public static $publicCallback = [
        'payment_callback_with_key_post',
        'payment_callback_with_key_get',
    ];

    public static $private = [
        'payment_create_private',
        'payment_create_private_old',
        'payment_create_recurring',
        'payment_create_wallet',
        'payment_create_upi',
        'payment_create_openwallet',
        'payment_create_aeps',
        'payment_refund',
        'payment_capture',
        'payment_fetch_transfers',
        'payment_transfer',
        'payment_fetch_by_id',
        'payment_fetch_multiple',
        'payment_fetch_refunds',
        'payment_fetch_refund_by_id',
        'payment_fetch_transaction',
        'payment_fetch_card_details',
        'payment_payout',
        'refund_create',
        'refund_fetch_by_id',
        'refund_fetch_multiple',
        'card_check_recurring',
        'card_fetch_by_id',
        'order_create',
        'order_fetch',
        'order_fetch_by_id',
        'order_payments',
        'feature_dummy',
        'razorx_dummy',
        'webhook_create',
        'webhook_edit',
        'webhook_fetch',
        'webhook_fetch_multiple',
        'oauth_app_webhook_create',
        'setl_fetch_by_id',
        'setl_fetch_multiple',
        'setl_combined_report',
        'customer_create',
        'customer_update',
        'customer_create_token',
        'customer_fetch_by_id',
        'customer_fetch_multiple',
        'customer_update_token',
        'customer_delete_token',
        'customer_fetch_token',
        'customer_fetch_tokens',
        'customer_add_bank_account',
        'customer_fetch_bank_account',
        'invoice_create',
        'invoice_fetch',
        'invoice_fetch_multiple',
        'invoice_update',
        'invoice_issue',
        'invoice_cancel',
        'invoice_delete',
        'invoice_send_notification_private',
        'item_create',
        'item_fetch',
        'item_fetch_multiple',
        'item_update',
        'item_delete',
        'customer_create_address',
        'customer_delete_address',
        'customer_fetch_addresses',
        'customer_set_primary_address',
        'plan_create',
        'plan_fetch',
        'plan_fetch_multiple',
        'subscription_create',
        'subscription_fetch',
        'subscription_fetch_multiple',
        'subscription_cancel',
        'subscription_create_addon',
        'addon_fetch',
        'addon_fetch_multiple',
        'addon_delete',
        'p2p_fetch_private',
        'vpa_fetch_private',
        'customer_collect_request_fetch_private',
        'payout_fetch_by_id',
        'payout_fetch_multiple',
        'payout_create',
        'customer_get_wallet_balance',
        'customer_get_wallet_statement',
        'transfer_fetch_multiple',
        'transfer_fetch',
        'transfer_edit',
        'transfer_create',
        'transfer_create_reversal',
        'virtual_account_create',
        'virtual_account_edit',
        'virtual_account_fetch',
        'virtual_account_fetch_multiple',
        'payment_bank_transfer_fetch',
        'virtual_account_fetch_payments',
        'transfer_fetch_reversals',
        'reversal_fetch',
        'reversal_fetch_multiple',
        'dispute_fetch_multiple',
        'beta_account_create',
        'beta_account_fetch',
        'beta_account_fetch_multiple',
        'beta_account_post_bank_account',
        'beta_account_fetch_setl_destinations',
        'dispute_fetch',
        'account_features_add',
        'account_features_get',
        'payment_acknowledge',
    ];

    // Only routes defined in internalApps go here
    // If a route needs access from the Dashboard
    // Put it in the Admin Array instead
    public static $internal = [
        'admin_authentication',
        'admin_edit_app_auth',
        'admin_forgot_password',
        'admin_get_by_attr',
        'admin_lock_old_accounts',
        'admin_oauth_authenticate',
        'admin_reset_password',
        'bank_transfer_notify',
        'bank_transfer_process',
        'bank_transfer_refund_retry',
        'batch_process_file',
        'billdesk_create_cancelled_refunds',
        'card_update_saved',
        'currency_update_rates',
        'emandate_debit_reconcile',
        'emi_generate_excel',
        'entity_tax_update',
        'fund_transfer_attempt_reconcile',
        'fund_transfer_attempt_recon_report',
        'gateway_file_create',
        'gateway_validate_unknown_refund',
        'geoip_update',
        'invitation_action',
        'invitation_fetch_by_token',
        'invoice_expire_bulk',
        'invoice_send_notifications',
        'merchant_activation_migrate',
        'merchant_admin_lead_put',
        'merchant_create_app_access_mapping',
        'merchant_create_invoice_entities',
        'merchant_invoice_correction',
        'merchant_daily_report',
        'merchant_delete_app_access_mapping',
        'merchant_notify_holiday',
        'merchant_patch_beneficiary_code',
        'merchant_payout',
        'merchant_payout_mail',
        'merchant_post_beneficiary_file',
        'merchant_secret',
        'mock_hdfc_auth_enrolled',
        'mock_hdfc_enroll',
        'mock_hdfc_payment',
        'nodal_initiate_transfer',
        'oauth_merchant_notify',
        'offer_deactivate',
        'order_refund_multiple_authorized',
        'org_get_by_hostname',
        'payment_auth_notify',
        'payment_auto_capture',
        'payment_capture_reminder',
        'payment_refund_authorized',
        'payment_timeout',
        'payment_update_on_hold',
        'payment_verify_multiple',
        'reconciliate',
        'refund_create_gateway_record',
        'refund_gateway_refunded_txns',
        'refund_generate_excel',
        'refund_retry_failed',
        'schedule_migration',
        'schedule_process_tasks',
        'scorecard',
        'setl_initiate',
        'setl_initiate_daily',
        'setl_post_details_old',
        'setl_reconcile_generate',
        'setl_reconcile_h2h',
        'setl_reconcile_test',
        'subscription_cancel_due',
        'subscriptions_charge_invoices',
        'subscriptions_expire',
        'subscriptions_retry',
        'user_change_password',
        'user_confirm_by_data',
        'user_fetch',
        'user_fetch_email',
        'user_login',
        'user_merchant_upgrade',
        'user_register',
        'user_resend_verification',
        'user_reset_password_create',
        'user_reset_password_token',
        'virtual_account_refund_excess',
        'fund_transfer_attempt_process',
        'daily_reconciliation_summary_fetch',
        'lambda_post_h2h',
        'setcronjob_webhook',
    ];

    // The below routes needs X-Dashboard-User-Id in case of any authentication except private and admin.
    // User context is taken from the provided header.
    // Below rotues deal only with user entity without context of merchant.
    public static $userWhitelist = [
        'user_resend_verification',
        'user_fetch',
        'user_change_password',
        'user_merchant_upgrade',
        'invoice_create',
        'invoice_fetch',
        'invoice_fetch_multiple',
        'invoice_update',
        'invoice_issue',
        'invoice_delete',
        'invoice_add_line_items',
        'invoice_update_line_item',
        'invoice_remove_line_item_bulk',
        'invoice_remove_line_item',
        'invoice_send_notification_private',
        'invoice_cancel',
    ];

    public static $proxy = [
        'merchant_fetch_users',
        'transaction_monthly_report',
        'transaction_fetch_by_id',
        'transaction_fetch_multiple',
        'setl_fetch_transactions',
        'setl_get_details',
        'adj_fetch_by_id',
        'adj_fetch_multiple',
        'card_fetch_multiple',
        'balance_fetch',
        'reports_transaction_broking',
        'reports_transaction_dsp',
        'reports_order_rpp',
        'reports_monthly_invoice',
        'reports_public_entity',
        'reports_public_entity_file',
        'bank_account_fetch',
        'merchant_edit_config',
        'merchant_edit_config_logo',
        'merchant_delete_config_logo',
        'merchant_fetch_config',
        'merchant_sub_create',
        'merchant_fetch_referrals',
        'webhook_fetch_events',
        'customer_delete',
        'device_verify_token',
        'app_fetch_tokens',
        'credits_fetch_multiple',
        'credits_fetch_by_id',
        'batch_create',
        'batch_validate_file',
        'batch_fetch_multiple',
        'batch_fetch_by_id',
        'batch_download_file',
        'batch_stats',
        'invoice_issue_by_batch',
        'invoice_notify_by_batch',
        'invoice_get_stats_by_batch_ids',
        'invoice_add_line_items',
        'invoice_update_line_item',
        'invoice_remove_line_item_bulk',
        'invoice_remove_line_item',
        'subscription_manual_retry',
        'subscription_test_charge',
        'subscription_fetch_due_addons',
        'merchant_get_features',
        'merchant_update_features',
        'merchant_create_key',
        'merchant_fetch_keys',
        'merchant_replace_key',
        'merchant_gst_fetch',
        'merchant_gst_edit',
        'merchant_activation_details',
        'merchant_activation_upload_file',
        'merchant_activation_save',
        'merchant_activation_update_website',
        'merchant_activation_business_categories',
        'offer_create',
        'offer_update',
        'offer_fetch_multiple',
        'offer_fetch_by_id',
        'bank_transfer_process_test',
        'reports_fetch_multiple',
        'file_get_signed_url',
        'reports_generate',
        'invitation_create',
        'invitation_fetch',
        'invitation_resend',
        'invitation_edit',
        'invitation_delete',
        'oauth_token_fetch_multiple',
        'oauth_token_fetch',
        'oauth_token_revoke',
        'oauth_application_create',
        'oauth_application_fetch_multiple',
        'oauth_application_fetch',
        'oauth_application_delete',
        'oauth_application_update',
        'merchant_analytics',
        'reports_refund_irctc',
        'feature_onboarding_create',
        'feature_onboarding_fetch_responses',
        'merchant_pre_signup_details',
        'merchant_edit_pre_signup_details',
        'create_submerchant_user',
        'user_merchant_mapping_action',
        'onboarding_features_fetch_details',
        'onboarding_features_create',
        'onboarding_features_fetch_submission',
        'reporting_config_get',
        'reporting_config_list',
        'reporting_config_create',
        'reporting_config_edit',
        'reporting_config_delete',
        'reporting_log_get',
        'reporting_log_list',
        'reporting_log_create',
        'reporting_schedule_get',
        'reporting_schedule_list',
        'reporting_schedule_create',
        'reporting_schedule_delete',
        'ufh_get_file_signed_url',
        'pincode_get',
        'dispute_edit',
        'merchant_get_tags',
        'account_fetch',
        'merchant_add_bank_account',
        'merchant_requests_create',
        'merchant_requests_get_feature',
        'merchant_bank_account_change_status',
        'tax_get',
        'tax_list',
        'tax_create',
        'tax_update',
        'tax_delete',
        'tax_group_get',
        'tax_group_list',
        'tax_group_create',
        'tax_group_update',
        'tax_group_delete',
        'tax_get_meta_states',
        'tax_get_meta_gst_taxes',
    ];

    // These will run on internal auth with the assurance
    // of X-Admin-Token being passed.
    public static $admin = [
        'org_get',
        'org_get_multiple',
        'admin_fetch_merchant_ids_new',
        'admin_fetch_merchants_new',
        'admin_create',
        'group_get',
        'group_get_multiple',
        'group_get_allowed_groups',
        'org_create',
        'org_edit',
        'org_delete',
        'org_fieldmap_create',
        'org_fieldmap_get_multiple',
        'org_fieldmap_get',
        'org_fieldmap_get_by_entity',
        'org_fieldmap_edit',
        'org_fieldmap_delete',
        'role_create',
        'role_get_multiple',
        'role_get',
        'role_edit',
        'role_delete',
        'admin_get_multiple',
        'admin_get',
        'admin_edit',
        'admin_delete',
        'admin_lead_create',
        'admin_lead_put',
        'admin_lead_get_multiple',
        'group_create',
        'group_edit',
        'group_delete',
        'permission_get_multiple',
        'permission_get_by_type',
        'permission_get',
        'permission_get_roles',
        'permission_create',
        'permission_edit',
        'permission_delete',
        'auditlog_search',
        'refund_edit_status',
        'admin_logout',
        'schedule_create',
        'schedule_delete',
        'schedule_update',
        'schedule_assign',
        'schedule_fetch_multiple',
        'setl_reconcile',
        'transaction_bulk_update',
        'setl_update_channel_bulk',
        'setl_fetch_schedule',
        'feature_delete',
        'feature_delete_entity',
        'feature_get',
        'admin_dummy_account_test',
        'admin_get_file',
        // workflows
        'workflow_create',
        'workflow_get',
        'workflow_get_multiple',
        'workflow_update',
        'workflow_delete',
        'action_checker_create',
        'action_diff_get',
        'action_request_execute',
        'action_comment_create',
        'workflow_action_update',
        'workflow_action_details',
        'workflow_action_close',
        'workflow_action_get_multiple',
        'merchants_update_bulk',
        'merchants_update_channel',
        'adj_add',
        'payment_authorize_refund',
        'admin_change_password',
        'pricing_create_plan',
        'merchant_get_pricing',
        'merchant_assign_pricing',
        'pricing_get_plans',
        'pricing_get_gateway_plans',
        'pricing_delete_plan_rule_force',
        'merchant_invoice_update_gstin',
        'merchant_details_fetch',
        'merchant_get_terminals',
        'merchant_invoice_add_bulk',
        'setl_retry',
        'merchant_activation_files',
        'merchant_get_rejection_reasons',
        'merchant_requests_rejection_reasons',
        'merchant_batches',
        'admin_fetch_all_entities',
        'merchant_activation_archive',
        'merchant_activation_status',
        'merchant_activation_status_change_log',
        'onboarding_features_fetch_submissions',
        'onboarding_features_get_submissions',
        'onboarding_features_update_status',
        'onboarding_features_fetch_status',
        'onboarding_features_bulk_update_status',
        'onboarding_features_update',
        'feature_onboarding_fetch_all_responses',
        'pricing_get_merchant_plans',
        'pricing_supported_networks',
        'pricing_add_plan_rule',
        'pricing_get_plan',
        'pricing_delete_plan_rule',
        'payment_verify',
        'payment_authorize_failed',
        'iin_add',
        'emi_plan_add',
        'dummy_critical_error',
        'admin_lead_verify',
        'merchant_tag_add',
        'merchant_tag_delete',
        'merchant_update_key_access',
        'refund_verify_multiple',
        'refund_verify_failed',
        'merchant_edit_email',
        'dispute_reason_create',
        'merchant_tags_bulk',
        'refund_verify',
        'merchant_edit',
        'adj_add_bulk',
        'adj_add_reverse',
        'adjustments_split_for_dispute',
        'admin_fetch_entity_multiple',
        'admin_fetch_terminal_by_id',
        'admin_fetch_entity_by_id',
        'bank_transfer_edit_payer_account',
        'bank_transfer_insert',
        'bank_transfer_strip_payer_accounts',
        'batch_process_by_id',
        'batch_retry_output_file',
        'billdesk_reconcile_cancelled',
        'coupon_apply',
        'coupon_create',
        'coupon_delete',
        'credits_create',
        'credits_delete',
        'credits_edit',
        'currency_fetch_rates',
        'dispute_migrate_adjustments',
        'dummy_route',
        'emi_plan_delete',
        'emi_plan_fetch_by_id',
        'es_aliases_post',
        'es_debug_get',
        'feature_add',
        'feature_bulk_assign',
        'feature_bulk_remove',
        'feature_get_multiple',
        'fund_transfer_attempt_bulk_update',
        'gateway_add_priorities',
        'gateway_create_downtime',
        'gateway_create_rule',
        'gateway_delete_rule',
        'gateway_fetch_priorities',
        'gateway_file_acknowledge',
        'gateway_file_retry',
        'gateway_remove_priorities',
        'gateway_update_downtime',
        'gateway_update_priorities',
        'gateway_update_rule',
        'get_cache_counts',
        'get_config_keys',
        'gratis_postpaid_transactions',
        'iin_edit',
        'iin_fetch_by_iin',
        'iin_fetch_multiple',
        'iin_generate_post',
        'iin_range_upload',
        'iin_upload',
        'internal_dummy_account_test',
        'merchant_actions',
        'merchant_activate',
        'merchant_activation_update',
        'merchant_activation_upload_file_admin',
        'merchant_beneficiary_file',
        'merchant_create',
        'merchant_create_terminal',
        'merchant_delete_terminal',
        'merchant_edit_free_credits',
        'merchant_fetch',
        'merchant_fetch_bank_account',
        'merchant_fetch_multiple',
        'merchant_fetch_webhooks',
        'merchant_generate_test_bank_acnt',
        'merchant_get_banks',
        'merchant_live_disable',
        'merchant_live_enable',
        'merchant_put_payment_methods',
        'merchant_send_activation_mail',
        'merchant_set_banks',
        'merchants_update_bank_account',
        'methods_update_merchants',
        'migrate_tokens_to_gateway_tokens',
        'mock_generate_reconciliation',
        'nodal_add_beneficiary',
        'org_get_self',
        'payment_authorize_time_out',
        'payment_auto_capture_email',
        'payment_bulk_capture',
        'payment_capture_gateway_manual',
        'payment_capture_verify',
        'payment_dispute_create',
        'payment_fix_authorize_at',
        'payment_force_authorize',
        'payments_multiple_authorize_refund',
        'promotion_create',
        'promotion_update',
        'refund_create_missing_txn',
        'refund_gateway_manual',
        'risk_create',
        'risk_fetch_multiple',
        'risk_get',
        'risk_update',
        'schedule_fetch',
        'schedule_update_next_run',
        'send_newsletter',
        'send_test_newsletter',
        'set_config_keys',
        'setl_delete_file',
        'setl_edit',
        'setl_file_generate',
        'setl_fixer',
        'settings_delete',
        'settings_fetch_defined',
        'settings_fetch',
        'settings_upsert',
        'terminal_add_merchant',
        'terminal_check_encrypted_value',
        'terminal_delete',
        'terminal_edit',
        'terminal_reassign_merchant',
        'terminal_remove_merchant',
        'terminal_restore',
        'terminal_toggle',
        'transaction_create_fees_breakup',
        'upi_fill_bank',
        'upi_psp_allow',
        'upi_psp_disallow',
        'user_confirm',
        'user_create',
        'db_meta_query',
        'admin_get_app_auth',
        'nodal_get_account_balance',
        'enable_emi_merchant_sub',
        'oauth_sync_merchant_map',

        // Shield Routes
        'shield_rules_get_multiple',
        'shield_rules_get',
        'shield_rules_create',
        'shield_rules_update',
        'shield_rules_delete',
        'shield_rules_evaluate',

        'user_fetch_admin',
        'merchant_requests_list',
        'merchant_requests_update',
        'merchant_requests_status_log',
        'merchant_requests_get',
        'merchant_requests_bulk_update',
        'merchant_activation_bulk_assign_reviewer',
        'merchant_activation_reviewers',
    ];

    public static $routePermission = [
        'group_create'                             => Permission::CREATE_GROUP,
        'admin_create'                             => Permission::CREATE_ADMIN,
        'group_get'                                => Permission::VIEW_GROUP,
        'group_get_multiple'                       => Permission::VIEW_ALL_GROUP,
        'org_create'                               => Permission::CREATE_ORG,
        'org_get_multiple'                         => Permission::VIEW_ALL_ORG,
        'org_edit'                                 => Permission::EDIT_ORG,
        'org_delete'                               => Permission::DELETE_ORG,
        'org_get'                                  => Permission::VIEW_ORG,
        'role_create'                              => Permission::CREATE_ROLE,
        'role_get_multiple'                        => Permission::VIEW_ALL_ROLE,
        'role_get'                                 => Permission::VIEW_ROLE,
        'role_edit'                                => Permission::EDIT_ROLE,
        'role_delete'                              => Permission::DELETE_ROLE,
        'admin_get_multiple'                       => Permission::VIEW_ALL_ADMIN,
        'admin_get'                                => Permission::VIEW_ADMIN,
        'admin_edit'                               => Permission::EDIT_ADMIN,
        'admin_delete'                             => Permission::DELETE_ADMIN,
        'group_edit'                               => Permission::EDIT_GROUP,
        'group_delete'                             => Permission::DELETE_GROUP,
        'group_get_allowed_groups'                 => Permission::GROUP_GET_ALLOWED_GROUPS,
        'schedule_create'                          => Permission::SCHEDULE_CREATE,
        'schedule_delete'                          => Permission::SCHEDULE_DELETE,
        'schedule_update'                          => Permission::SCHEDULE_UPDATE,
        'schedule_assign'                          => Permission::SCHEDULE_ASSIGN,
        'admin_fetch_merchant_ids_new'             => '*',
        'admin_fetch_merchants_new'                => '*',
        'permission_create'                        => Permission::CREATE_PERMISSION,
        'permission_edit'                          => Permission::EDIT_PERMISSION,
        'permission_get'                           => Permission::GET_PERMISSION,
        'permission_get_multiple'                  => Permission::VIEW_ALL_PERMISSION,
        'permission_get_by_type'                   => Permission::EDIT_ORG,
        'permission_get_roles'                     => Permission::VIEW_ROLE,
        'permission_delete'                        => Permission::DELETE_PERMISSION,
        'auditlog_search'                          => Permission::VIEW_AUDITLOG,
        'admin_logout'                             => '*',
        'org_fieldmap_create'                      => Permission::EDIT_ORG,
        'org_fieldmap_get_multiple'                => Permission::EDIT_ORG,
        'org_fieldmap_get'                         => Permission::EDIT_ORG,
        'org_fieldmap_get_by_entity'               => '*',
        'org_fieldmap_edit'                        => Permission::EDIT_ORG,
        'org_fieldmap_delete'                      => Permission::EDIT_ORG,
        'admin_lead_create'                        => Permission::CREATE_MERCHANT_INVITE,
        'admin_lead_put'                           => Permission::EDIT_MERCHANT_INVITE,
        'admin_lead_get_multiple'                  => Permission::VIEW_MERCHANT_INVITE,
        'admin_dummy_account_test'                 => Permission::VIEW_MERCHANT,
        'feature_delete'                           => Permission::DELETE_MERCHANT_FEATURES,
        'feature_delete_entity'                    => Permission::DELETE_MERCHANT_FEATURES,
        'feature_get'                              => Permission::VIEW_MERCHANT_FEATURES,
        'workflow_create'                          => Permission::CREATE_WORKFLOW, // Fix permissions
        'workflow_get'                             => Permission::VIEW_WORKFLOW,
        'workflow_get_multiple'                    => Permission::VIEW_ALL_WORKFLOW,
        'workflow_update'                          => Permission::EDIT_WORKFLOW,
        'workflow_delete'                          => Permission::DELETE_WORKFLOW,
        'action_checker_create'                    => '*',
        'action_diff_get'                          => '*',
        'action_request_execute'                   => '*',
        'action_comment_create'                    => '*',
        'workflow_action_close'                    => '*',
        'workflow_action_update'                   => '*',
        'workflow_action_details'                  => '*',
        'workflow_action_get_multiple'             => '*',
        'refund_generate_excel'                    => '*',
        'credits_fetch_multiple'                   => Permission::VIEW_MERCHANT_CREDITS_LOG,
        'credits_create'                           => Permission::ADD_MERCHANT_CREDITS,
        'credits_delete'                           => Permission::DELETE_MERCHANT_CREDITS,
        'merchant_put_payment_methods'             => Permission::EDIT_MERCHANT_METHODS,
        'balance_fetch'                            => Permission::VIEW_MERCHANT_BALANCE,
        'feature_get_multiple'                     => Permission::VIEW_MERCHANT_FEATURES,
        'merchant_actions'                         => '*',
        'merchant_live_enable'                     => Permission::EDIT_MERCHANT_ENABLE_LIVE,
        'merchant_live_disable'                    => Permission::EDIT_MERCHANT_DISABLE_LIVE,
        'admin_fetch_entity_by_id'                 => '*',
        'merchant_activation_update'               => '*', // permission handled in code
        'merchant_assign_pricing'                  => Permission::EDIT_MERCHANT_PRICING,
        'merchant_get_banks'                       => Permission::VIEW_MERCHANT_BANKS,
        'merchant_set_banks'                       => Permission::ASSIGN_MERCHANT_BANKS,
        'merchant_fetch_bank_account'              => Permission::VIEW_MERCHANT_BANK_ACCOUNTS,
        'merchant_edit'                            => '*', // permission handled in code
        'adj_add'                                  => Permission::ADD_MERCHANT_ADJUSTMENT,
        'merchant_add_bank_account'                => Permission::EDIT_MERCHANT_BANK_DETAIL,
        'merchant_activate'                        => Permission::EDIT_ACTIVATE_MERCHANT,
        'admin_fetch_terminal_by_id'               => '*',
        'merchants_update_bulk'                    => Permission::EDIT_BULK_MERCHANT,
        'merchants_update_channel'                 => Permission::EDIT_BULK_MERCHANT_CHANNEL,
        'schedule_fetch_multiple'                  => Permission::SCHEDULE_FETCH_MULTIPLE,
        'setl_fetch_schedule'                      => Permission::SCHEDULE_FETCH_MULTIPLE,
        'admin_fetch_all_entities'                 => '*',
        'admin_fetch_entity_multiple'              => '*',
        'payment_authorize_refund'                 => Permission::EDIT_AUTHORIZED_REFUND_PAYMENT,
        'payment_fetch_refunds'                    => Permission::VIEW_REFUND_PAYMENTS,
        'retry_refund_failed'                      => Permission::RETRY_REFUND_FAILED,
        'payment_refund'                           => Permission::EDIT_PAYMENT_REFUND,
        'payment_capture'                          => Permission::EDIT_PAYMENT_CAPTURE,
        'gateway_create_rule'                      => Permission::CREATE_GATEWAY_RULE,
        'gateway_update_rule'                      => Permission::EDIT_GATEWAY_RULE,
        'gateway_delete_rule'                      => Permission::DELETE_GATEWAY_RULE,
        'terminal_toggle'                          => '*',
        'terminal_delete'                          => Permission::DELETE_TERMINAL,
        'terminal_edit'                            => Permission::EDIT_TERMINAL,
        'terminal_reassign_merchant'               => Permission::ASSIGN_MERCHANT_TERMINAL,
        'terminal_add_merchant'                    => '*',
        'terminal_remove_merchant'                 => '*',
        'emi_plan_delete'                          => Permission::DELETE_EMI_PLAN,
        'iin_edit'                                 => Permission::EDIT_IIN_RULE,
        'offer_create'                             => Permission::CREATE_MERCHANT_OFFER,
        'offer_update'                             => Permission::EDIT_MERCHANT_OFFER,
        'merchant_edit_config'                     => Permission::ASSIGN_MERCHANT_HANDLE,
        'merchant_fetch'                           => '*',
        'merchant_get_terminals'                   => '*',
        'merchant_activation_details'              => '*',
        'merchant_fetch_users'                     => '*',
        'admin_change_password'                    => '*',
        'admin_get_file'                           => '*',
        'invitation_fetch'                         => '*',
        'pricing_create_plan'                      => Permission::CREATE_PRICING_PLAN,
        'merchant_get_pricing'                     => Permission::VIEW_MERCHANT_PRICING,
        'merchant_invoice_update_gstin'            => Permission::EDIT_MERCHANT_INVOICE_GSTIN,
        'merchant_details_fetch'                   => '*',
        'setl_retry'                               => Permission::RETRY_SETTLEMENT,
        'setl_update_channel_bulk'                 => Permission::SETTLEMENT_BULK_UPDATE,
        'transaction_bulk_update'                  => Permission::SETTLEMENT_BULK_UPDATE,
        'setl_reconcile'                           => Permission::SETTLEMENT_BULK_UPDATE,
        'merchant_batches'                         => Permission::MERCHANT_BATCH_UPLOAD,
        'merchant_invoice_add_bulk'                => '*',
        'payment_dispute_create'                   => Permission::CREATE_DISPUTE,
        'dispute_edit'                             => Permission::EDIT_DISPUTE,
        'settings_fetch'                           => Permission::VIEW_WALLET_CONFIG,
        'settings_fetch_defined'                   => Permission::VIEW_WALLET_CONFIG,
        'settings_upsert'                          => Permission::EDIT_WALLET_CONFIG,
        'settings_delete'                          => Permission::EDIT_WALLET_CONFIG,
        'merchant_analytics'                       => Permission::VIEW_MERCHANT_ANALYTICS,
        'merchant_activation_files'                => '*',
        'merchant_activation_archive'              => '*', // permission handled in code
        'merchant_activation_status'               => Permission::EDIT_ACTIVATE_MERCHANT,
        'merchant_activation_status_change_log'    => Permission::VIEW_ACTIVATION_FORM,
        'merchant_update_key_access'               => Permission::EDIT_MERCHANT_KEY_ACCESS,
        'merchant_get_rejection_reasons'           => '*',
        'merchant_requests_rejection_reasons'      => '*',
        'dispute_reason_create'                    => Permission::CREATE_DISPUTE_REASON,
        'user_confirm_by_data'                     => '*',
        'onboarding_features_fetch_submissions'    => Permission::MANAGE_ONBOARDING_SUBMISSIONS,
        'onboarding_features_update_status'        => Permission::MANAGE_ONBOARDING_SUBMISSIONS,
        'onboarding_features_fetch_status'         => Permission::MANAGE_ONBOARDING_SUBMISSIONS,
        'onboarding_features_bulk_update_status'   => Permission::MANAGE_ONBOARDING_SUBMISSIONS,
        'onboarding_features_update'               => Permission::MANAGE_ONBOARDING_SUBMISSIONS,
        'onboarding_features_fetch_details'        => Permission::MANAGE_ONBOARDING_SUBMISSIONS,
        'onboarding_features_get_submissions'      => Permission::MANAGE_ONBOARDING_SUBMISSIONS,
        'feature_onboarding_fetch_all_responses'   => '*' /* deprecated; will be removed. not adding permissions */,
        'geoip_update'                             => '*',
        'batch_process_by_id'                      => Permission::RETRY_BATCH,
        'merchant_get_tags'                        => '*',
        'merchant_tags_bulk'                       => '*',
        'pricing_get_merchant_plans'               => '*',
        'pricing_supported_networks'               => '*',
        'pricing_add_plan_rule'                    => '*',
        'pricing_get_plan'                         => '*',
        'pricing_delete_plan_rule'                 => '*',
        'payment_verify'                           => '*',
        'payment_authorize_failed'                 => '*',
        'iin_add'                                  => '*',
        'emi_plan_add'                             => '*',
        'emi_generate_excel'                       => '*',
        'dummy_critical_error'                     => '*',
        'admin_lead_verify'                        => '*',
        'merchant_tag_add'                         => '*',
        'merchant_tag_delete'                      => '*',
        'refund_verify_multiple'                   => '*',
        'refund_verify_failed'                     => '*',
        'merchant_edit_email'                      => '*',
        'refund_verify'                            => '*',
        'pricing_get_plans'                        => '*',
        'pricing_get_gateway_plans'                => '*',
        'pricing_delete_plan_rule_force'           => '*',
        'gateway_file_create'                      => '*',
        'adj_add_bulk'                             => '*',
        'adj_add_reverse'                          => '*',
        'adjustments_split_for_dispute'            => '*',
        'bank_transfer_edit_payer_account'         => '*',
        'bank_transfer_insert'                     => '*',
        'bank_transfer_strip_payer_accounts'       => '*',
        'batch_retry_output_file'                  => '*',
        'billdesk_reconcile_cancelled'             => '*',
        'coupon_apply'                             => '*',
        'coupon_create'                            => '*',
        'coupon_delete'                            => '*',
        'credits_edit'                             => '*',
        'currency_fetch_rates'                     => '*',
        'dispute_migrate_adjustments'              => '*',
        'dummy_route'                              => '*',
        'emi_plan_fetch_by_id'                     => '*',
        'es_aliases_post'                          => '*',
        'es_debug_get'                             => '*',
        'feature_add'                              => '*',
        'feature_bulk_assign'                      => '*',
        'feature_bulk_remove'                      => '*',
        'fund_transfer_attempt_bulk_update'        => '*',
        'gateway_add_priorities'                   => '*',
        'gateway_create_downtime'                  => '*',
        'gateway_fetch_priorities'                 => '*',
        'gateway_file_acknowledge'                 => '*',
        'gateway_file_retry'                       => '*',
        'gateway_remove_priorities'                => '*',
        'gateway_update_downtime'                  => '*',
        'gateway_update_priorities'                => '*',
        'get_cache_counts'                         => '*',
        'get_config_keys'                          => '*',
        'gratis_postpaid_transactions'             => '*',
        'iin_fetch_by_iin'                         => '*',
        'iin_fetch_multiple'                       => '*',
        'iin_generate_post'                        => '*',
        'iin_range_upload'                         => '*',
        'iin_upload'                               => '*',
        'internal_dummy_account_test'              => '*',
        'merchant_activation_upload_file_admin'    => '*',
        'merchant_beneficiary_file'                => '*',
        'merchant_create'                          => '*',
        'merchant_create_terminal'                 => '*',
        'merchant_delete_terminal'                 => '*',
        'merchant_edit_free_credits'               => '*',
        'merchant_fetch_multiple'                  => '*',
        'merchant_fetch_webhooks'                  => '*',
        'merchant_generate_test_bank_acnt'         => '*',
        'merchant_send_activation_mail'            => '*',
        'merchants_update_bank_account'            => '*',
        'methods_update_merchants'                 => '*',
        'migrate_tokens_to_gateway_tokens'         => '*',
        'mock_generate_reconciliation'             => '*',
        'nodal_add_beneficiary'                    => '*',
        'org_get_self'                             => '*',
        'payment_authorize_time_out'               => '*',
        'payment_auto_capture_email'               => '*',
        'payment_bulk_capture'                     => '*',
        'payment_capture_gateway_manual'           => '*',
        'payment_capture_verify'                   => '*',
        'payment_fix_authorize_at'                 => '*',
        'payment_force_authorize'                  => '*',
        'payments_multiple_authorize_refund'       => '*',
        'promotion_create'                         => '*',
        'promotion_update'                         => '*',
        'refund_create_missing_txn'                => '*',
        'refund_gateway_manual'                    => '*',
        'risk_create'                              => '*',
        'risk_fetch_multiple'                      => '*',
        'risk_get'                                 => '*',
        'risk_update'                              => '*',
        'schedule_fetch'                           => '*',
        'schedule_update_next_run'                 => '*',
        'send_newsletter'                          => '*',
        'send_test_newsletter'                     => '*',
        'set_config_keys'                          => '*',
        'setl_delete_file'                         => '*',
        'setl_edit'                                => '*',
        'setl_file_generate'                       => '*',
        'setl_fixer'                               => '*',
        'setl_reconcile'                           => '*',
        'setl_update_channel_bulk'                 => '*',
        'terminal_check_encrypted_value'           => '*',
        'terminal_restore'                         => '*',
        'transaction_bulk_update'                  => '*',
        'transaction_create_fees_breakup'          => '*',
        'upi_fill_bank'                            => '*',
        'upi_psp_allow'                            => '*',
        'upi_psp_disallow'                         => '*',
        'user_confirm'                             => '*',
        'user_create'                              => '*',
        'admin_get_app_auth'                       => '*',
        'reports_transaction_dsp'                  => Permission::VIEW_SPECIAL_MERCHANT_REPORT,
        'reports_refund_irctc'                     => Permission::VIEW_SPECIAL_MERCHANT_REPORT,
        'nodal_get_account_balance'                => '*',
        'enable_emi_merchant_sub'                  => '*',
        'shield_rules_get_multiple'                => Permission::VIEW_SHIELD_RULES,
        'shield_rules_get'                         => Permission::VIEW_SHIELD_RULES,
        'shield_rules_create'                      => Permission::CREATE_SHIELD_RULES,
        'shield_rules_update'                      => Permission::EDIT_SHIELD_RULES,
        'shield_rules_delete'                      => Permission::DELETE_SHIELD_RULES,
        'shield_rules_evaluate'                    => Permission::EVALUATE_SHIELD_RULES,
        'user_fetch_admin'                         => '*',
        'refund_edit_status'                       => '*',
        'batch_create'                             => '*',
        'reporting_config_get'                     => '*',
        'reporting_config_list'                    => '*',
        'reporting_config_create'                  => '*',
        'reporting_config_edit'                    => '*',
        'reporting_config_delete'                  => '*',
        'reporting_log_get'                        => '*',
        'reporting_log_list'                       => '*',
        'reporting_log_create'                     => '*',
        'reporting_schedule_get'                   => '*',
        'reporting_schedule_list'                  => '*',
        'reporting_schedule_create'                => '*',
        'reporting_schedule_delete'                => '*',
        'ufh_get_file_signed_url'                  => '*',
        'merchant_requests_create'                 => '*',
        'merchant_requests_get'                    => '*',
        'merchant_requests_list'                   => Permission::VIEW_MERCHANT_REQUESTS,
        'merchant_requests_get'                    => Permission::VIEW_MERCHANT_REQUESTS,
        'merchant_requests_update'                 => Permission::EDIT_MERCHANT_REQUESTS,
        'merchant_requests_bulk_update'            => Permission::EDIT_MERCHANT_REQUESTS,
        'merchant_requests_status_log'             => Permission::VIEW_MERCHANT_REQUESTS,
        'merchant_bank_account_change_status'      => '*',
        'merchant_activation_reviewers'            => '*',
        'merchant_activation_bulk_assign_reviewer' => Permission::ASSIGN_MERCHANT_ACTIVATION_REVIEWER,
        'db_meta_query'                            => Permission::DB_META_QUERY,
        'oauth_sync_merchant_map'                  => Permission::OAUTH_SYNC_MERCHANT_MAP,
    ];

    public static $direct = [
        'device_verify',
        'upi_get_bank_list',
        'upi_read_async',
        'upi_get_key_list',
        'account',
        'payment_create_checkout_get',
        'invoice_view_live',
        'invoice_view_test',
        'invoice_view_live_post',
        'invoice_view_test_post',
        'subscription_view_live',
        'subscription_view_test',
        'subscription_view_live_post',
        'subscription_view_test_post',
        'sms_callback',
        'checkout_public',
        'mock_hdfc_3dsecure',
        'transparent_redirect_get',
        'transparent_redirect_post',
        'gateway_payment_callback_get',
        'gateway_payment_callback_post',
        'gateway_payment_callback_kotak',
        'gateway_payment_callback_kotak_cancel',
        'gateway_payment_callback_corporation',
        'mailgun_webhook',
        'gateway_downtime_source_webhook',
        'checkout_onyx',
        'checkout_embedded',
        'checkout_hosted',
        'checkout_hosted_get',
        'mock_event_tracker',
        'upi_npci_request',
        'upi_zero_call',
        'mock_billdesk_payment',
        'mock_esigner_payment',
        'qr_code_download_live',
        'qr_code_download_test',
        'gateway_payment_callback_bharatqr',
    ];

    /**
     * List of routes, requiring session changes
     */
    public static $session = [
        'checkout',
        'merchant_checkout_preferences',
        'otp_verify',
        'customer_get_saved_status',
        'payment_create',
        'payment_create_checkout',
        'payment_create_jsonp',
        'payment_create_ajax',
        'payment_create_fees',
        'app_fetch_payments',
        'customer_logout_global',
        'app_delete_token',
    ];

    /**
     * These are all the applications we have
     * If you add something here, add it to $internal as well
     * Nothing here should be in private or admin auth
     */
    public static $internalApps = [
        'dashboard' => [
            '*'
        ],

        'mock_gateways' => [
            'mock_hdfc_enroll',
            'mock_hdfc_auth_enrolled',
            'mock_hdfc_payment',
        ],

        // These routes will be hit from the dashboard.
        // We create a new app because these routes when hit
        // won't have any merchant or admin in context.
        'dashboard_guest' => [
            'user_login',
            'user_register',
            'org_get_by_hostname',
            'user_reset_password_create',
            'user_merchant_upgrade',
            'user_change_password',
            'user_fetch',
            'invitation_action',
            'user_fetch_email',
            'merchant_admin_lead_put',
            'invitation_fetch_by_token',
            'user_resend_verification',
            'user_reset_password_token',
            // Called during signup flow
            'admin_edit_app_auth',
            'admin_authentication',
            'admin_oauth_authenticate',
            'admin_forgot_password',
            'admin_reset_password',
            'admin_get_by_attr',
            'user_confirm_by_data',
        ],

        'cron' => [
            'entity_tax_update',
            'setl_initiate',
            'setl_initiate_daily',
            'setl_reconcile_generate',
            'setl_reconcile_test',
            'nodal_initiate_transfer',
            'payment_auth_notify',
            'payment_timeout',
            'scorecard',
            'merchant_daily_report',
            'merchant_post_beneficiary_file',
            'merchant_notify_holiday',
            'merchant_invoice_correction',
            'payment_auto_capture',
            'payment_verify_multiple',
            'refund_generate_excel',
            'payment_refund_authorized',
            'payment_capture_reminder',
            'emi_generate_excel',
            'setl_post_details_old',
            'invoice_send_notifications',
            'card_update_saved',
            'invoice_expire_bulk',
            'batch_process_file',
            'order_refund_multiple_authorized',
            'subscriptions_charge_invoices',
            'subscriptions_retry',
            'subscriptions_expire',
            'subscription_cancel_due',
            'refund_create_gateway_record',
            'gateway_validate_unknown_refund',
            'currency_update_rates',
            'refund_gateway_refunded_txns',
            'merchant_activation_migrate',
            'billdesk_create_cancelled_refunds',
            'schedule_migration',
            'offer_deactivate',
            'merchant_patch_beneficiary_code',
            'payment_update_on_hold',
            'refund_retry_failed',
            'bank_transfer_refund_retry',
            'reports_transaction_dsp',
            'schedule_process_tasks',
            'virtual_account_refund_excess',
            'merchant_create_invoice_entities',
            'merchant_payout',
            'gateway_file_create',
            'reports_refund_irctc',
            'merchant_payout_mail',
            'geoip_update',
            'fund_transfer_attempt_reconcile',
            'fund_transfer_attempt_recon_report',
            'admin_lock_old_accounts',
            'fund_transfer_attempt_process',
            'daily_reconciliation_summary_fetch',
            // Not actually a cron, but added in this list
            // so the cron app has access to the route.
            'setcronjob_webhook',
        ],

        'kotak' => [
            'bank_transfer_process',
            'bank_transfer_notify',
        ],

        'yesbank' => [
            'bank_transfer_process',
            'bank_transfer_notify',
        ],

        // BharatQR routes are not authenticated
        // currently, so this is not in internal
        // auth

        // 'bharatqr' => [
        //     'gateway_payment_callback_bharatqr',
        // ],

        'mailgun' => [
            'reconciliate',
            'emandate_debit_reconcile',
        ],

        'raven' => [

        ],

        'hosted' => [
            'merchant_secret',
            'payment_acknowledge',
        ],

        'h2h' => [
            'setl_reconcile_h2h',
            'lambda_post_h2h'
        ],

        'auth_service' => [
            'oauth_merchant_notify',
            'merchant_create_app_access_mapping',
            'merchant_delete_app_access_mapping',
        ],
    ];

    protected static $jsonpRoutes = [
        'checkout',
        'payment_create_jsonp',
        'payment_get_status',
        'merchant_public_get_banks',
        'merchant_methods',
        'merchant_methods_downtime',
    ];

    /**
     * A route can belong to multiple features, mapped here
     */
    public static $routeNameToFeaturesMap = [
        'feature_dummy'                        => [Feature::DUMMY],
        'merchant_sub_create'                  => [Feature::AGGREGATOR, Feature::MARKETPLACE],
        'customer_delete'                      => [Feature::TOKENS, Feature::CHARGE_AT_WILL],
        'customer_delete_token'                => [Feature::TOKENS, Feature::CHARGE_AT_WILL],
        'customer_fetch_tokens'                => [Feature::TOKENS, Feature::CHARGE_AT_WILL],
        'payment_create_wallet'                => [Feature::S2SWALLET],
        'payment_create_upi'                   => [Feature::S2SUPI],
        'payment_create_openwallet'            => [Feature::OPENWALLET],
        'payment_create_recurring'             => [Feature::CHARGE_AT_WILL],
        'payment_create_private_old'           => [Feature::S2S],
        'reports_transaction_broking'          => [Feature::BROKING_REPORT],
        'reports_transaction_dsp'              => [Feature::DSP_REPORT],
        'reports_order_rpp'                    => [Feature::RPP_REPORT],
        'payment_payout'                       => [Feature::PAYOUT],
        'payout_create'                        => [Feature::PAYOUT],
        'payout_fetch_by_id'                   => [Feature::PAYOUT],
        'payout_fetch_multiple'                => [Feature::PAYOUT],
        'customer_get_wallet_balance'          => [Feature::OPENWALLET],
        'customer_get_wallet_statement'        => [Feature::OPENWALLET],
        'payment_transfer'                     => [Feature::MARKETPLACE, Feature::OPENWALLET],
        'payment_fetch_transfers'              => [Feature::MARKETPLACE, Feature::OPENWALLET],
        'transfer_create'                      => [Feature::MARKETPLACE, Feature::OPENWALLET],
        'transfer_fetch_multiple'              => [Feature::MARKETPLACE, Feature::OPENWALLET],
        'transfer_fetch'                       => [Feature::MARKETPLACE, Feature::OPENWALLET],
        'transfer_create_reversal'             => [Feature::MARKETPLACE, Feature::OPENWALLET],
        'plan_create'                          => [Feature::SUBSCRIPTIONS],
        'plan_fetch'                           => [Feature::SUBSCRIPTIONS],
        'plan_fetch_multiple'                  => [Feature::SUBSCRIPTIONS],
        'subscription_create'                  => [Feature::SUBSCRIPTIONS],
        'subscription_fetch'                   => [Feature::SUBSCRIPTIONS],
        'subscription_fetch_multiple'          => [Feature::SUBSCRIPTIONS],
        'subscription_manual_retry'            => [Feature::SUBSCRIPTIONS],
        'virtual_account_create'               => [Feature::VIRTUAL_ACCOUNTS],
        'virtual_account_edit'                 => [Feature::VIRTUAL_ACCOUNTS],
        'virtual_account_fetch'                => [Feature::VIRTUAL_ACCOUNTS],
        'virtual_account_fetch_multiple'       => [Feature::VIRTUAL_ACCOUNTS],
        'virtual_account_fetch_payments'       => [Feature::VIRTUAL_ACCOUNTS],
        'reports_refund_irctc'                 => [Feature::IRCTC_REPORT],

        // Account APIs
        'beta_account_create'                  => [Feature::MARKETPLACE],
        'beta_account_fetch'                   => [Feature::MARKETPLACE],
        'beta_account_fetch_multiple'          => [Feature::MARKETPLACE],
        'beta_account_post_bank_account'       => [Feature::MARKETPLACE],
        'beta_account_fetch_setl_destinations' => [Feature::MARKETPLACE],
        'account_fetch'                        => [Feature::MARKETPLACE],
    ];

    /*
     * Routes that can be accessed by other org admins.
     * primarily razorpay org
     */
    public static $crossOrgRoutes = [
        'org_edit',
        'org_get',
        'role_edit',
        'schedule_create',
        'schedule_delete',
        'schedule_update',
        'schedule_assign',
        'admin_dummy_account_test',
        'merchant_invoice_update_gstin',
        'setl_retry',
        'merchant_invoice_add_bulk',
    ];

    const RAZORPAYJS_ROUTES = [
        'payment_cancel',
        'payment_create_ajax',
        'payment_otp_submit',
        'payment_otp_resend',
        'payment_topup_ajax'
    ];

    // Sets TRACE level to CRITICAL for these routes
    const CRITICAL_ROUTES = [
        'payment_create',
        'payment_create_private',
        'payment_create_recurring',
        'payment_create_private_old',
        'payment_create_checkout',
        'payment_create_aeps',
        'payment_create_jsonp',
        'payment_create_ajax',
        'payment_create_fees',
        'payment_create_wallet',
        'payment_create_openwallet',
        'payment_create_upi',
        'payment_callback_post',
        'payment_callback_get',
        'payment_callback_with_key_post',
        'payment_callback_with_key_get',
        'payment_get_status',
        'payment_otp_submit',
        'payment_otp_resend',
        'payment_topup_ajax',
        'payment_topup_post',
        'payment_redirect_callback',
        'gateway_payment_callback_bharatqr',
    ];

    const WORKFLOW_EXECUTE_ROUTE_NAME = 'action_request_execute';

    const WORKFLOW_APPROVE_ROUTE_NAME = 'action_checker_create';

    /**
     * S2S payment routes
     */
    const S2S_PAYMENT_ROUTES = [
        'payment_create_private',
        'payment_create_private_old',
        'payment_create_recurring',
        'payment_create_aeps',
        'payment_create_openwallet',
    ];

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

    public static function getSlaveRoutes()
    {
        return self::$slaveRoutes;
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
        // If current request was on keyless public auth, append the x_entity_id query for public urls.
        if (($key === '') and ($this->ba->isKeylessPublicAuth() === true))
        {
            $parameters['x_entity_id'] = $this->ba->getKeylessXEntityId();
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

    public function getPublicCallbackUrlWithHash($pid, $key = '')
    {
        if ($key === '')
        {
            $key = $this->ba->getPublicKey();
        }

        $secret = $this->app->config->get('app.key');

        $hash = hash_hmac('sha1', $pid, $secret);

        $parameters = ['id' => $pid, 'hash' => $hash];

        return $this->getUrlWithPublicCallbackAuth($parameters);
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
            self::$apiRoutes['payment_create_jsonp'][1],
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
    // @codingStandardsIgnoreEnd

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

        $router = $this->router->$method($uri, ['as' => $name, 'uses' => $action]);

        //
        // We add the web middleware group, conditionally to routes
        // which require cookie / session access
        //
        if (in_array($name, self::$session, true) === true)
        {
            $router->middleware('web');
        }
    }

    public function defineAllExtraRoutes()
    {
        $this->router
             ->any('{all}',
                   [
                       'as' => 'api_catch_all',
                       'uses' => '\RZP\Http\Controllers\PublicController@getCatchAllRoute'
                   ])
             ->where('all', '.*');
    }

    public function defineRootApiRoute()
    {
        $this->router
             ->get('/',
                   [
                       'as' => 'api_root',
                       'uses' => '\RZP\Http\Controllers\PublicController@getRoot'
                   ]);
    }

    public function getApiRouteInCategory($category)
    {
        return array_intersect_key(self::$apiRoutes, array_flip(self::$$category));
    }

    public static function getApiRoute($name)
    {
        return self::$apiRoutes[$name];
    }

    public function isWorkflowExecuteOrApproveCall()
    {
        $routeName = $this->getCurrentRouteName();

        if (($routeName === self::WORKFLOW_EXECUTE_ROUTE_NAME) or
            ($routeName === self::WORKFLOW_APPROVE_ROUTE_NAME))
        {
            return true;
        }

        return false;
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

    public function isS2SPaymentRoute(): bool
    {
        $currentRoute = $this->getCurrentRouteName();

        return (in_array($currentRoute, self::S2S_PAYMENT_ROUTES, true) === true);
    }
}
