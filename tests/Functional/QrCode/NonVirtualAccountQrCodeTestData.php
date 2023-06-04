<?php

use RZP\Gateway\Upi\Icici\Fields;

return [
    'testCreateBharatQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'bharat_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testCreateBharatQrCodeWithEntityOrigin' => [
        'usage'          => 'multiple_use',
        'type'           => 'bharat_qr',
        'fixed_amount'   => false,
    ],

    'processOrNotifyBankTransfer' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Ritesh goel',
            'payer_account'  => '9876543210',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => 'utr_thisisbestutr',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'testCreateUpiQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'upi_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testCreateUpiQrCodeFixedAmount' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'upi_qr',
        'fixed_amount'   => true,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testFetchQrCodeByCustomerId' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'name'         => 'Test QR Code',
                'description'  => 'QR code for tests',
                'type'         => 'upi_qr',
                'fixed_amount' => false,
                'customer_id'  => 'cust_100000customer'
            ]
        ]
    ],

    'testFetchQrCodeById' => [
        'name'         => 'Test QR Code',
        'description'  => 'QR code for tests',
        'type'         => 'upi_qr',
        'fixed_amount' => false,
        'customer_id'  => 'cust_100000customer'
    ],

    'testProcessIciciQrPayment' => [
        'url'     => '/callback/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessIciciQrPaymentInternal' => [
        'url'     => '/payment/callback/bharatqr/upi_icici/internal',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessIciciQrPaymentInternalWithPayerAccountType' => [
        'url'     => '/payment/callback/bharatqr/upi_icici/internal',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
            Fields::PAYER_ACCOUNT_TYPE  => 'CREDIT|123456',
        ],
    ],

    'testProcessIciciQrPaymentWithPayerAccountType' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
            Fields::PAYER_ACCOUNT_TYPE  => 'CREDIT|123456',
        ],
    ],

    'testQrCodePricingForSavings' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
            Fields::PAYER_ACCOUNT_TYPE  => 'SAVINGS',
        ],
    ],

    'testProcessIciciQrPaymentInternalWithInvalidPayerAccountType' => [
        'url'     => '/payment/callback/bharatqr/upi_icici/internal',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
            Fields::PAYER_ACCOUNT_TYPE  => 'INVALIDTYPE',
        ],
    ],

    'processIciciQrPaymentWithDifferentAmountUtil' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testReminderCallback' => [
        'base_url'        => '/reminders/send/test/qr_code/qr_code/',
        'expected_status' => 'closed'
    ],


    'testFetchPaymentsForQrCode' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 4000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'method'            => 'upi',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => 'QRv2 Payment',
                'email'             => null,
                'contact'           => null,
                'error_code'        => null,
                'error_description' => null,
            ]
        ],
    ],

    'testFetchQrCodeByCustomerEmail' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'name'         => 'Test QR Code',
                'description'  => 'QR code for tests',
                'usage'        => 'multiple_use',
                'type'         => 'bharat_qr',
                'fixed_amount' => false,
                'customer_id'  => 'cust_100000customer',
            ],
        ],
    ],

    'tax_invoice' => [
        'number'         => 'INV001',
        'date'           => 1589994898,
        'customer_name'  => 'Abc xyz',
        'business_gstin' => '06AABCU9603R1ZR',
        'gst_amount'     => 4010,
        'cess_amount'    => 200,
        'supply_type'    => 'intrastate'
    ],

    'processOrNotifyRblBankTransfer' => [
        'request'  => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken' => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action'      => 'VirtualAccountTransaction',
                'Data'        => [
                    [
                        'messageType'              => 'ft',
                        'amount'                   => '3439.46',
                        'UTRNumber'                => 'CMS480098890',
                        'senderIFSC'               => 'ICIC0000104',
                        'senderAccountNumber'      => '010405000010',
                        'senderAccountType'        => 'Current Account',
                        'senderName'               => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'   => 'Current Account',
                        'beneficiaryAccountNumber' => '00010469876543210',
                        'creditDate'               => '13-10-2016 1929',
                        'creditAccountNumber'      => '409000404030',
                        'corporateCode'            => 'CAFLT',
                        'clientCodeMaster'         => '02405',
                        'senderInformation'        => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'Status' => 'Success',
            ],
            'status_code' => 200,
        ]
    ],


    'processOrNotifyIciciBankTransfer' => [
        'request' => [
            'url'     => '/ecollect/validate/icici',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_Number_Verification_IN' =>  [
                    [
                        'client_code'     => '2233',
                        'payee_account'   =>  null,
                        'amount'          => '1000.00',
                        'mode'            => 'N',
                        'transaction_id'  => 'ICICI123',
                        'payer_name'      => 'ABCD Limited',
                        'payer_account'   => '22233303415693401',
                        'payer_ifsc'      => 'ICIC0000104',
                        'description'     => 'some info',
                        'date'            => '2019-03-19 20:00:11',
                    ],
                ],
            ],
        ],

        'response' => [
            'content' => [
                'Virtual_Account_Number_Verification_OUT' => [
                    [
                        'client_code'    => '2233',
                        'amount'         => '1000.00',
                        'mode'           => 'N',
                        'transaction_id' => 'ICICI123',
                        'payer_name'     => 'ABCD Limited',
                        'payer_account'  => '22233303415693401',
                        'payer_ifsc'     => 'ICIC0000104',
                        'status'         => 'ACCEPT',
                        'reject_reason'  => '',
                        'date'           => '2019-03-19 20:00:11'
                    ]
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testQrCodeDemo' => [
        'url' => '/payments/qr_codes/demo',
        'method' => 'POST',
        'content' => [
            'name'           => 'Test QR Code',
            'description'    => 'QR code for tests',
            'usage'          => 'multiple_use',
            'type'           => 'upi_qr',
            'fixed_amount'   => '0',
            'notes'          => [
                'a' => 'b',
            ],
        ]
    ],

    'testFetchQrCodeByPaymentId' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'name'         => 'Test QR Code',
                'description'  => 'QR code for tests',
                'type'         => 'upi_qr',
                'fixed_amount' => false
            ]
        ]
    ],

    'testProcessIciciQrPaymentToFetchPayerName' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Rosemin',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessIciciQrPaymentToFetchNotPayerName' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testProcessYesBankQrPaymentInternal' => [
        'url'     => '/payment/callback/bharatqr/upi_yesbank/internal',
        'method'  => 'post',
        'content' => [
            'data'    =>
                [
                    'payment'  =>
                        [
                            'amount_authorized' => 300,
                            'currency'          => 'INR',
                        ],
                    'status'   => 'payment_successful',
                    'terminal' =>
                        [
                            'gateway' => 'upi_yesbank',
                            'vpa'     => 'testvpa@yesb',
                        ],
                    'upi'      =>
                        [
                            'merchant_reference' => 'LnYZWjQcVbWZ4aqrv2',
                            'npci_reference_id'  => '306133002290',
                            'vpa'                => 'kushagra@oksbi',
                        ],
                ],
            'success' => true,
        ],
    ],
];
