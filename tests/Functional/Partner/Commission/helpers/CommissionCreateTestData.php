<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Tests\Functional\Partner\Constants;

$autoApprovalCaptureRequestResponse = [
    'request' => [
        'method' => 'POST',
        'content' => [],
    ],
    'response' => [
        'content' => [
            'status' => 'captured',
            'entity' => 'payment',
        ],
    ],
];

return [
    'createVirtualAccountQrCodeReceiver' => [
        'method'  => 'POST',
        'url'     => '/virtual_accounts',
        'content' => [
            'name'        => 'Test virtual account',
            'description' => 'VA for tests',
            'receivers'   => [
                'types' => ['qr_code'],
                'qr_code' => [
                    'method' => [
                        'card' =>  false,
                        'upi' =>  true,
                    ]
                ],
            ],
            'amount_expected' => 100000,
        ],
    ],

    'createVirtualAccountBankTransferReceiver' => [
        'method'  => 'POST',
        'url'     => '/virtual_accounts',
        'content' => [
            'name'        => 'Test virtual account',
            'description' => 'VA for tests',
            'receivers'   => [
                'types' => ['bank_account'],
            ],
        ],
    ],

    'createBankTransferPayment' => [
        'method'  => 'POST',
        'url'     => '/ecollect/validate/test',
        'content' => [
            'amount'         => 1000,
            'payer_account'  => '7654321234567',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(12)),
            'time'           => time(),
            'description'    => 'Test bank transfer',
            'payee_account'  => 'random_ac_num',
            'payee_ifsc'     => 'random_ifsc',
        ],
    ],

    'createQRCodePayment' => [
        'method'  => 'POST',
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'content' => [
            'response'        => '92',
            'merchantId'      => 'abcd_bharat_qr',
            'subMerchantId'   => '42324',
            'terminalId'      => '2425',
            'success'         => 'true',
            'message'         => 'Transaction initiated',
            'merchantTranId'  => 'to_be_filled',
            'BankRRN'         => random_int(111111111, 999999999),
            'PayerName'       => 'Ria Garg',
            'PayerVA'         => 'random@icici',
            'PayerAmount'    => '1000.00',
            'TxnStatus'       => 'SUCCESS',
        ],
    ],

    'testImplicitVariableOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableOnPOSPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableOnPaymentCaptureReverseShadow' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableOnPaymentCaptureWithSignUpsource' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testSkipCommissionWithDefaultSignUpsource' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitCommissionFullRefund' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testBulkImplicitCommissionFullRefund' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitCommissionPartialRefund' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitCommissionShouldNotRefundForLastMonth' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testShouldNotCreateRefundForExplicit' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableCommissionCalculate' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitFixedCommissionCalculate' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitCommissionCalculate' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitFixedCommissionCalculateAPI' => [
        'request'  => [
            'url'     => '/internal/calculate_commission',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
                'data'    => [
                    [
                        'partner_id'           => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                        'source_type'          => 'payment',
                        'type'                 => 'implicit',
                        'currency'             => 'INR',
                        'debit'                => 0,
                        'credit'               => 944,
                        'tax'                  => 144,
                        'fee'                  => 944,
                        'notes'                => [],
                        'commission_component' => [
                            'pricing_type'    => 'fixed',
                            'pricing_feature' => 'payment',
                            'merchant_pricing_plan_rule_id' => '1ABp2Xd3t5aRPX',
                            'merchant_pricing_percentage' => 200,
                            'merchant_pricing_fixed' => 0,
                            'merchant_pricing_amount' =>  8000,
                            'commission_pricing_plan_rule_id' => 'C6rNP4gZXcnZWM',
                            'commission_pricing_percentage' => 20,
                            'commission_pricing_fixed' =>  0,
                            'commission_pricing_amount' =>  800,
                        ]
                    ]
                ]
            ],
        ],

    ],

    'testImplicitVariableCommissionCalculateAPIForMY' => [
        'request'  => [
            'url'     => '/internal/calculate_commission',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
                'data'    => [
                    [
                        'partner_id'           => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                        'source_type'          => 'payment',
                        'type'                 => 'implicit',
                        'currency'             => 'MYR',
                        'debit'                => 0,
                        'credit'               => 944   ,
                        'tax'                  => 144,
                        'fee'                  => 944,
                        'notes'                => [],
                        'commission_component' => [
                            'pricing_type'    => 'variable',
                            'pricing_feature' => 'payment',
                            'merchant_pricing_plan_rule_id' => '1ABp2Xd3t5aRPX',
                            'merchant_pricing_percentage' => 200,
                            'merchant_pricing_fixed' => 0,
                            'merchant_pricing_amount' =>  8000,
                            'commission_pricing_plan_rule_id' => '1ABp2Xd3t5aRQX',
                            'commission_pricing_percentage' => 180,
                            'commission_pricing_fixed' =>  0,
                            'commission_pricing_amount' =>  7200,
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testImplicitVariableCommissionCalculateAPI' => [
        'request'  => [
            'url'     => '/internal/calculate_commission',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
                'data'    => [
                    [
                        'partner_id'           => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                        'source_type'          => 'payment',
                        'type'                 => 'implicit',
                        'currency'             => 'INR',
                        'debit'                => 0,
                        'credit'               => 944,
                        'tax'                  => 144,
                        'fee'                  => 944,
                        'notes'                => [],
                        'commission_component' => [
                            'pricing_type'    => 'variable',
                            'pricing_feature' => 'payment',
                            'merchant_pricing_plan_rule_id' => '1ABp2Xd3t5aRPX',
                            'merchant_pricing_percentage' => 200,
                            'merchant_pricing_fixed' => 0,
                            'merchant_pricing_amount' =>  8000,
                            'commission_pricing_plan_rule_id' => '1ABp2Xd3t5aRQX',
                            'commission_pricing_percentage' => 180,
                            'commission_pricing_fixed' =>  0,
                            'commission_pricing_amount' =>  7200,
                        ]
                    ]
                ]
            ],
        ],

    ],

    'testExplicitCommissionCalculateAPI' => [
        'request'  => [
            'url'     => '/internal/calculate_commission',
            'method'  => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true,
                'data'    => [
                    [
                        'partner_id'           => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                        'source_type'          => 'payment',
                        'type'                 => 'explicit',
                        'currency'             => 'INR',
                        'debit'                => 0,
                        'credit'               => 944,
                        'tax'                  => 144,
                        'fee'                  => 944,
                        'source_id'            => 'MLZBpXJVMuWbqM',
                        'notes'                => [],
                        'commission_component' => [
                            'pricing_type'    => 'fixed',
                            'pricing_feature' => 'payment',
                            'merchant_pricing_plan_rule_id' => '1ABp2Xd3t5aRPX',
                            'merchant_pricing_percentage' => 200,
                            'merchant_pricing_fixed' => 0,
                            'merchant_pricing_amount' =>  8000,
                            'commission_pricing_plan_rule_id' => 'C6rNP4gZXcnZWM',
                            'commission_pricing_percentage' => 20,
                            'commission_pricing_fixed' =>  0,
                            'commission_pricing_amount' =>  800,
                        ]
                    ]
                ]
            ],
        ],

    ],


    'testImplicitVariableOnNONINRPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testInvoiceCompleteFlow' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],
    'testInvoiceApprovalFor3MonthOld' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testInvoiceCreateWithAutoApproval' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateWithAutoApprovalDisabled' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateAutoApprovalFailedGSTINPresent' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateAutoApprovalWithGSTINPresentResellerFailed' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateAutoApprovalWithGSTINPresentResellerSuccess' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateAutoApprovalFailedResellerKYCNotApproved' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateAutoApprovalSuccessForNonReseller' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateAutoApprovalFailedExpNotEnabled' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateAutoApprovalFailedForNonResellerKYCStatus' => $autoApprovalCaptureRequestResponse,

    'testInvoiceCreateWithout3SubMtusAfterUpdatedTnc' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testInvoiceCreateWithout3SubMtusBeforeUpdatedTnc' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableOnHoldClearForHighTdsPercentage' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testInvoiceAction' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'action' => 'under_review',
            ],
        ],
        'response' => [
            'content' => [
                'success' => 'true',
            ],
        ],
    ],

    'testInvoiceOnHoldClear' => [
        'request'  => [
            'method'  => 'PUT',
            'url' => '/commissions/invoice/on_hold_clear/bulk',
            'content' => [
                'invoice_ids' => ['a', 'b'],
                'create_tds'  => false,
                'update_invoice_status' => false,
                'skip_processed' => false,
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testInvoiceActionApproved' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'action' => 'approved',
            ],
        ],
        'response' => [
            'content' => [
                'success' => 'true',
            ],
        ],
    ],

    'testInvoiceFetch' => [
        'request'  => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant_id' => 'DefaultPartner',
                'gross_amount' => 1770,
                'tax_amount' => 270,
                'status' => 'under_review',
                'line_items' => [
                    [
                        'name' => 'primary_commission',
                        'gross_amount' => 1770,
                        'tax_amount' => 270,
                        'taxable_amount' => 1500,
                        'taxes' => [
                            [
                                'name' => 'CGST 9%',
                                'rate' => 90000,
                                'rate_type' => "percentage",
                                'tax_amount' => 135,
                            ],
                            [
                                'name' => 'SGST 9%',
                                'rate' => 90000,
                                'rate_type' => "percentage",
                                'tax_amount' => 135,
                            ]
                        ],
                    ],
                ],
                'pdf' => [
                    'type' => 'commission_invoice',
                    'bucket' => 'invoices',
                ],
            ],
        ],
    ],

    'testInvoiceFetchAfterAutoApproved' => [
        'request'  => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant_id' => 'DefaultPartner',
                'gross_amount' => 1770,
                'tax_amount' => 270,
                'status' => 'processed',
                'line_items' => [
                    [
                        'name' => 'primary_commission',
                        'gross_amount' => 1770,
                        'tax_amount' => 270,
                        'taxable_amount' => 1500,
                        'taxes' => [
                            [
                                'name' => 'CGST 9%',
                                'rate' => 90000,
                                'rate_type' => "percentage",
                                'tax_amount' => 135,
                            ],
                            [
                                'name' => 'SGST 9%',
                                'rate' => 90000,
                                'rate_type' => "percentage",
                                'tax_amount' => 135,
                            ]
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testInvoiceGenerate' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/commissions/invoice/create',
            'content' => [],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testImplicitFixedOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableOnPaymentCaptureForMY' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testPlatformPartnerCustomPricingPlan' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testPlatformPartnerAppLevelCustomPricingPlan' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitForRecordOnly' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableAndExplicit' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableAndExplicitForSubvention' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitOnInternationalPayment' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitPricingRuleAbsent' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testGSTForPaymentsLessThan2K' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableAndExplicitPostpaid' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testCustomerBearerExplicitBearerAuth' => [
        'request'  => [
            'url'     => '/payments/create/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 400000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => [
                    'name'  => 'QA Razorpay',
                    'number' => '5104015555555558',
                    'expiry_month' => 11,
                    'expiry_year' => 24,
                    'cvv'         => 124,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => ((4000 * 2 * 18 / 100) + (4000 * 0.2 * 18 / 100)) / 100,
                    'fees'            => ((4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)) / 100,
                    'amount'          => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)) / 100,
                    'razorpay_fee'    => (80 + 8),
                    'original_amount' => 4000,
                ],
            ],
        ],
    ],

    'testCustomerBearerExplicitPublicAuth' => [
        'request'  => [
            'url'     => '/payments/create/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 400000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'qa.testing@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => [
                    'name'  => 'QA Razorpay',
                    'number' => '5104015555555558',
                    'expiry_month' => 11,
                    'expiry_year' => 24,
                    'cvv'         => 124,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => ((4000 * 2 * 18 / 100)) / 100,
                    'fees'            => ((4000 * 2) + (4000 * 2 * 18 / 100)) / 100,
                    'amount'          => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100)) / 100,
                    'razorpay_fee'    => 80,
                    'original_amount' => 4000,
                ],
            ],
        ],
    ],

    'testCustomerBearerPaymentCreateBearerAndPublicAuth' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment failed because fees or tax was tampered',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCustomerBearerExplicitOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testCustomerBearerOnExistingAuthorizedPayment' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testClearOnHoldForCommission' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/commissions/partner/{id}/on_hold/clear',
            'content' => [
                'to' => 1963800112,
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testInitiateCommissionSettlement' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/settlements/initiate',
            'content' => [
                'merchant_ids' => [Constants::DEFAULT_PLATFORM_MERCHANT_ID],
                'use_queue'    => true,
                'balance_type' => 'commission',
            ],
        ],
        'response' => [
            'content' => [
                'total_merchants' => 1,
                'enqueued'        => 1,
                'enqueue_failed'  => 0,
            ],
        ],
    ],

    'testCommissionSettlementForNonActivePartner' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testCaptureCommission' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/commissions/{id}/capture',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
            ],
        ],
    ],

    'testCaptureCommissionWithReverseShadowCommissionEnabled' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/commissions/{id}/capture',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
            ],
        ],
    ],

    'testCaptureCommissionByPartner' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/commissions/partner/{id}/capture',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testFetchCommissionConfigByPayment' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/commission_configs?payment_id=',
        ],
        'response' => [
            'content' => [
                'isPartnerOriginated' => true,
                'partner' => [
                    'type' => 'pure_platform'
                ],
                'tax_components' => [
                    'cgst' => 900,
                    'sgst' => 900
                ],
                'partner_config' => [
                    'commissions_enabled' => true
                ]
            ],
        ],
    ],

    'testFetchCommissionConfigsWithInvalidPayment' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/commission_configs?payment_id=randomId',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],

    'testFetchCommissionConfigByPaymentForMerchant' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/commission_configs?payment_id=randomId',
        ],
        'response' => [
            'content'     => [
                'isPartnerOriginated' => false,
                'partner' => [],
                'tax_components' => [],
                'partner_config' => []
            ],
            'status_code' => 200,
        ]
    ],

    'testBulkCaptureByPartner' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/commissions/partner/capture/bulk',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 2,
            ],
        ],
    ],

    'testBulkCaptureByPartnerInvalidInput' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/commissions/partner/capture/bulk',
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The partner ids field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'createInvoiceDataForLessSubM' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'createBharatQrCode' => [
        'method'  => 'POST',
        'url'     => '/payments/qr_codes',
        'content' => [
            'name'         => 'Test QR Code',
            'description'  => 'QR code for tests',
            'usage'        => 'multiple_use',
            'type'         => 'bharat_qr',
            'fixed_amount' => '0',
            'notes'        => [
                'a' => 'b',
            ],
        ],
    ],

    'createBharatQrCodePayment' => [
        'method'  => 'POST',
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'content' => [
            'merchantId'         => 'abcd_bharat_qr',
            'subMerchantId'     => '78965412',
            'BankRRN'           => '000011100101',
            'merchantTranId'    => 'qrv2',
            'PayerVA'           => '74889837470@ybl',
            'PayerAmount'       => 1000,
            'TxnStatus'         => 'SUCCESS',
            'TxnInitDate'       => '20201108230300',
            'TxnCompletionDate' =>'20201108230300',
            'terminalId'        => null,
            'PayerName'         => null,
            'PayerMobile'       => '0000000000',
        ],
    ],

    'testInvoiceFetchWithLessSubMTestDataExpEnabled' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/commissions/invoice/fetch/bulk',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'please add minimum of 3 subMerchants to view the invoices',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_ADD_MINIMUM_SUBM,
        ]
    ],

    'testInvoiceFetchWithLessSubMTestDataExpDisabled' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/commissions/invoice/fetch/bulk',
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerFetchWithCommissionInvoiceFeature' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/partner/commission_invoice_feature',
        ],
        'response' => [
            'content'     => [
                'partner_ids' => ['1000000000plat','10000000000000','100nonplatform'],
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerFetchWithCommissionInvoiceFeatureWithOffset' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/partner/commission_invoice_feature?limit=1&after_id=',
        ],
        'response' => [
            'content'     => [
                'partner_ids' => ['1000000000plat'],
            ],
            'status_code' => 200,
        ],
    ],

    'testInvoiceFetchForResellerActivatedPartner' => $autoApprovalCaptureRequestResponse,
    'testInvoiceFetchForActivatedResellerPartnerWithMerchantKYC' => $autoApprovalCaptureRequestResponse,
    'testCreateAndCaptureFromPRTS' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/internal/create_and_capture_commission',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "payload" => "{\"commission\":{\"id\":\"10ImplicitPlan\",\"source_id\":\"{payment_id}\",\"source_type\":\"payment\",\"partner_id\":\"1000000000plat\",\"partner_config_id\":\"10ImplicitPlan\",\"config_type\":\"partner_config\",\"type\":\"implicit\",\"status\":\"created\",\"debit\":0,\"credit\":3,\"currency\":\"INR\",\"fee\":3,\"tax\":0,\"transaction_id\":\"\",\"record_only\":0,\"notes\":[],\"model\":\"commission\",\"settlement_id\":\"\",\"settled_at\":0,\"created_at\":1694433789,\"updated_at\":1694433789},\"commission_component\":{\"id\":\"10ImplicitPlan\",\"created_at\":1695326073,\"updated_at\":1695326073,\"commission_id\":\"10ImplicitPlan\",\"pricing_type\":\"variable\",\"pricing_feature\":\"payment\",\"commission_pricing_amount\":109,\"commission_pricing_fixed\":0,\"commission_pricing_percentage\":15,\"commission_pricing_plan_rule_id\":\"JGRAjDX9CxlYoS\",\"merchant_pricing_amount\":1447,\"merchant_pricing_fixed\":0,\"merchant_pricing_percentage\":200,\"merchant_pricing_plan_rule_id\":\"COGRFuwhzSmjqv\"}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "captured" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testCreateAndCaptureFromPRTSWithTransactionId' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/internal/create_and_capture_commission',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "payload" => "{\"commission\":{\"id\":\"10ImplicitPlan\",\"source_id\":\"{payment_id}\",\"source_type\":\"payment\",\"partner_id\":\"1000000000plat\",\"partner_config_id\":\"10ImplicitPlan\",\"config_type\":\"partner_config\",\"type\":\"implicit\",\"status\":\"created\",\"debit\":0,\"credit\":3,\"currency\":\"INR\",\"fee\":3,\"tax\":0,\"transaction_id\":\"OQr2zEwSiWidzN\",\"record_only\":0,\"notes\":[],\"model\":\"commission\",\"settlement_id\":\"\",\"settled_at\":0,\"created_at\":1694433789,\"updated_at\":1694433789},\"commission_component\":{\"id\":\"10ImplicitPlan\",\"created_at\":1695326073,\"updated_at\":1695326073,\"commission_id\":\"10ImplicitPlan\",\"pricing_type\":\"variable\",\"pricing_feature\":\"payment\",\"commission_pricing_amount\":109,\"commission_pricing_fixed\":0,\"commission_pricing_percentage\":15,\"commission_pricing_plan_rule_id\":\"JGRAjDX9CxlYoS\",\"merchant_pricing_amount\":1447,\"merchant_pricing_fixed\":0,\"merchant_pricing_percentage\":200,\"merchant_pricing_plan_rule_id\":\"COGRFuwhzSmjqv\"}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "captured" => true,
                    "transaction_id" => "OQr2zEwSiWidzN",
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testCaptureFromPRTS' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/internal/capture_commission',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "payload" => "{\"commission\":{\"id\":\"10ImplicitPlan\",\"source_id\":\"{payment_id}\",\"source_type\":\"payment\",\"partner_id\":\"1000000000plat\",\"partner_config_id\":\"10ImplicitPlan\",\"config_type\":\"partner_config\",\"type\":\"implicit\",\"status\":\"created\",\"debit\":0,\"credit\":3,\"currency\":\"INR\",\"fee\":3,\"tax\":0,\"transaction_id\":\"\",\"record_only\":0,\"notes\":[],\"model\":\"commission\",\"settlement_id\":\"\",\"settled_at\":0,\"created_at\":1694433789,\"updated_at\":1694433789},\"commission_component\":{\"id\":\"10ImplicitPlan\",\"created_at\":1695326073,\"updated_at\":1695326073,\"commission_id\":\"10ImplicitPlan\",\"pricing_type\":\"variable\",\"pricing_feature\":\"payment\",\"commission_pricing_amount\":109,\"commission_pricing_fixed\":0,\"commission_pricing_percentage\":15,\"commission_pricing_plan_rule_id\":\"JGRAjDX9CxlYoS\",\"merchant_pricing_amount\":1447,\"merchant_pricing_fixed\":0,\"merchant_pricing_percentage\":200,\"merchant_pricing_plan_rule_id\":\"COGRFuwhzSmjqv\"}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "captured" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testCreateAndCaptureFromPRTSAlreadyCreatedCommission' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/internal/create_and_capture_commission',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "payload" => "{\"commission\":{\"id\":\"10ImplicitPlan\",\"source_id\":\"{payment_id}\",\"source_type\":\"payment\",\"partner_id\":\"1000000000plat\",\"partner_config_id\":\"10ImplicitPlan\",\"config_type\":\"partner_config\",\"type\":\"implicit\",\"status\":\"created\",\"debit\":0,\"credit\":3,\"currency\":\"INR\",\"fee\":3,\"tax\":0,\"transaction_id\":\"\",\"record_only\":0,\"notes\":[],\"model\":\"commission\",\"settlement_id\":\"\",\"settled_at\":0,\"created_at\":1694433789,\"updated_at\":1694433789},\"commission_component\":{\"id\":\"10ImplicitPlan\",\"created_at\":1695326073,\"updated_at\":1695326073,\"commission_id\":\"10ImplicitPlan\",\"pricing_type\":\"variable\",\"pricing_feature\":\"payment\",\"commission_pricing_amount\":109,\"commission_pricing_fixed\":0,\"commission_pricing_percentage\":15,\"commission_pricing_plan_rule_id\":\"JGRAjDX9CxlYoS\",\"merchant_pricing_amount\":1447,\"merchant_pricing_fixed\":0,\"merchant_pricing_percentage\":200,\"merchant_pricing_plan_rule_id\":\"COGRFuwhzSmjqv\"}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "captured" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testCreateAndCaptureFromPRTSAlreadyCreatedAndCapturedCommission' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/internal/create_and_capture_commission',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "payload" => "{\"commission\":{\"id\":\"10ImplicitPlan\",\"source_id\":\"{payment_id}\",\"source_type\":\"payment\",\"partner_id\":\"1000000000plat\",\"partner_config_id\":\"10ImplicitPlan\",\"config_type\":\"partner_config\",\"type\":\"implicit\",\"status\":\"created\",\"debit\":0,\"credit\":3,\"currency\":\"INR\",\"fee\":3,\"tax\":0,\"transaction_id\":\"\",\"record_only\":0,\"notes\":[],\"model\":\"commission\",\"settlement_id\":\"\",\"settled_at\":0,\"created_at\":1694433789,\"updated_at\":1694433789},\"commission_component\":{\"id\":\"10ImplicitPlan\",\"created_at\":1695326073,\"updated_at\":1695326073,\"commission_id\":\"10ImplicitPlan\",\"pricing_type\":\"variable\",\"pricing_feature\":\"payment\",\"commission_pricing_amount\":109,\"commission_pricing_fixed\":0,\"commission_pricing_percentage\":15,\"commission_pricing_plan_rule_id\":\"JGRAjDX9CxlYoS\",\"merchant_pricing_amount\":1447,\"merchant_pricing_fixed\":0,\"merchant_pricing_percentage\":200,\"merchant_pricing_plan_rule_id\":\"COGRFuwhzSmjqv\"}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "captured" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testCaptureFromPRTSAlreadyCapturedCommission' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/internal/capture_commission',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "payload" => "{\"commission\":{\"id\":\"10ImplicitPlan\",\"source_id\":\"{payment_id}\",\"source_type\":\"payment\",\"partner_id\":\"1000000000plat\",\"partner_config_id\":\"10ImplicitPlan\",\"config_type\":\"partner_config\",\"type\":\"implicit\",\"status\":\"created\",\"debit\":0,\"credit\":3,\"currency\":\"INR\",\"fee\":3,\"tax\":0,\"transaction_id\":\"\",\"record_only\":0,\"notes\":[],\"model\":\"commission\",\"settlement_id\":\"\",\"settled_at\":0,\"created_at\":1694433789,\"updated_at\":1694433789},\"commission_component\":{\"id\":\"10ImplicitPlan\",\"created_at\":1695326073,\"updated_at\":1695326073,\"commission_id\":\"10ImplicitPlan\",\"pricing_type\":\"variable\",\"pricing_feature\":\"payment\",\"commission_pricing_amount\":109,\"commission_pricing_fixed\":0,\"commission_pricing_percentage\":15,\"commission_pricing_plan_rule_id\":\"JGRAjDX9CxlYoS\",\"merchant_pricing_amount\":1447,\"merchant_pricing_fixed\":0,\"merchant_pricing_percentage\":200,\"merchant_pricing_plan_rule_id\":\"COGRFuwhzSmjqv\"}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "captured" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testCreatePaymentPageWithPartnerRole' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"phone\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"pri__ref__id\",\"required\":true,\"title\":\"Roll No\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":3}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"Class\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":4}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => 100000,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testCreateInvoiceIssuedFromPRTS' => [
        'request' => [
            'method' => 'post',
            'url'    => '/internal/commissions_invoice/process',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "action" => "create_issued",
                "payload" => "{\"Invoice\":{\"id\":\"MLMq2vRFqMlyoJ\",\"created_at\":1691015878,\"updated_at\":1691016142,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"merchant_id\":\"1000000000plat\",\"month\":7,\"year\":2023,\"status\":\"issued\",\"gross_amount\":1264,\"tax_amount\":193,\"balance_id\":\"FD7BWf1yiyRo18\",\"file_details\":{\"fileName\":\"fileName.pdf\",\"location\":\"pdfs/commission/\",\"bucketName\":\"S3BucketName\"},\"line_items\":[{\"id\":\"MLMq2wPcBB9IpP\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"name\":\"primary_commission\",\"entity_type\":\"commission_invoice\",\"entity_id\":\"MLMq2vRFqMlyoJ\",\"gross_amount\":1264,\"currency\":\"INR\",\"tax_amount\":193,\"tax_rate\":1800,\"Taxes\":[{\"id\":\"MLMq2whfYbZBOB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYjuyZsOlMK\",\"name\":\"CGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96},{\"id\":\"MLMq2wn7UaimaB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYqgYcqpr8q\",\"name\":\"SGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96}]}]}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "processed" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateInvoiceIssuedWithRegenerateFromPRTS' => [
        'request' => [
            'method' => 'post',
            'url'    => '/internal/commissions_invoice/process',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "action" => "create_issued",
                "payload" => "{\"regenerate_if_exists\": true,\"Invoice\":{\"id\":\"MLMq2vRFqMlyoJ\",\"created_at\":1691015878,\"updated_at\":1691016142,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"merchant_id\":\"1000000000plat\",\"month\":7,\"year\":2023,\"status\":\"issued\",\"gross_amount\":1264,\"tax_amount\":193,\"balance_id\":\"FD7BWf1yiyRo18\",\"file_details\":{\"fileName\":\"fileName.pdf\",\"location\":\"pdfs/commission/\",\"bucketName\":\"S3BucketName\"},\"line_items\":[{\"id\":\"MLMq2wPcBB9IpP\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"name\":\"primary_commission\",\"entity_type\":\"commission_invoice\",\"entity_id\":\"MLMq2vRFqMlyoJ\",\"gross_amount\":1264,\"currency\":\"INR\",\"tax_amount\":193,\"tax_rate\":1800,\"Taxes\":[{\"id\":\"MLMq2whfYbZBOB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYjuyZsOlMK\",\"name\":\"CGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96},{\"id\":\"MLMq2wn7UaimaB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYqgYcqpr8q\",\"name\":\"SGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96}]}]}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "processed" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateInvoiceAndFinanceWorkflowFromPRTS' => [
        'request' => [
            'method' => 'post',
            'url'    => '/internal/commissions_invoice/process',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "action" => "create_and_finance_workflow",
                "payload" => "{\"Invoice\":{\"id\":\"MLMq2vRFqMlyoJ\",\"created_at\":1691015878,\"updated_at\":1691016142,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"merchant_id\":\"1000000000plat\",\"month\":7,\"year\":2023,\"status\":\"under_review\",\"gross_amount\":1264,\"tax_amount\":193,\"balance_id\":\"FD7BWf1yiyRo18\",\"file_details\":{\"fileName\":\"fileName.pdf\",\"location\":\"pdfs/commission/\",\"bucketName\":\"S3BucketName\"},\"line_items\":[{\"id\":\"MLMq2wPcBB9IpP\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"name\":\"primary_commission\",\"entity_type\":\"commission_invoice\",\"entity_id\":\"MLMq2vRFqMlyoJ\",\"gross_amount\":1264,\"currency\":\"INR\",\"tax_amount\":193,\"tax_rate\":1800,\"Taxes\":[{\"id\":\"MLMq2whfYbZBOB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYjuyZsOlMK\",\"name\":\"CGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96},{\"id\":\"MLMq2wn7UaimaB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYqgYcqpr8q\",\"name\":\"SGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96}]}]},\"TdsPercentage\":{\"IsSet\":true,\"Value\":20}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "processed" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateInvoiceAndSettlementTDSFromPRTS' => [
        'request' => [
            'method' => 'post',
            'url'    => '/internal/commissions_invoice/process',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "action" => "create_and_settlement",
                "payload" => "{\"Invoice\":{\"id\":\"MLMq2vRFqMlyoJ\",\"created_at\":1691015878,\"updated_at\":1691016142,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"merchant_id\":\"1000000000plat\",\"month\":7,\"year\":2023,\"status\":\"approved\",\"gross_amount\":1264,\"tax_amount\":193,\"balance_id\":\"FD7BWf1yiyRo18\",\"file_details\":{\"fileName\":\"fileName.pdf\",\"location\":\"pdfs/commission/\",\"bucketName\":\"S3BucketName\"},\"line_items\":[{\"id\":\"MLMq2wPcBB9IpP\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"name\":\"primary_commission\",\"entity_type\":\"commission_invoice\",\"entity_id\":\"MLMq2vRFqMlyoJ\",\"gross_amount\":1264,\"currency\":\"INR\",\"tax_amount\":193,\"tax_rate\":1800,\"Taxes\":[{\"id\":\"MLMq2whfYbZBOB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYjuyZsOlMK\",\"name\":\"CGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96},{\"id\":\"MLMq2wn7UaimaB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYqgYcqpr8q\",\"name\":\"SGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96}]}]},\"CreateTds\":true,\"TdsPercentage\":{\"IsSet\":true,\"Value\":20}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "processed" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateFinanceWorkflowFromPRTS' => [
        'request' => [
            'method' => 'post',
            'url'    => '/internal/commissions_invoice/process',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "action" => "finance_workflow",
                "payload" => "{\"Invoice\":{\"id\":\"MLMq2vRFqMlyoJ\",\"created_at\":1691015878,\"updated_at\":1691016142,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"merchant_id\":\"1000000000plat\",\"month\":7,\"year\":2023,\"status\":\"under_review\",\"gross_amount\":1264,\"tax_amount\":193,\"balance_id\":\"FD7BWf1yiyRo18\",\"file_details\":{\"fileName\":\"fileName.pdf\",\"location\":\"pdfs/commission/\",\"bucketName\":\"S3BucketName\"},\"line_items\":[{\"id\":\"MLMq2wPcBB9IpP\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"name\":\"primary_commission\",\"entity_type\":\"commission_invoice\",\"entity_id\":\"MLMq2vRFqMlyoJ\",\"gross_amount\":1264,\"currency\":\"INR\",\"tax_amount\":193,\"tax_rate\":1800,\"Taxes\":[{\"id\":\"MLMq2whfYbZBOB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYjuyZsOlMK\",\"name\":\"CGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96},{\"id\":\"MLMq2wn7UaimaB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYqgYcqpr8q\",\"name\":\"SGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96}]}]},\"CreateTds\":true,\"TdsPercentage\":{\"IsSet\":false,\"Value\":0}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "processed" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testSettlementTDSFromPRTS' => [
        'request' => [
            'method' => 'post',
            'url'    => '/internal/commissions_invoice/process',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "action" => "settlement",
                "payload" => "{\"Invoice\":{\"id\":\"MLMq2vRFqMlyoJ\",\"created_at\":1691015878,\"updated_at\":1691016142,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"merchant_id\":\"1000000000plat\",\"month\":7,\"year\":2023,\"status\":\"approved\",\"gross_amount\":1264,\"tax_amount\":193,\"balance_id\":\"FD7BWf1yiyRo18\",\"file_details\":{\"fileName\":\"fileName.pdf\",\"location\":\"pdfs/commission/\",\"bucketName\":\"S3BucketName\"},\"line_items\":[{\"id\":\"MLMq2wPcBB9IpP\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"name\":\"primary_commission\",\"entity_type\":\"commission_invoice\",\"entity_id\":\"MLMq2vRFqMlyoJ\",\"gross_amount\":1264,\"currency\":\"INR\",\"tax_amount\":193,\"tax_rate\":1800,\"Taxes\":[{\"id\":\"MLMq2whfYbZBOB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYjuyZsOlMK\",\"name\":\"CGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96},{\"id\":\"MLMq2wn7UaimaB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYqgYcqpr8q\",\"name\":\"SGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96}]}]},\"CreateTds\":true,\"TdsPercentage\":{\"IsSet\":true,\"Value\":20}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "processed" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testForAdjustmentViaSettlementTDSFromPRTS' => [
        'request' => [
            'method' => 'post',
            'url'    => '/internal/commissions_invoice/process',
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "action" => "settlement",
                "payload" => "{\"Invoice\":{\"id\":\"MLMq2vRFqMlyoJ\",\"created_at\":1691015878,\"updated_at\":1691016142,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"merchant_id\":\"1000000000plat\",\"month\":7,\"year\":2023,\"status\":\"approved\",\"gross_amount\":1264,\"tax_amount\":193,\"balance_id\":\"FD7BWf1yiyRo18\",\"file_details\":{\"fileName\":\"fileName.pdf\",\"location\":\"pdfs/commission/\",\"bucketName\":\"S3BucketName\"},\"line_items\":[{\"id\":\"MLMq2wPcBB9IpP\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"name\":\"primary_commission\",\"entity_type\":\"commission_invoice\",\"entity_id\":\"MLMq2vRFqMlyoJ\",\"gross_amount\":1264,\"currency\":\"INR\",\"tax_amount\":193,\"tax_rate\":1800,\"Taxes\":[{\"id\":\"MLMq2whfYbZBOB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYjuyZsOlMK\",\"name\":\"CGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96},{\"id\":\"MLMq2wn7UaimaB\",\"created_at\":1691015878,\"updated_at\":1691015878,\"deleted_at\":{\"Int64\":0,\"Valid\":false},\"line_item_id\":\"MLMq2wPcBB9IpP\",\"tax_id\":\"9nDpYqgYcqpr8q\",\"name\":\"SGST @ 9%\",\"rate\":90000,\"rate_type\":\"percentage\",\"amount\":96}]}]},\"CreateTds\":true,\"TdsPercentage\":{\"IsSet\":false,\"Value\":0}}",
                "created_at" => 1694433789,
            ],
        ],
        'response' => [
            'content' => [
                "id" => "Mazh0bq30sJVmu",
                "created_at" => 1694433789,
                "response" => [
                    "processed" => true,
                ]
            ],
            'status_code' => 200,
        ],
    ],
];
