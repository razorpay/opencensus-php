<?php

namespace RZP\Tests\Functional\InternationalBankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\BankTransfer\Service;
use RZP\Error\PublicErrorDescription;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;

return [
    'testCreateAccountForCurrencyCloud' => [
      'request' => [
          'url' => '/international/virtual_accounts',
          'method' => 'post',
          'content' => [
          ],
      ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCreateAccountForCurrencyCloudForAllCurrenciesAtOnce' => [
        'request' => [
            'url' => '/international/virtual_accounts',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCreateAccountForCurrencyCloudPricingPlan' => [
        'request' => [
            'url' => '/international/virtual_accounts',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCreateAccountForCurrencyCloudPricingPlanForSingleCurrencyApartFromSwift' => [
        'request' => [
            'url' => '/international/virtual_accounts',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCreateAccountForCurrencyCloudPricingPlanForEnablingAllCurrencies' => [
        'request' => [
            'url' => '/international/virtual_accounts',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCreateAccountForCurrencyCloudPricingPlanForEnablingSingleCurrency' => [
        'request' => [
            'url' => '/international/virtual_accounts',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],


    'testCreateAccountForCurrencyCloudPricingPlanMultipleMerchant' => [
        'request' => [
            'url' => '/international/virtual_accounts',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCreateAccountForCurrencyCloudForSegmentEvent' => [
        'request' => [
            'url' => '/international/virtual_accounts',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFailCreateAccountForCurrencyCloud' => [
      'request' => [
          'url' => '/international/virtual_accounts',
          'method' => 'post',
          'content' => [
              'accept_b2b_tnc' => 0,
              'va_currency' => "USD",
          ]
      ],
      'response' => [
          'content'     => [
              'error' => [
                  'description'   => PublicErrorDescription::BAD_REQUEST_TERMS_AND_CONDITIONS_NOT_CHECKED,
              ],
          ],
          'status_code' => 400,
      ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_TERMS_AND_CONDITIONS_NOT_CHECKED,
        ],
    ],

    'testFailCreateAccountForCurrencyCloudMozartError' => [
      'request' => [
          'url' => '/international/virtual_accounts',
          'method' => 'post',
          'content' => [
              'accept_b2b_tnc' => 1,
              'va_currency' => "USD",
          ]
      ],
      'response' => [
          'content'     => [
              'error' => [
                  'description'   => PublicErrorDescription::BAD_REQUEST_VIRTUAL_ACCOUNT_CREATION_FAILED,
              ],
          ],
          'status_code' => 400,
      ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CREATION_FAILED,
        ],
    ],

    'testCashManagerTransactionNotificationForCurrencyCloud' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'cash_manager_transaction_notification'
            ],
            'content' => [
                    'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                    'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                    'currency' => 'USD',
                    'amount' => '47',
                    'related_entity_type' => 'inbound_funds',
                    'related_entity_id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                    'related_entity_short_reference' => 'IF-20230609-GFOTB9'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testSplitPaymentsForCurrencyCloud' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'cash_manager_transaction_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                'currency' => 'USD',
                'amount' => '32000',
                'related_entity_type' => 'inbound_funds',
                'related_entity_id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'related_entity_short_reference' => 'IF-20230609-GFOTB9'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCashManagerTransactionNotificationForCurrencyCloudForFPS' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'cash_manager_transaction_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                'currency' => 'GBP',
                'amount' => '47',
                'related_entity_type' => 'inbound_funds',
                'related_entity_id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'related_entity_short_reference' => 'IF-20230609-GFOTB9'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCashManagerTransactionNotificationForCurrencyCloudForSEPA' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'cash_manager_transaction_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                'currency' => 'GBP',
                'amount' => '47',
                'related_entity_type' => 'inbound_funds',
                'related_entity_id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'related_entity_short_reference' => 'IF-20230609-GFOTB9'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCashManagerTransactionNotificationForCurrencyCloudForSEPASegmentEvent' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'cash_manager_transaction_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                'currency' => 'GBP',
                'amount' => '47',
                'related_entity_type' => 'inbound_funds',
                'related_entity_id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'related_entity_short_reference' => 'IF-20230609-GFOTB9'
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],


    'testDirectCaptureFailureForCurrencyCloud' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payments/' . 'id' . '/capture',
            'content' => [
                'amount'    => '30000',
                'currency'  => 'USD',
            ],
        ],
        'response'  => [
            'content'     => [],
        ],
    ],

    'testTransferCompletedNotificationSWIFTFromCurrencyCloud' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'transfer_completed_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'reason' => '',
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCashManagerTransactionNotificationForCurrencyCloudWithHeaderInInput' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => null,
            'content' => [
                'header' => [
                    'message_type' => 'cash_manager_transaction',
                    'notification_type' => 'cash_manager_transaction_notification'
                ],
                'body' => [
                    'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                    'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                    'currency' => 'USD',
                    'amount' => '47',
                    'related_entity_type' => 'inbound_funds',
                    'related_entity_id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                    'related_entity_short_reference' => 'IF-20230609-GFOTB9'
                ]
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCashManagerSWIFTTransactionFlow' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => null,
            'content' => [
                'header' => [
                    'message_type' => 'cash_manager_transaction',
                    'notification_type' => 'cash_manager_transaction_notification'
                ],
                'body' => [
                    'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                    'account_id' => '15b78101-0142-44a1-9758-8f7262429e9b',
                    'currency' => 'USD',
                    'amount' => '47',
                    'related_entity_type' => 'inbound_funds',
                    'related_entity_id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                    'related_entity_short_reference' => 'IF-20230609-GFOTB9'
                ]
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testTransferCompletedNotificationSEPAFromCurrencyCloud' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'transfer_completed_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'reason' => '',
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testTransferCompletedNotificationFPSFromCurrencyCloud' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'transfer_completed_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'reason' => '',
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testTransferCompletedNotificationACHFromCurrencyCloud' => [
        'request' => [
            'url' => '/international/virtual_accounts/payment/create',
            'method' => 'post',
            'headers' => [
                'notification_type' => 'transfer_completed_notification'
            ],
            'content' => [
                'id' => 'a0d9034e-bc9f-45e7-a1e4-6485735798f6',
                'reason' => '',
            ]
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCaptureCronForB2BPayments' => [
        'request' => [
            'url' => '/b2b/payments/capture',
            'method' => 'post'
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCaptureCronForB2BAmazonPayments' => [
        'request' => [
            'url' => '/b2b/payments/capture',
            'method' => 'post'
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCaptureCronForB2BPaymentsForDisableIntlAccount' => [
        'request' => [
            'url' => '/b2b/payments/capture',
            'method' => 'post'
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testSettlementCronForB2BPayments' => [
        'request' => [
            'url' => '/b2b/payments/settlement',
            'method' => 'post'
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testTradeCallFailingSettlementCronForB2BPayments' => [
        'request' => [
            'url' => '/b2b/payments/settlement',
            'method' => 'post'
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testSuccessfulSettlementCronForB2BPayments' => [
        'request' => [
            'url' => '/b2b/payments/settlement',
            'method' => 'post'
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testGetBalanceDetailsForMerchantVA' => [
        'request' => [
            'url' => '/international/virtual_accounts/balance/USD',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testCreateBeneficiaryForMerchantInCC' => [
        'request' => [
            'url' => '/merchant/%s/international/virtual_accounts/beneficiary',
            'method' => 'post',
            'content' => [
                'currency'=> 'USD',
                'account_number'=> '1234123',
                'bank_account_holder_name'=> 'Razorpay Stage',
                'bank_address'=> 'NO.302,GROUND FLOOR,7TH CROSS,DOMLUR LAYOUT,BANGALORE - 560071',
                'bank_name'=> 'ICICI Bank',
                'bank_country'=> 'IN',
                'bic_swift'=> 'ICICINBBCTS',
                'beneficiary_address'=> '1ST FLOOR,22 LASKAR HOSUR ROAD,ADUGODI,SJR CYBER, BANGALORE,KARNATAKA,INDIA - 560030',
                'beneficiary_city'=> 'delhi',
                'beneficiary_company_name'=> 'Razorpay',
                'beneficiary_country'=> 'IN',
                'beneficiary_entity_type'=> 'company',
                'name'=> 'Razopay Payments',
                'beneficiary_postcode' => 'asdasdad',
                'beneficiary_state_or_province' => 'sasada asdasd',
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],
    'createInternationalIntegration' => [
        'request' => [
            'url' => '/merchant/international_integration',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testToggleInternationalVirtualAccountForMerchantForInvalidAction' => [
        'request' => [
            'url' => '/international/virtual_account/toggle',
            'method' => 'post',
            'content' => [
                'action' => "random",
            ],
        ],
        'response' => [],
    ],

    'testToggleInternationalVirtualAccountForMerchantForDisableAction' => [
        'request' => [
            'url' => '/international/virtual_account/toggle',
            'method' => 'post',
            'content' => [
                'action' => "deactivate",
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testToggleInternationalVirtualAccountForMerchantForEnableAction' => [
        'request' => [
            'url' => '/international/virtual_account/toggle',
            'method' => 'post',
            'content' => [
                'action' => "activate",
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testToggleInternationalVirtualAccountForMerchantForEnableActionForEnabledAccount' => [
        'request' => [
            'url' => '/international/virtual_account/toggle',
            'method' => 'post',
            'content' => [
                'action' => "activate",
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],
];
