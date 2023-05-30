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

    'testCommissionTransactionChannelOnPaymentCaptureForMalaysainMerchants' => [
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
            'content'     => ['1000000000plat','10000000000000','100nonplatform'],
            'status_code' => 200,
        ],
    ],

    'testPartnerFetchWithCommissionInvoiceFeatureWithOffset' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/partner/commission_invoice_feature?limit=1&offset=0',
        ],
        'response' => [
            'content'     => ['1000000000plat'],
            'status_code' => 200,
        ],
    ],

    'testInvoiceFetchForResellerActivatedPartner' => $autoApprovalCaptureRequestResponse,
    'testInvoiceFetchForActivatedResellerPartnerWithMerchantKYC' => $autoApprovalCaptureRequestResponse,
];
