<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\BankTransfer\Service;
use RZP\Error\PublicErrorDescription;
use RZP\Trace\TraceCode;

return [
    'createVirtualAccount' => [
        'url'     => '/virtual_accounts',
        'method'  => 'post',
        'content' => [
            'receivers' => [
                'types' => [
                    'bank_account',
                ],
            ],
        ],
    ],

    'processBankTransfer' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [],
    ],

    'testBankTransferProcessXDemoCron' => [
        'request' => [
            'url'    => '/ecollect/validate/test/x-demo-cron',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'valid'   => true,
                'message' => null,
            ]
        ],
    ],

    'notifyBankTransfer' => [
        'url'     => '/ecollect/pay',
        'method'  => 'post',
        'content' => [],
    ],

    'processOrNotifyBankTransfer' => [
        'url'     => null,
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => 'utr_thisisbestutr',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
        'server' => [
            'HTTP_X-Request-Origin' => config('applications.banking_service_url')
        ],
    ],

    'testBankTransferImps' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDB9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testBankTransferImpsWithNbin' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => '9761',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testBankTransferYesbankMIS' => [
        'url'     => '/ecollect/validate/yesbank/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => '9020',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'testBankTransferYesbankMISForClosedVirtualAccount' => [
        'url'     => '/ecollect/validate/yesbank/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => '9020',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'testBankTransferYesBankRefundsNotAllowed' => [
        'request' => [
            'url'     => '/ecollect/validate/test',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => 'YESB0CMSNOC',
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '9876543210123456789',
                'payer_ifsc'     => 'HDB9876543210',
                'mode'           => 'imps',
                'transaction_id' => strtoupper(random_alphanum_string(22)),
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Refund is currently not supported for this payment method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
        ],
    ],

    'testBankTransferInsert' => [
        'url'     => '/bank_transfers/dashboard',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees, inserted',
        ],
    ],

    'testBankTransferFloatingPointImprecision' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 579.3,
            'description'    => 'NEFT payment of 579 rupees and 30 paise',
        ],
    ],

    'testBankTransferRbl' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
              'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'ft',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'CMS480098890',
                        'senderIFSC'                => 'ICIC0000104',
                        'senderAccountNumber'       => '010405000010',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '00010469876543210',
                        'creditDate'                => '13-10-2016 1929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testRblBankTransferWithEmptyPayeeAccount' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'IMPS',
                        'beneficiaryAccountNumber'  => '',
                        'beneficiaryAccountType'    => 'Current Account',
                        'senderName'                => 'BharatPe',
                        'senderAccountNumber'       => '123412341234',
                        'senderIFSC'                => 'SBIN0000002',
                        'senderAccountType'         => 'Current Account',
                        'amount'                    => '100.50',
                        'senderInformation'         => '',
                        'UTRNumber'                 => '12345ABCDE01',
                        'creditDate'                => '14-02-2020 201500',
                        'creditAccountNumber'       => '409000694314',
                        'corporateCode'             => 'RAZORPAY',
                        'clientCodeMaster'          => '',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Failure.',
            ],
            'status_code' => 400,
        ],
    ],

    'testBankTransferRblRefund' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'ft',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'CMS480098890',
                        'senderIFSC'                => 'ICIC0000104',
                        'senderAccountNumber'       => '010405000010',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '00010469876543210',
                        'creditDate'                => '13-10-2016 1929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testBankTransferRblImps' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'IMPS',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'IMPS 006713653919 FROM MR  AAGOSH',
                        'senderIFSC'                => 'SBIN0000000',
                        'senderAccountNumber'       => '00000033980059612',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '00010469876543210',
                        'creditDate'                => '13-10-2016 1929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testIciciBankTransferCallback' => [
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

    'testIciciBankTransferCallbackInvalid' => [
        'request' => [
            'url'     => '/ecollect/validate/icici',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_Number_Verification_IN' =>  [
                    [
                        'client_code'     => '2233',
                        'payee_account'   =>  '22233303415693402',
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
                        'payee_account'   =>  '22233303415693402',
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

    'testIciciBankTransferCallbackBadRequest' => [
        'request' => [
            'url'     => '/ecollect/validate/icici',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_Number_Verification_IN' =>  [
                    [
                        'client_code'     => '2233',
                        'payee_account'   =>  '22233303415693402',
                        'amount'          => '1000.00',
                        'mode'            => 'N',
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
                        'payee_account'  =>  '22233303415693402',
                        'amount'         => '1000.00',
                        'mode'           => 'N',
                        'payer_name'     => 'ABCD Limited',
                        'payer_account'  => '22233303415693401',
                        'payer_ifsc'     => 'ICIC0000104',
                        'status'         => 'REJECT',
                        'reject_reason'  => 'BAD_REQUEST',
                        'date'           => '2019-03-19 20:00:11'
                    ]
                ]
            ],
            'status_code' => 400,
        ]
    ],

    'testHdfcEcmsBankTransferCallback' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> '10000',
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 0,
                'Reason' => 'Success',
                'transaction_id' => '02081900017',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcFailVAOnValidation' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 200,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Payment amount can not exceed the order amount',
                'transaction_id' => '02081900017',
            ],
            'status_code' => 200,
        ]
    ],

    "testHdfcFailVAOnValidationWithoutFeatureFlag" =>  [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 200,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Payment amount can not exceed the order amount',
                'transaction_id' => '02081900017',
            ],
            'status_code' => 200,
        ]
    ],


    'testHdfcEcmsBankTransferCallbackBadRequest' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> '10000',
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017!',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Invalid UTR',
                'transaction_id' => '02081900017!',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferDuplicateTransaction' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> '10000',
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Duplicate Transaction',
                'transaction_id' => '02081900017',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferTransactionNotFound' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> '10000',
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Transaction Not Found',
                'transaction_id' => '02081900017',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackAlreadyProcessed' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> '10000',
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 0,
                'Reason' => 'Already Processed',
                'transaction_id' => '02081900017',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackExpiry' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> '10000',
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],

        ],
        'response' => [
            'content' => [
                'Status' => 0,
                'Reason' => 'Success',
                'transaction_id' => '02081900017',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackMaxAmountThresholdExceeded' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 12345000,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Payment amount can not exceed the transaction limit of the merchant',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackFeatureHigherAmount' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 10003,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 0,
                'Reason' => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackFeatureHigherAmountFailure' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 12,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Payment amount can not be less than the order amount',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackFeatureLowerAmount' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 12,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 0,
                'Reason' => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackFeatureLowerAmountFailure' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 10005,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 1,
                'Reason' => 'Payment amount can not exceed the order amount',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackFeatureHigherAndLowerAmount' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 11000,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 0,
                'Reason' => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testHdfcEcmsBankTransferCallbackFeatureHigherWithPartialPayment' => [
    'request' => [
        'url'     => '/ecollect/validate/hdfc/ecms',
        'method'  => 'post',
        'content' => [
            'Virtual_Account_No'=> 'HB45898041727816',
            'Remitter_Name'=> 'Ritesh',
            'Remitter_Account_No'=> '9876543210',
            'Remitter_IFSC'=> 'RZPRAZORPAY',
            'Remitter_Bank_Name'=>'ANDHRA BANK',
            'Remitting_Bank_Branch'=>'MUMBAI',
            'Client_Code'=> 'Client',
            'Type'=> 'NEFT',
            'Reference_No'=> '10000000092',
            'Bene_Name'=> 'TPSL',
            'Transaction_Date'=> '01-Jan-2020',
            'Amount'=> 11000,
            'Transaction_Description'=> 'NEFT payment of 4 rupees',
            'UniqueID'=>'02081900017',
            'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
            'Debit_Credit'=>'C',
            'Cheque_No'=>'',
            'Account_Number'=>'02400922022726'
        ],
    ],
    'response' => [
        'content' => [
            'Status' => 0,
            'Reason' => 'Success',
        ],
        'status_code' => 200,
    ]
],

    'testHdfcEcmsBankTransferCallbackPartialPaymentExceedsOrderAmount' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 11000,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 2,
                'Reason' => 'Payment already done for this order.',
            ],
            'status_code' => 200,
        ],
    ],

    'testHdfcEcmsBankTransferCallbackPartialPaymentExceedsThresholdAmount' => [
        'request' => [
            'url'     => '/ecollect/validate/hdfc/ecms',
            'method'  => 'post',
            'content' => [
                'Virtual_Account_No'=> 'HB45898041727816',
                'Remitter_Name'=> 'Ritesh',
                'Remitter_Account_No'=> '9876543210',
                'Remitter_IFSC'=> 'RZPRAZORPAY',
                'Remitter_Bank_Name'=>'ANDHRA BANK',
                'Remitting_Bank_Branch'=>'MUMBAI',
                'Client_Code'=> 'Client',
                'Type'=> 'NEFT',
                'Reference_No'=> '10000000092',
                'Bene_Name'=> 'TPSL',
                'Transaction_Date'=> '01-Jan-2020',
                'Amount'=> 11000,
                'Transaction_Description'=> 'NEFT payment of 4 rupees',
                'UniqueID'=>'02081900017',
                'UserID'=>'qBD0eNLhMAca0ihiUfG8hw==',
                'Debit_Credit'=>'C',
                'Cheque_No'=>'',
                'Account_Number'=>'02400922022726'
            ],
        ],
        'response' => [
            'content' => [
                'Status' => 2,
                'Reason' => 'Amount exceeds total payment limit threshold',
            ],
            'status_code' => 200,
        ],
    ],

    'testBankTransferRblIft' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'IMPS',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => '006713070094-IFT Payment',
                        'senderIFSC'                => '',
                        'senderAccountNumber'       => '',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'SBI294559d909324c4b9d29b930a39d27dd',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '00010469876543210',
                        'creditDate'                => '13-10-2016 1929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testBankTransferRblUpi' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'IMPS',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'UPI/006713070094/UPI/BALJEETKUMA@OKSBI',
                        'senderIFSC'                => '',
                        'senderAccountNumber'       => '',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'SBI294559d909324c4b9d29b930a39d27dd',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '00010469876543210',
                        'creditDate'                => '13-10-2016 1929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testBankTransferRblWithDuplicateUtr' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'N',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'CMS480098890',
                        'senderIFSC'                => 'ICIC0000104',
                        'senderAccountNumber'       => '010405000010',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '00010469876543210',
                        'creditDate'                => '13-10-2016 091929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ]
    ],

    'testBankTransferRblWithInvalidData' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'N',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => '',
                        'senderIFSC'                => 'ICIC0000104',
                        'senderAccountNumber'       => '010405000010',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => 'VACAFLT02405',
                        'creditDate'                => '13-10-2016 091929',
                        'creditAccountNumber'       => '',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Failure.',
            ],
            'status_code' => 400,
        ]
    ],

    'testBankTransferRblWithMissingHeader' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'N',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'CMS480098890',
                        'senderIFSC'                => 'ICIC0000104',
                        'senderAccountNumber'       => '010405000010',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '',
                        'creditDate'                => '13-10-2016 091929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Failure Invalid token.',
            ],
            'status_code' => 400,
        ]
    ],

    'testBankTransferRblWithInternalServerError' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'N',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'CMS480098890',
                        'senderIFSC'                => 'ICIC0000104',
                        'senderAccountNumber'       => '010405000010',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => 'VACAFLT02405',
                        'creditDate'                => '13-10-2016 091929',
                        'creditAccountNumber'       => '',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ]
    ],

    'testBankTransferRblWithEmptyFields' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'imps',
                        'amount'                    => '3439.46',
                        'UTRNumber'                 => 'UPI/ /UPI/BALJEETKUMA@OKSBI',
                        'senderIFSC'                => '',
                        'senderAccountNumber'       => '',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'CREDIT CARD OPERATIONS',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '00010469876543210',
                        'creditDate'                => '13-10-2016 1929',
                        'creditAccountNumber'       => '409000404030',
                        'corporateCode'             => 'CAFLT',
                        'clientCodeMaster'          => '02405',
                        'senderInformation'         => 'MID 74256975 ICICI PYT 121016',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Failure.',
            ],
            'status_code' => 400,
        ]
    ],

    'testBankTransferSpecialCharsInAccNumber' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '123-123-123',
            'payer_ifsc'     => 'XYZ9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid account number',
        ],
    ],

    'testBankTransferStripPayerBankAccount' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '00000000000123456',
            'payer_ifsc'     => 'ABC9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with leading zeroes',
        ],
    ],

    'testBankTransferImpsUnmappedBankCode' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'XYZ9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid bank code',
        ],
    ],

    'testBankTransferRefundRetry' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'XYZ9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid bank code',
        ],
    ],

    'testBankTransferRemoveSpaces' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => 'R Z R P A Y 1 2 3',
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'XYZ9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with a stupid bank code',
        ],
    ],

    'testBankTransferImpsFromRogueBankNullAccount' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '',
            'payer_ifsc'     => '',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with no account number',
        ],
    ],

    'testBankTransferImpsFromRogueBankInvalidAccount' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => '533/1 NEFT CASH FOR NON CUSTOMER',
            'payer_account'  => '123',
            'payer_ifsc'     => 'PJSB0000003',
            'mode'           => 'rtgs',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with nonsense account number',
        ],
    ],

    'testBankTransferImpsFromRogueBankStripAccount' => [
        'url'     => '/ecollect/validate',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '00000000000123456',
            'payer_ifsc'     => 'CNB9876543210',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees, with leading zeroes',
        ],
    ],

    'bankTransferImpsFailedRefund' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Refund is currently not supported for this payment method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
        ],
    ],

    'testBankTransferProcessFailure' => [
        'request' => [
            'url' => '/ecollect/validate/test',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'HDFC0000001',
                'mode'           => 'neft',
                'transaction_id' => 'vba_4567',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid' => null,
            ],
        ],
    ],

    'testBankTransferToReallyReallyLongPayeeAccount' => [
        'request' => [
            'url' => '/ecollect/validate/test',
            'method' => 'post',
            'content' => [
                'payee_account'  => '11122200123456781112220012345678',
                'payee_ifsc'     => 'RAZR0000001',
                'payer_ifsc'     => 'HDFC0000001',
                'mode'           => 'neft',
                'transaction_id' => 'vba_4567',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid' => true,
            ],
        ],
    ],

    'testBankTransferProcessDuplicateUtr' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => null,
            'mode'           => 'imps',
            'transaction_id' => null,
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'testBankTransferProcessWithExtraFields' => [
        'request' => [
            'url' => '/ecollect/validate/test',
            'method' => 'post',
            'content' => [
                'payee_account'      => null,
                'payee_ifsc'         => 'RAZR0000001',
                'payee_name'         => 'Razorpay',
                'payer_name'         => 'Name of account holder',
                'payer_account'      => '9876543210123456789',
                'payer_account_type' => 'ca',
                'payer_ifsc'         => 'HDFC0000001',
                'payer_address'      => 'Address of payer',
                'mode'               => 'imps',
                'transaction_id'     => 'HDFC148415544000000000',
                'time'               => 148415544000,
                'amount'             => 50000,
                'currency'           => 'INR',
                'description'        => 'NEFT payment of 50,000 rupees with extra fields',
                'attempt'            => 1,
            ],
        ],
        'response' => [
            'content' => [
                'valid' => true,
            ],
        ],
    ],

    'testBankTransferProcessWithFieldsOnTestMode' => [
        'request' => [
            'url' => '/ecollect/validate/test',
            'method' => 'post',
            'content' => [
                'payee_account'           => null,
                'payee_ifsc'              => 'RAZR0000001',
                'payee_name'              => 'Razorpay',
                'payer_name'              => 'Name of account holder',
                'payer_account'           => '9876543210123456789',
                'payer_account_type'      => 'ca',
                'payer_ifsc'              => 'HDFC0000001',
                'payer_address'           => 'Address of payer',
                'mode'                    => 'imps',
                'transaction_id'          => 'HDFC148415544000000000',
                'time'                    => 148415544000,
                'amount'                  => 50000,
                'currency'                => 'INR',
                'description'             => 'NEFT payment of 50,000 rupees with extra fields',
                'attempt'                 => 1,
            ],
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'valid' => true,
            ],
        ],
    ],

    'testBankTransferProcessWithFieldsOnLiveMode' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'           => null,
                'payee_ifsc'              => 'RAZR0000001',
                'payee_name'              => 'Razorpay',
                'payer_name'              => 'Name of account holder',
                'payer_account'           => '9876543210123456789',
                'payer_account_type'      => 'ca',
                'payer_ifsc'              => 'HDFC0000001',
                'payer_address'           => 'Address of payer',
                'mode'                    => 'imps',
                'transaction_id'          => 'HDFC148415544000000000',
                'time'                    => 148415544000,
                'amount'                  => 50000,
                'currency'                => 'INR',
                'description'             => 'NEFT payment of 50,000 rupees with extra fields',
                'attempt'                 => 1,
            ],
        ],
        'response' => [
            'content' => [
                'valid' => true,
            ],
        ],
    ],

    'testBankTransferProcessWithIncorrectPayeeAccountLength' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => '1234',
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '9876543210123456789',
                'payer_ifsc'     => 'YESB0000022',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => false,
                'message' => TraceCode::BANK_TRANSFER_REQUEST_ICICI_PAYEE_ACCOUNT_NUMBER_WITH_INVALID_LENGTH,
            ],
        ],
    ],

    'testPendingBankTransfer' => [
        'request'  => [
            'url'     => '/admin/process_pending_bank_transfer',
            'method'  => 'post',
            'content' => [
                'bank_transfer_request_id'  => null,
            ],
        ],
        'response' => [
            'content' => [
                'valid'          => true,
                'transaction_id' => 'RANDOMUTR012345',
            ],
            'status_code' => 200,
        ],
    ],

    'testPendingBankTransferWithInvalidID' => [
        'request'  => [
            'url'     => '/admin/process_pending_bank_transfer',
            'method'  => 'post',
            'content' => [
                'bank_transfer_request_id'  => null,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testBankTransferNotifyNonFailure' => [
        'request' => [
            'url' => '/ecollect/pay',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payer_ifsc'     => 'IFSC0009876',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'HDFC0000001',
                'mode'           => 'neft',
                'transaction_id' => 'vba_1234',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testBankTransferPublicAuth' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid payment method given: bank_transfer',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFileCreationRefund' => [
        'amount'            => 4000000,
        'fees'              => 0,
        'tax'               => 0,
        'processed_amount'  => 0,
        'processed_count'   => 0,
        'total_count'       => 1,
        'type'              => 'refund',
    ],

    'createTpvRefund' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'SBIN',
                'account_number' => '04030403040304',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'tpvPaymentNetbankingEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank_name'       => 'SBIN',
        'status'          => 'Ok',
    ],

    'testBankTransferProcessWithPayerBankAccountOf4Chars' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => 'UDAN',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => 'utr_thisisbestutr',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'testBankTransferIcici' => [
        'url'     => '/ecollect/validate/icici/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'ICIC0000104',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testBankTransferIciciMigration' => [
        'url'     => '/ecollect/validate/icici/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'ICIC0000104',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testFetchPaymentsPostRblMigration' => [
        'url'     => '/ecollect/validate/icici/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'ICIC0000104',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testBankTransferIciciWithInvalidPrefixForPayerAccount' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => '3434123412341234',
                'payee_ifsc'     => 'ICIC0000104',
                'payer_name'     => 'Name of account holder',
                'payer_account'  => 'INHSBC073-523524-001',
                'payer_ifsc'     => 'HSBC0560002',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'transaction_id' => 'RANDOMUTR012345',
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => true,
            ],
        ],
    ],

    'testBankTransferIciciWithIfscAsBankCode' => [
        'url'     => '/ecollect/validate/icici/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'SBIN',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 100,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testBankTransferIciciWithIfscAsInvalidBankCode' => [
        'url'     => '/ecollect/validate/icici/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'SBI',
            'mode'           => 'imps',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 100,
            'description'    => 'IMPS payment of 50,000 rupees',
        ],
    ],

    'testCheckEcollectIciciBatchCreate' => [
        'request'  => [
            'url'     => '/ecollect/validate/file',
            'method'  => 'post',
            'content' => [
                'source' => 'lambda',
                'key'    => 'icici/filename.xlsx',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testRblFallbackTerminal' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'ft',
                        'amount'                    => '1000',
                        'UTRNumber'                 => 'RANDOM0UTR0',
                        'senderIFSC'                => 'CNRB0008652',
                        'senderAccountNumber'       => '999988887777',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'Rzrpy',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => '1112228866442200',
                        'creditDate'                => '14-02-2020 201500',
                        'creditAccountNumber'       => '112233445566',
                        'corporateCode'             => '',
                        'clientCodeMaster'          => '',
                        'senderInformation'         => 'Something something',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ],
    ],

    'testRblBankTransferWithNoMatchingTerminal' => [
        'request' => [
            'url'     => '/ecollect/validate/rbl/test',
            'method'  => 'post',
            'server'  => [
                'HTTP_XorgToken'   => 'RANDOM_RBL_SECRET',
            ],
            'content' => [
                'ServiceName' => 'VirtualAccount',
                'Action' => 'VirtualAccountTransaction',
                'Data' =>  [
                    [
                        'messageType'               => 'ft',
                        'amount'                    => '1000',
                        'UTRNumber'                 => 'RANDOM0UTR0',
                        'senderIFSC'                => 'CNRB0008652',
                        'senderAccountNumber'       => '999988887777',
                        'senderAccountType'         => 'Current Account',
                        'senderName'                => 'Rzrpy',
                        'beneficiaryAccountType'    => 'Current Account',
                        'beneficiaryAccountNumber'  => 'AAABBB8866442200',
                        'creditDate'                => '14-02-2020 201500',
                        'creditAccountNumber'       => '112233445566',
                        'corporateCode'             => '',
                        'clientCodeMaster'          => '',
                        'senderInformation'         => 'Something something',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'Status'    => 'Success',
            ],
            'status_code' => 200,
        ],
    ],

    'testEcollectRblBatchCreate' => [
        'request' => [
            'url' => '/ecollect/validate/file/rbl',
            'method' => 'post',
            'content' => [
                'source' => 'lambda',
                'key' => 'BtRbl/filename.xlsx',
            ],
        ],
        'response' => [
            'content' => [
                'type' => 'ecollect_rbl',
                'status' => 'created',
            ],
            'status_code' => 200,
        ],
    ],

    'ecollectRblBatchData' => [
        [
            'TRANSACTION_TYPE'                  => 'IMPS',
            'AMOUNT'                            => '100',
            'UTR NUMBER'                        => null,
            'RRN_NUMBER'                        => 'IMPS 12345ABCDE01 FROM BHARATPE',
            'SENDER_IFSC'                       => 'SBIN0000002',
            'SENDER_ACCOUNT_NUMBER'             => '999988887777',
            'SENDER_ACCOUNT_TYPE'               => 'Current Account',
            'SENDER_NAME'                       => 'BharatPe',
            'BENEFICIARY_ACCOUNT_TYPE'          => 'Current Account',
            'BENEFICIARY_ACCOUNT_NUMBER'        => '2223330005148068',
            'BENENAME'                          => null,
            'CREDIT_DATE'                       => '14-02-2020 201500',
            'CREDIT_ACCOUNT_NUMBER'             => '409000694314',
            'CORPORATE_CODE'                    => null,
            'SENDER_INFORMATION'                => null,
        ]
    ],

    'testEcollectYesbankBatchCreate' => [
        'request'  => [
            'url'     => '/ecollect/validate/file/yesbank',
            'method'  => 'post',
            'content' => [
                'source' => 'lambda',
                'key'    => 'yesbank/filename.xlsx',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testEcollectYesbankBatchCreateDuplicate' => [
        'url'     => '/ecollect/validate/yesbank/internal',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => strtoupper(random_alphanum_string(22)),
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'ecollectYesbankBatchData' => [
        [
            'CUST CODE'          => '787878',
            'REMITTER CODE'      => '0060604653',
            'CUSTOMER SUBCODE'   => null,
            'INVOICE NO'         => null,
            'BENE ACCOUNT NO'    => '7878780060604653',
            'AMOUNT'             => '235647.89',
            'RMTR ACCOUNT NO'    => '917020041206002',
            'RMTR ACCOUNT IFSC'  => 'UTIB0001506',
            'TRANSACTION REF NO' => 'UTIB202202175000338730',
            'TRANS RECEIVED AT'  => '2/17/2022 5:12:27 PM',
            'TRANS STATUS'       => 'CREDITED',
            'VALIDATION STATUS'  => 'VALIDATED: OK',
            'TRANSFER TYPE'      => 'RTGS',
            'CREDIT REF'         => '00136990540',
            'NOTIFY STATUS'      => 'NOTIFIED: OK',
            'NOTIFY RESULT'      => null,
            'RETURN REF'         => null,
            'RETURNED AT'        => null,
            'RMTR FULL NAME'     => 'RAZORPAY SOFTWARE PRIVATE LIMITED -',
            'RMTR ADD'           => 'MUNICIPAL NO.22 LASKAR HOSUR ROAD.AFTER FARUMMALLOPPOSITE TATA DOCOMO,',
            'UDF11'              => null,
            'UDF12'              => null,
            'UDF13'              => null,
            'UDF14'              => null
        ]
    ],

    'testProcessBankTransferInvalidPayerIfsc' => [
        'url'     => '/ecollect/validate/test',
        'method'  => 'post',
        'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'UTIB0000002',
            'mode'           => 'neft',
            'transaction_id' => 'utr_thisisbestutr',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'cancelInvoice' => [
        'url'     => '/invoices/inv_1000000invoice/cancel',
        'method'  => 'post',
        'content' => [],
    ],

    'createVAWithAllowedPayer' => [
        'content' => [
            'receivers'      => [
                'types' => [
                    'bank_account'
                ]
            ],
            'allowed_payers' => [
                [
                    'type'         => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'HDFC0000053',
                        'account_number' => '765432123456789'
                    ]
                ],
                [
                    'type'         => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'UTIB0000013',
                        'account_number' => '000123499988'
                    ],
                ],
            ],
        ],
    ],

    'bankTransferValidateTpv' => [
        'request'  => [
            'url'     => '/ecollect/validate/test',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '765432123456789',
                'payer_ifsc'     => 'HDFC0000053',
                'mode'           => 'neft',
                'transaction_id' => strtoupper(random_alphanum_string(22)),
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => true,
                'message' => null,
            ],
        ],
    ],

    'testWebhookVirtualAccountCreditedWithAllowedPayer' => [
        'event' => [
            'entity' => 'event',
            'event' => 'virtual_account.credited',
            'contains' => [
                'payment',
                'virtual_account',
                'bank_transfer',
            ],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 5000000,
                        'currency'          => 'INR',
                        'base_amount'       => 5000000,
                        'status'            => 'captured',
                        'order_id'          => null,
                        'invoice_id'        => null,
                        'international'     => false,
                        'method'            => 'bank_transfer',
                        'amount_refunded'   => 0,
                        'amount_transferred'=> 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => 'NEFT payment of 50,000 rupees',
                        'vpa'               => null,
                        'email'             => null,
                        'contact'           => null,
                        'fee'               => 5900,
                        'tax'               => 900,
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
                'virtual_account' => [
                    'entity' => [
                        'name'            => 'Test Merchant',
                        'entity'          => 'virtual_account',
                        'status'          => 'active',
                        'amount_expected' => null,
                        'amount_paid' => 5000000,
                        'customer_id' => null,
                        'allowed_payers' => [
                            [
                                'type'         => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'HDFC0000053',
                                    'account_number' => '765432123456789'
                                ],
                            ],
                            [
                                'type'         => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'UTIB0000013',
                                    'account_number' => '000123499988'
                                ],
                            ],
                        ],
                        'close_by' => null,
                        'closed_at' => null,
                        'receivers' => [
                            [
                                'entity'    => 'bank_account',
                                'ifsc'      => 'RAZR0000001',
                                'name'      => 'Test Merchant',
                            ],
                        ],
                    ],
                ],
                'bank_transfer' => [
                    'entity' => [
                        'entity'             => 'bank_transfer',
                        'mode'               => 'NEFT',
                        'amount'             => 5000000,
                        'payer_bank_account' => [
                            'entity' => 'bank_account',
                            'ifsc' => 'HDFC0000053',
                            'account_number' => '765432123456789'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testWebhookRefundProcessedForTpvFailure' => [
        'event' => [
            'entity'   => 'event',
            'event'    => 'refund.processed',
            'contains' => [
                'refund',
            ],
            'payload'  => [
                'refund' => [
                    'entity' => [
                        'entity'          => 'refund',
                        'amount'          => 5000000,
                        'currency'        => 'INR',
                        'notes'           => [
                            'refund_reason' => 'Bank Account Validation Failed'
                        ],
                        'receipt'         => null,
                        'status'          => 'processed',
                        'speed_requested' => 'normal',
                        'speed_processed' => 'normal',
                        'acquirer_data'   => [],
                    ],
                ],
            ],
        ],
    ],

    'testBankTransferImpsWithNbinValidateTpv' => [
        'request'  => [
            'url'     => '/ecollect/validate',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '765432123456789',
                'payer_ifsc'     => '9240',
                'mode'           => 'imps',
                'transaction_id' => strtoupper(random_alphanum_string(22)),
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => true,
                'message' => null,
            ],
        ],
    ],

    'adminFetchBankTransferRequest' => [
        'request' => [
            'url'       => '/admin/bank_transfer_request/',
            'method'    => 'get',
            'content'   => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFound' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '9876543210123456789',
                'payer_ifsc'     => 'YESB0000022',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testBankTransferIciciIMPSForRazorpayXWherePayeeAccountNumberDoesNotExist' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '9876543210123456789',
                'payer_ifsc'     => 'YESB0000022',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testBankTransferIciciIMPSForRazorpayXWherePayeeAccountNumberDoesNotExistAndRefundsViaX' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '9876543210123456789',
                'payer_ifsc'     => 'YESB0000022',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testBankTransferIciciIMPSForRazorpayXWithTpvEnabledButNoTpvAccountFoundAndRefundsViaX' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '9876543210123456789',
                'payer_ifsc'     => 'YESB0000022',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testAdminTestBankTransferPayment' => [
            'url' => '/ecollect/validate/test',
            'method' => 'post',
            'content' => [
                'payee_account'  => 'RZP1234567890',
                'payer_account'  => '765432346787812',
                'payer_ifsc'     => 'HDFC0000001',
                'mode'           => 'neft',
                'transaction_id' => 'vba_4567',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
    ],

    'processBankTransferProcessWithDifferentAmount' => [
        'url' => '/ecollect/validate/test',
        'method' => 'post',
        'content' => [
            'payee_account'  => 'RZP1234567890',
            'payer_account'  => '765432346787812',
            'payer_ifsc'     => 'HDFC0000001',
            'mode'           => 'neft',
            'transaction_id' => 'vba_4567',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'NEFT payment of 50,000 rupees',
        ],
    ],

    'testBankTransferToVirtualAccountMerchantNotLiveVa' => [
        'request'  => [
        'url'     => '/ecollect/validate/icici/internal',
        'method'  => 'post',
            'content' => [
            'payee_account'  => null,
            'payee_ifsc'     => null,
            'payer_name'     => 'Name of account holder',
            'payer_account'  => '9876543210123456789',
            'payer_ifsc'     => 'YESB0000022',
            'mode'           => 'IMPS',
            'time'           => 148415544000,
            'amount'         => 50000,
            'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response'  => [
            'content' => []
        ]
    ],

    'testBankTransferToVirtualAccountMerchantNotLiveOnPgButLiveOnX' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '9876543210123456789',
                'payer_ifsc'     => 'YESB0000022',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response'  => [
            'content' => [],
        ],
    ],

    'testBankTransferIciciWherePayerAccountIsExtractedFromPayerName' => [
        'request'  => [
            'url'     => '/ecollect/validate/icici/internal',
            'method'  => 'post',
            'content' => [
                'payee_account'  => '3434123412341234',
                'payee_ifsc'     => 'ICIC0000104',
                'payer_name'     => 'HSBC 073-560123-123 Name of account holder',
                'payer_account'  => 'IN',
                'payer_ifsc'     => 'HSBC0560002',
                'mode'           => 'IMPS',
                'time'           => 148415544000,
                'transaction_id' => 'RANDOMUTR012345',
                'amount'         => 50000,
                'description'    => 'IMPS payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => true,
            ],
        ],
    ],

    'testBankTransferYesBankWhenPayerAccountContainsPayerNameForPJSB' => [
        'request'  => [
            'url'     => '/ecollect/validate',
            'method'  => 'post',
            'content' => [
                'payee_account'  => '3434123412341234',
                'payee_ifsc'     => 'YESB0CMSNOC',
                'payer_name'     => 'John Doe',
                'payer_account'  => '123456543217890John Doe',
                'payer_ifsc'     => 'PJSB0000055',
                'mode'           => 'NEFT',
                'time'           => 148415544000,
                'transaction_id' => 'RANDOMUTR012345',
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [
                'valid'   => true,
            ],
        ],
    ],

    'testBankTransferProcessWithBeneficiaryNameOfLengthOne' => [
        'request' => [
            'url' => '/ecollect/validate',
            'method' => 'post',
            'content' => [
                'payee_account'           => null,
                'payee_ifsc'              => 'RAZR0000001',
                'payee_name'              => 'Razorpay',
                'payer_name'              => 'i',
                'payer_account'           => '9876543210123456789',
                'payer_account_type'      => 'ca',
                'payer_ifsc'              => 'HDFC0000001',
                'payer_address'           => 'Address of payer',
                'mode'                    => 'imps',
                'transaction_id'          => 'HDFC148415544000000000',
                'time'                    => 148415544000,
                'amount'                  => 50000,
                'currency'                => 'INR',
                'description'             => 'NEFT payment of 50,000 rupees with extra fields',
                'attempt'                 => 1,
            ],
        ],
        'response' => [
            'content' => [
                'valid' => true,
            ],
        ],
    ],

    'testBankTransferRblViaScService' => [
        'request'  => [
            'url'     => '/ecollect/validate/internal',
            'method'  => 'post',
            'headers' => [
                'Route-Name' => 'bank_transfer_process_rbl_test'
            ],
            'content' => [
                'gateway'         => 'rbl',
                'data'            => [
                    'payee_account'  => '0001046002505396',
                    'payee_ifsc'     => 'RATN0VAAPIS',
                    'payer_name'     => 'CREDIT CARD OPERATIONS',
                    'payer_account'  => '010405000010',
                    'payer_ifsc'     => 'ICIC0000104',
                    'mode'           => 'ift',
                    'transaction_id' => 'CMS480098890',
                    'time'           => 1655961550,
                    'amount'         => '3439.46',
                    'description'    => 'MID 74256975 ICICI PYT 121016',
                    'narration'      => 'CMS480098890'
                ],
                'request_payload' => [
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
                ]
            ],
        ],
        'response' => [
            'content' => [
                'valid' => true,
            ],
        ],
    ]
];
