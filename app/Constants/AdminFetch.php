<?php

namespace RZP\Constants;

use RZP\Base\Fetch;

/**
 * Class AdminFetch
 *
 * Its a temporary class to server Fetch Entities API.
 * We will be removing this by overriding each entity one by one.
 * Class only serves fields, validation is handled by Repository.
 */
class AdminFetch
{
    public static function fields()
    {
        return Fetch::getCommonFields();
    }

    public static function entities()
    {
        return [
            Entity::ADJUSTMENT => [
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::AMEX => [
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt Number',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::APP_TOKEN => [
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'device_token' => [
                    Fetch::LABEL  => 'Device Token',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::AXIS_GENIUS => [
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt Number',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::AXIS_MIGS => [
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt No',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'vpc_ShopTransactionNo' => [
                    Fetch::LABEL  => 'Shop Transaction No',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'vpc_TransactionNo' => [
                    Fetch::LABEL  => 'Transaction No',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'vpc_TxnResponseCode' => [
                    Fetch::LABEL  => 'Txn Response Code',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'vpc_3DSstatus' => [
                    Fetch::LABEL  => 'Vpc 3DSstatus',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'Y',
                        'N',
                        'U',
                        'A',
                    ],
                ],
            ],

            Entity::BANK_ACCOUNT => [
                'deleted' => [
                    Fetch::LABEL  => 'Deleted',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'customer',
                        'merchant',
                    ],
                ],
            ],

            Entity::BANK_TRANSFER => [
                'merchant_id' => Fields::MERCHANT_ID,
                'payment_id' => Fields::PAYMENT_ID,
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'virtual_account_id' => [
                    Fetch::LABEL  => 'Virtual Account ID',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'mode' => [
                    Fetch::LABEL  => 'Mode',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'neft',
                        'rtgs',
                        'ift',
                        'imps',
                    ],
                ],
                'payer_account' => [
                    Fetch::LABEL  => 'Payer Account',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'payer_ifsc' => [
                    Fetch::LABEL  => 'Payer IFSC',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'payee_account' => [
                    Fetch::LABEL  => 'Payee Account',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'payee_ifsc' => [
                    Fetch::LABEL  => 'Payee IFSC',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::BATCH => [
                'merchant_id' => Fields::MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'processing',
                        'processed',
                    ],
                ],
            ],

            Entity::BATCH_FUND_TRANSFER => [
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'settlement',
                        'payout',
                    ],
                ],
                'date' => [
                    Fetch::LABEL  => 'Date',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::BILLDESK => [
                'AuthStatus' => [
                    Fetch::LABEL  => 'AuthStatus',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        '0001',
                        '0300',
                        '0002',
                        '0399',
                        'NA',
                    ],
                ],
                'BankReferenceNo' => [
                    Fetch::LABEL  => 'Bank Reference No',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'RefStatus' => [
                    Fetch::LABEL  => 'Refund Status',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'RefundId' => [
                    Fetch::LABEL  => 'Billdesk Refund Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'TxnReferenceNo' => [
                    Fetch::LABEL  => 'Txn Reference No',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::CARD => [
                'global_card_id' => [
                    Fetch::LABEL  => 'Global Card Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'iin' => [
                    Fetch::LABEL  => 'IIN',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'last4' => [
                    Fetch::LABEL  => 'Last4',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'Visa',
                        'MasterCard',
                        'Maestro',
                        'Diners Club',
                        'American Express',
                        'RuPay',
                        'Unknown',
                        'Discover',
                    ],
                ],
                'status' => Fields::PAYMENT_STATUS,
                'vault' => [
                    Fetch::LABEL  => 'Vault',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'vault_token' => [
                    Fetch::LABEL  => 'Vault Token',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::CREDITS => [
                'merchant_id' => Fields::MERCHANT_ID,
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'fee',
                        'amount',
                    ],
                ],
            ],

            Entity::CUSTOMER => [
                'merchant_id' => Fields::MERCHANT_ID,
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'active' => [
                    Fetch::LABEL  => 'Active',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'contact' => [
                    Fetch::LABEL  => 'Contact',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::CUSTOMER_BALANCE => [
                'merchant_id' => Fields::MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::CUSTOMER_TRANSACTION => [
                'entity_id' => [
                    Fetch::LABEL  => 'Payment/Refund Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'transfer',
                        'refund',
                    ],
                ],
            ],

            Entity::CYBERSOURCE => [
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'ref' => [
                    Fetch::LABEL  => 'Reference',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'capture_ref' => [
                    Fetch::LABEL  => 'Capture Reference',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::EBS => [
                'payment_id' => Fields::PAYMENT_ID,
            ],

            Entity::FEE_BREAKUP => [
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'pricing_rule_id' => [
                    Fetch::LABEL  => 'Pricing Rule Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::FIRST_DATA => [
                'payment_id' => Fields::PAYMENT_ID,
                'action' => [
                    Fetch::LABEL  => 'Action',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'refund_id' => [
                    Fetch::LABEL  => 'Refund ID',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'gateway_payment_id' => Fields::PAYMENT_ID,
                'tdate' => [
                    Fetch::LABEL  => 'Tdate',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'caps_payment_id' => Fields::PAYMENT_ID,
                'gateway_transaction_id' => [
                    Fetch::LABEL  => 'Gateway Transaction ID',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::DISPUTE => [
                'merchant_id' => Fields::MERCHANT_ID,
                'payment_id' => Fields::PAYMENT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'open',
                        'under_review',
                        'won',
                        'lost',
                    ],
                ],
                'phase' => [
                    Fetch::LABEL  => 'Phase',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'chargeback',
                        'pre_arbitration',
                        'arbitration',
                        'retrieval',
                    ],
                ],
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::EMI_PLAN => [
                'bank' => [
                    Fetch::LABEL  => 'Bank',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::FEATURE => [
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'entity_type' => [
                    Fetch::LABEL  => 'Entity Type',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'name' => [
                    Fetch::LABEL  => 'Name',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::FILE_STORE => [
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::FUND_TRANSFER_ATTEMPT => [
                'batch_fund_transfer_id' => [
                    Fetch::LABEL  => 'Batch Fund Transfer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'source_type' => [
                    Fetch::LABEL  => 'Source Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'settlement',
                        'payout',
                    ],
                ],
                'source_id' => [
                    Fetch::LABEL  => 'Source Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'processed',
                    ],
                ],
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::GATEWAY_DOWNTIME => [
                'method' => Fields::METHOD,
                'gateway' => Fields::GATEWAY,
                'bank' => [
                    Fetch::LABEL  => 'Bank',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::HDFC => [
                'auth' => [
                    Fetch::LABEL  => 'Auth Code',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'gateway_transaction_id' => [
                    Fetch::LABEL  => 'Gateway Transaction Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'payment_id' => Fields::PAYMENT_ID,
                'refund_id' => [
                    Fetch::LABEL  => 'Refund Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'ref' => [
                    Fetch::LABEL  => 'Reference',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::IIN => [
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'otp_read' => [
                    Fetch::LABEL  => 'Otp Read',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'iin' => [
                    Fetch::LABEL  => 'Iin',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'issuer' => [
                    Fetch::LABEL  => 'Issuer',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'credit',
                        'debit',
                        'unknown',
                    ],
                ],
            ],

            Entity::INVOICE => [
                'payment_id' => Fields::PAYMENT_ID,
                'receipt' => [
                    Fetch::LABEL  => 'Receipt',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'user_id' => [
                    Fetch::LABEL  => 'User Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'draft',
                        'issued',
                        'paid',
                        'cancelled',
                        'expired',
                    ],
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'ecod',
                        'link',
                        'invoice',
                    ],
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'order_id' => [
                    Fetch::LABEL  => 'Order Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'customer_name' => [
                    Fetch::LABEL  => 'Customer Name',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'customer_email' => [
                    Fetch::LABEL  => 'Customer Email',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'customer_contact' => [
                    Fetch::LABEL  => 'Customer Contact',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::KEY => [
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::MERCHANT => [
                'activated' => [
                    Fetch::LABEL  => 'Activated',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'amex' => [
                    Fetch::LABEL  => 'Amex',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'card' => [
                    Fetch::LABEL  => 'Card',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'category' => [
                    Fetch::LABEL  => 'MCC Code',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'category2' => [
                    Fetch::LABEL  => 'Category 2',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'hold_funds' => [
                    Fetch::LABEL  => 'Hold Funds',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'live' => [
                    Fetch::LABEL  => 'Live',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'mobikwik' => [
                    Fetch::LABEL  => 'Mobikwik',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'paytm' => [
                    Fetch::LABEL  => 'Paytm',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'payumoney' => [
                    Fetch::LABEL  => 'Payumoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'payzapp' => [
                    Fetch::LABEL  => 'Payzapp',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'olamoney' => [
                    Fetch::LABEL  => 'Olamoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'mpesa' => [
                    Fetch::LABEL  => 'Mpesa',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'upi' => [
                    Fetch::LABEL  => 'Upi',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'airtelmoney' => [
                    Fetch::LABEL  => 'Airtelmoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'freecharge' => [
                    Fetch::LABEL  => 'Freecharge',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'jiomoney' => [
                    Fetch::LABEL  => 'Jiomoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'sbibuddy' => [
                    Fetch::LABEL  => 'Sbibuddy',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'pricing_plan_id' => [
                    Fetch::LABEL  => 'Pricing Plan Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'parent_id' => [
                    Fetch::LABEL  => 'Marketplace Parent Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'receipt_email_enabled' => [
                    Fetch::LABEL  => 'Receipt Email_enabled',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'fee_bearer' => [
                    Fetch::LABEL  => 'Fee Bearer',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'platform',
                        'customer',
                    ],
                ],
                'fee_model' => [
                    Fetch::LABEL  => 'Fee Model',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'prepaid',
                        'postpaid',
                    ],
                ],
                'risk_rating' => [
                    Fetch::LABEL  => 'Risk Rating',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        '1',
                        '2',
                        '3',
                        '4',
                        '5',
                    ],
                ],
            ],

            Entity::METHODS => [
                'amex' => [
                    Fetch::LABEL  => 'Amex',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'card' => [
                    Fetch::LABEL  => 'Card',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'mobikwik' => [
                    Fetch::LABEL  => 'Mobikwik',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'paytm' => [
                    Fetch::LABEL  => 'Paytm',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'payumoney' => [
                    Fetch::LABEL  => 'Payumoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'payzapp' => [
                    Fetch::LABEL  => 'Payzapp',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'mpesa' => [
                    Fetch::LABEL  => 'Mpesa',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'olamoney' => [
                    Fetch::LABEL  => 'Olamoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'upi' => [
                    Fetch::LABEL  => 'Upi',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'airtelmoney' => [
                    Fetch::LABEL  => 'Airtelmoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'freecharge' => [
                    Fetch::LABEL  => 'Freecharge',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'jiomoney' => [
                    Fetch::LABEL  => 'Jiomoney',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'sbibuddy' => [
                    Fetch::LABEL  => 'Sbibuddy',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::MERCHANT_INVOICE => [
                'merchant_id' => Fields::MERCHANT_ID,
                'invoice_number' => [
                    Fetch::LABEL  => 'Invoice No.',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'gstin' => [
                    Fetch::LABEL  => 'GSTIN',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'month' => [
                    Fetch::LABEL  => 'Month',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'year' => [
                    Fetch::LABEL  => 'Year',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::MOBIKWIK => [
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
            ],

            Entity::NETBANKING => [
                'bank_payment_id' => Fields::PAYMENT_ID,
                'caps_payment_id' => Fields::PAYMENT_ID,
                'int_payment_id' => Fields::PAYMENT_ID,
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
            ],

            Entity::OFFER => [
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::ORDER => [
                'account_number' => [
                    Fetch::LABEL  => 'Account Number',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'authorized' => [
                    Fetch::LABEL  => 'Authorized',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'receipt' => [
                    Fetch::LABEL  => 'Receipt',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'attempted',
                        'paid',
                    ],
                ],
            ],

            Entity::PAYMENT_ANALYTICS => [
                'checkout_id' => [
                    Fetch::LABEL  => 'Checkout Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'payment_id' => Fields::PAYMENT_ID,
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::PAYMENT => [
                'app_token' => [
                    Fetch::LABEL  => 'App Token',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'bank' => [
                    Fetch::LABEL  => 'Bank Code',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'card_id' => [
                    Fetch::LABEL  => 'Card Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'global_customer_id' => [
                    Fetch::LABEL  => 'Global Customer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'email' => [
                    Fetch::LABEL  => 'Contact Email',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'gateway' => Fields::GATEWAY,
                'global_token_id' => [
                    Fetch::LABEL  => 'Global Token Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'iin' => [
                    Fetch::LABEL  => 'Card IIN',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'invoice_id' => [
                    Fetch::LABEL  => 'Invoice Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'last4' => [
                    Fetch::LABEL  => 'Card Last 4',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'method' => Fields::METHOD,
                'notes' => [
                    Fetch::LABEL  => 'Notes',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'order_id' => [
                    Fetch::LABEL  => 'Order Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'refund_status' => [
                    Fetch::LABEL  => 'Refund Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'null',
                        'partial',
                        'full',
                    ],
                ],
                'save' => [
                    Fetch::LABEL  => 'Save',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'status' => Fields::PAYMENT_STATUS,
                'terminal_id' => [
                    Fetch::LABEL  => 'Terminal ID',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'token_id' => [
                    Fetch::LABEL  => 'Token Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'transfer_id' => [
                    Fetch::LABEL  => 'Transfer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'verified' => [
                    Fetch::LABEL  => 'Verified',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'null',
                        '0',
                        '1',
                        '2',
                    ],
                ],
                'wallet' => Fields::WALLET,
            ],

            Entity::PAYOUT => [
                'merchant_id' => Fields::MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'destination' => [
                    Fetch::LABEL  => 'Bank Account Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'method' => [
                    Fetch::LABEL  => 'Method',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'fund_transfer',
                    ],
                ],
            ],

            Entity::PAYTM => [
                'payment_id' => Fields::PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
            ],

            Entity::PRICING => [
                'plan_id' => [
                    Fetch::LABEL  => 'Plan Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::REFUND => [
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'batch_id' => [
                    Fetch::LABEL  => 'Batch Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'gateway' => Fields::GATEWAY,
                'merchant_id' => Fields::MERCHANT_ID,
                'payment_id' => Fields::PAYMENT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'processed',
                    ],
                ],
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::REVERSAL => [
                'merchant_id' => Fields::MERCHANT_ID,
                'transfer_id' => [
                    Fetch::LABEL  => 'Transfer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::RISK => [
                'fraud_type' => [
                    Fetch::LABEL  => 'Fraud Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'suspected',
                        'confirmed',
                    ],
                ],
                'source' => [
                    Fetch::LABEL  => 'Source',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'bank',
                        'gateway',
                        'maxmind',
                        'manual',
                        'internal',
                    ],
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'payment_id' => Fields::PAYMENT_ID,
            ],

            Entity::SETTLEMENT => [
                'batch_fund_transfer_id' => [
                    Fetch::LABEL  => 'Batch Fund Transfer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'processed',
                    ],
                ],
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::SETTLEMENT_DETAILS => [
                'merchant_id' => Fields::MERCHANT_ID,
                'settlement_id' => [
                    Fetch::LABEL  => 'Settlement Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::TERMINAL => [
                'enabled' => [
                    Fetch::LABEL  => 'Enabled',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'gateway' => Fields::GATEWAY,
                'category' => [
                    Fetch::LABEL  => 'Category',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'shared' => [
                    Fetch::LABEL  => 'Shared',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'gateway_merchant_id' => [
                    Fetch::LABEL  => 'Gateway Merchant Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'gateway_terminal_id' => [
                    Fetch::LABEL  => 'Gateway Terminal Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'network_category' => [
                    Fetch::LABEL  => 'Network Category',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'gateway_acquirer' => [
                    Fetch::LABEL  => 'Gateway Acquirer',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'axis',
                        'hdfc',
                        'icic',
                    ],
                ],
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
            ],

            Entity::TRANSACTION => [
                'entity_id' => [
                    Fetch::LABEL  => 'Payment/Refund/Settlement Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
                'reconciled' => [
                    Fetch::LABEL  => 'Reconciled',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'settled' => [
                    Fetch::LABEL  => 'Settled',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'on_hold' => [
                    Fetch::LABEL  => 'On Hold',
                    Fetch::TYPE   => Fetch::BOOLEAN
                ],
                'settlement_id' => [
                    Fetch::LABEL  => 'Settlement Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'payment',
                        'refund',
                        'settlement',
                        'adjustment',
                        'transfer',
                        'reversal',
                        'payout',
                    ],
                ],
            ],

            Entity::TRANSFER => [
                'source' => [
                    Fetch::LABEL  => 'Source Payment/Merchant Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'recipient' => [
                    Fetch::LABEL  => 'Recipient Merchant/Customer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::TOKEN => [
                'bank' => [
                    Fetch::LABEL  => 'Bank Code',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'card_id' => [
                    Fetch::LABEL  => 'Card Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'merchant_id'   => Fields::MERCHANT_ID,
                'method'        => Fields::METHOD,
                'terminal_id' => [
                    Fetch::LABEL  => 'Terminal Id',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'token' => [
                    Fetch::LABEL  => 'Token',
                    Fetch::TYPE   => Fetch::STRING,
                ],
                'wallet' => Fields::WALLET
            ],

            Entity::UPI => [
                'payment_id' => Fields::PAYMENT_ID,
                'bank' => Fields::UPI
            ],

            Entity::USER => [
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::VIRTUAL_ACCOUNT => [
                'merchant_id' => Fields::MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::ARRAY,
                    Fetch::VALUES => [
                        'active',
                        'closed',
                        'paid',
                    ],
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::STRING,
                ],
            ],

            Entity::WALLET => [
                'payment_id' => Fields::PAYMENT_ID,
                'wallet' => Fields::WALLET
            ],

            Entity::WEBHOOK => [
                'merchant_id' => Fields::MERCHANT_ID,
            ],

            Entity::SCHEDULE => [
                'merchant_id' => Fields::MERCHANT_ID,
            ],
        ];
    }

}
