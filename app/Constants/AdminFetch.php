<?php

namespace RZP\Constants;

use RZP\Base\Fetch;
use RZP\Models\Dispute;

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
            Entity::ADDON => [
                'deleted' => [
                    Fetch::LABEL    => 'Deleted',
                    Fetch::TYPE     => Fetch::TYPE_BOOLEAN
                ],
                'invoice_id' => [
                    Fetch::LABEL    => 'Invoice Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'merchant_id'       => Fetch::FIELD_MERCHANT_ID,
                'subscription_id'   => Fetch::FIELD_SUBSCRIPTION_ID,
            ],

            Entity::ADJUSTMENT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::AMEX => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::APP_TOKEN => [
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'device_token' => [
                    Fetch::LABEL  => 'Device Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::AXIS_GENIUS => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::AXIS_MIGS => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_ShopTransactionNo' => [
                    Fetch::LABEL  => 'Shop Transaction No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_TransactionNo' => [
                    Fetch::LABEL  => 'Transaction No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_TxnResponseCode' => [
                    Fetch::LABEL  => 'Txn Response Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_3DSstatus' => [
                    Fetch::LABEL  => 'Vpc 3DSstatus',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
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
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'customer',
                        'merchant',
                    ],
                ],
            ],

            Entity::BANK_TRANSFER => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'virtual_account_id' => [
                    Fetch::LABEL  => 'Virtual Account ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'mode' => [
                    Fetch::LABEL  => 'Mode',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'neft',
                        'rtgs',
                        'ift',
                        'imps',
                    ],
                ],
                'payer_account' => [
                    Fetch::LABEL  => 'Payer Account',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payer_ifsc' => [
                    Fetch::LABEL  => 'Payer IFSC',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payee_account' => [
                    Fetch::LABEL  => 'Payee Account',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payee_ifsc' => [
                    Fetch::LABEL  => 'Payee IFSC',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'refund_id' => Fetch::FIELD_REFUND_ID,
            ],

            Entity::BATCH => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'processing',
                        'processed',
                    ],
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'all',
                        'payment_link',
                        'refund',
                        'irctc_refund',
                        'irctc_settlement'
                    ],
                ],
            ],

            Entity::BATCH_FUND_TRANSFER => [
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'settlement',
                        'payout',
                        'refund',
                    ],
                ],
                'date' => [
                    Fetch::LABEL  => 'Date',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::BILLDESK => [
                'AuthStatus' => [
                    Fetch::LABEL  => 'AuthStatus',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
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
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'RefStatus' => [
                    Fetch::LABEL  => 'Refund Status',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'RefundId' => [
                    Fetch::LABEL  => 'Billdesk Refund Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'TxnReferenceNo' => [
                    Fetch::LABEL  => 'Txn Reference No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CARD => [
                'global_card_id' => [
                    Fetch::LABEL  => 'Global Card Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'iin' => [
                    Fetch::LABEL  => 'IIN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'last4' => [
                    Fetch::LABEL  => 'Last4',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
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
                'status' => Fetch::FIELD_PAYMENT_STATUS,
                'vault' => [
                    Fetch::LABEL  => 'Vault',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vault_token' => [
                    Fetch::LABEL  => 'Vault Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CREDITS => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'fee',
                        'amount',
                    ],
                ],
            ],

            Entity::CUSTOMER => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'active' => [
                    Fetch::LABEL  => 'Active',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'contact' => [
                    Fetch::LABEL  => 'Contact',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CUSTOMER_BALANCE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CUSTOMER_TRANSACTION => [
                'entity_id' => [
                    Fetch::LABEL  => 'Payment/Refund Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'transfer',
                        'refund',
                    ],
                ],
            ],

            Entity::CYBERSOURCE => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'ref' => [
                    Fetch::LABEL  => 'Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'capture_ref' => [
                    Fetch::LABEL  => 'Capture Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::EBS => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::FEE_BREAKUP => [
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'pricing_rule_id' => [
                    Fetch::LABEL  => 'Pricing Rule Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::FIRST_DATA => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'action' => [
                    Fetch::LABEL  => 'Action',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'refund_id' => [
                    Fetch::LABEL  => 'Refund ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_payment_id' => [
                    Fetch::LABEL  => 'Gateway Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'tdate' => [
                    Fetch::LABEL  => 'Tdate',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'caps_payment_id' => [
                    Fetch::LABEL  => 'Caps Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_transaction_id' => [
                    Fetch::LABEL  => 'Gateway Transaction ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::DISPUTE => [
                Dispute\Entity::MERCHANT_ID     => Fetch::FIELD_MERCHANT_ID,
                Dispute\Entity::PAYMENT_ID      => Fetch::FIELD_PAYMENT_ID,
                Dispute\Entity::STATUS          => [
                    Fetch::LABEL        => 'Status',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        Dispute\Status::OPEN,
                        Dispute\Status::UNDER_REVIEW,
                        Dispute\Status::WON,
                        Dispute\Status::LOST,
                        Dispute\Status::CLOSED,
                    ],
                ],
                Dispute\Entity::PHASE           => [
                    Fetch::LABEL        => 'Phase',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        Dispute\Phase::CHARGEBACK,
                        Dispute\Phase::PRE_ARBITRATION,
                        Dispute\Phase::ARBITRATION,
                        Dispute\Phase::RETRIEVAL,
                        Dispute\Phase::FRAUD,
                    ],
                ],
                Dispute\Entity::AMOUNT          => [
                    Fetch::LABEL        => 'Amount',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],

            Entity::DISPUTE_REASON => [
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => Dispute\Reason\Network::list(),
                ],
                'code' => [
                    Fetch::LABEL  => 'Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'description' => [
                    Fetch::LABEL  => 'Description',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_code' => [
                    Fetch::LABEL  => 'Gateway Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_description' => [
                    Fetch::LABEL  => 'Gateway Description',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ]
            ],

            Entity::EMI_PLAN => [
                'bank' => [
                    Fetch::LABEL  => 'Bank',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::FEATURE => [
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'entity_type' => [
                    Fetch::LABEL  => 'Entity Type',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'name' => [
                    Fetch::LABEL  => 'Name',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::FILE_STORE => [
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::FUND_TRANSFER_ATTEMPT => [
                'batch_fund_transfer_id' => [
                    Fetch::LABEL  => 'Batch Fund Transfer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'source_type' => [
                    Fetch::LABEL  => 'Source Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'settlement',
                        'payout',
                        'refund'
                    ],
                ],
                'source_id' => [
                    Fetch::LABEL  => 'Source Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'initiated',
                        'failed',
                        'processed',
                    ],
                ],
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::GATEWAY_DOWNTIME => [
                'method' => Fetch::FIELD_METHOD,
                'gateway' => Fetch::FIELD_GATEWAY,
                'issuer' => [
                    Fetch::LABEL  => 'Issuer',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::GATEWAY_FILE => [
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'emi',
                        'refund',
                        'combined',
                    ],
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'file_generated',
                        'file_sent',
                        'failed',
                        'acknowledged'
                    ],
                ],
                'target' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'rbl',
                        'hdfc',
                        'axis',
                        'icici',
                        'kotak',
                        'federal',
                    ],
                ],
            ],

            Entity::HDFC => [
                'auth' => [
                    Fetch::LABEL  => 'Auth Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_transaction_id' => [
                    Fetch::LABEL  => 'Gateway Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'refund_id' => [
                    Fetch::LABEL  => 'Refund Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'ref' => [
                    Fetch::LABEL  => 'Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::IIN => [
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'otp_read' => [
                    Fetch::LABEL  => 'Otp Read',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'iin' => [
                    Fetch::LABEL  => 'Iin',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'issuer' => [
                    Fetch::LABEL  => 'Issuer',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'credit',
                        'debit',
                        'unknown',
                    ],
                ],
            ],

            Entity::INVOICE => [
                'batch_id' => [
                    Fetch::LABEL  => 'Batch Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'receipt' => [
                    Fetch::LABEL  => 'Receipt',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'user_id' => [
                    Fetch::LABEL  => 'User Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'draft',
                        'issued',
                        'partially_paid',
                        'paid',
                        'cancelled',
                        'expired',
                    ],
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'ecod',
                        'link',
                        'invoice',
                    ],
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'order_id' => [
                    Fetch::LABEL  => 'Order Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'notes' => Fetch::FIELD_NOTES,
                'subscription_id' => Fetch::FIELD_SUBSCRIPTION_ID,
                'customer_name' => [
                    Fetch::LABEL  => 'Customer Name',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_email' => [
                    Fetch::LABEL  => 'Customer Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_contact' => [
                    Fetch::LABEL  => 'Customer Contact',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::ITEM => [
                'active' => [
                    Fetch::LABEL  => 'Active',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::KEY => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::MERCHANT => [
                'activated' => [
                    Fetch::LABEL  => 'Activated',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.amex' => [
                    Fetch::LABEL  => 'Amex',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.card' => [
                    Fetch::LABEL  => 'Card',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'category' => [
                    Fetch::LABEL  => 'MCC Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'category2' => [
                    Fetch::LABEL  => 'Category 2',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'hold_funds' => [
                    Fetch::LABEL  => 'Hold Funds',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'live' => [
                    Fetch::LABEL  => 'Live',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.mobikwik' => [
                    Fetch::LABEL  => 'Mobikwik',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.paytm' => [
                    Fetch::LABEL  => 'Paytm',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.payumoney' => [
                    Fetch::LABEL  => 'Payumoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.payzapp' => [
                    Fetch::LABEL  => 'Payzapp',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.olamoney' => [
                    Fetch::LABEL  => 'Olamoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.mpesa' => [
                    Fetch::LABEL  => 'Mpesa',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.upi' => [
                    Fetch::LABEL  => 'Upi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.airtelmoney' => [
                    Fetch::LABEL  => 'Airtelmoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.freecharge' => [
                    Fetch::LABEL  => 'Freecharge',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.jiomoney' => [
                    Fetch::LABEL  => 'Jiomoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.sbibuddy' => [
                    Fetch::LABEL  => 'Sbibuddy',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'pricing_plan_id' => [
                    Fetch::LABEL  => 'Pricing Plan Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'parent_id' => [
                    Fetch::LABEL  => 'Marketplace Parent Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'receipt_email_enabled' => [
                    Fetch::LABEL  => 'Receipt Email_enabled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'fee_bearer' => [
                    Fetch::LABEL  => 'Fee Bearer',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'platform',
                        'customer',
                    ],
                ],
                'fee_model' => [
                    Fetch::LABEL  => 'Fee Model',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'prepaid',
                        'postpaid',
                    ],
                ],
                'risk_rating' => [
                    Fetch::LABEL  => 'Risk Rating',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
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
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'card' => [
                    Fetch::LABEL  => 'Card',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'mobikwik' => [
                    Fetch::LABEL  => 'Mobikwik',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'paytm' => [
                    Fetch::LABEL  => 'Paytm',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'payumoney' => [
                    Fetch::LABEL  => 'Payumoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'payzapp' => [
                    Fetch::LABEL  => 'Payzapp',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'mpesa' => [
                    Fetch::LABEL  => 'Mpesa',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'olamoney' => [
                    Fetch::LABEL  => 'Olamoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'upi' => [
                    Fetch::LABEL  => 'Upi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'airtelmoney' => [
                    Fetch::LABEL  => 'Airtelmoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'freecharge' => [
                    Fetch::LABEL  => 'Freecharge',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'jiomoney' => [
                    Fetch::LABEL  => 'Jiomoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'sbibuddy' => [
                    Fetch::LABEL  => 'Sbibuddy',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::MERCHANT_INVOICE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'invoice_number' => [
                    Fetch::LABEL  => 'Invoice No.',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gstin' => [
                    Fetch::LABEL  => 'GSTIN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'month' => [
                    Fetch::LABEL  => 'Month',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'year' => [
                    Fetch::LABEL  => 'Year',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::MOBIKWIK => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::NETBANKING => [
                'bank_payment_id' => [
                    Fetch::LABEL  => 'Bank Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'caps_payment_id' => [
                    Fetch::LABEL  => 'Caps Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'int_payment_id' => [
                    Fetch::LABEL  => 'Int Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::OFFER => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::ORDER => [
                'account_number' => [
                    Fetch::LABEL  => 'Account Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'authorized' => [
                    Fetch::LABEL  => 'Authorized',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'notes' => Fetch::FIELD_NOTES,
                'receipt' => [
                    Fetch::LABEL  => 'Receipt',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
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
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::PAYMENT => [
                'app_token' => [
                    Fetch::LABEL  => 'App Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'bank' => [
                    Fetch::LABEL  => 'Bank Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'card_id' => [
                    Fetch::LABEL  => 'Card Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'global_customer_id' => [
                    Fetch::LABEL  => 'Global Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'email' => [
                    Fetch::LABEL  => 'Contact Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway' => Fetch::FIELD_GATEWAY,
                'global_token_id' => [
                    Fetch::LABEL  => 'Global Token Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'iin' => [
                    Fetch::LABEL  => 'Card IIN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'invoice_id' => [
                    Fetch::LABEL  => 'Invoice Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'last4' => [
                    Fetch::LABEL  => 'Card Last 4',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'method' => Fetch::FIELD_METHOD,
                'notes' => Fetch::FIELD_NOTES,
                'order_id' => [
                    Fetch::LABEL  => 'Order Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'refund_status' => [
                    Fetch::LABEL  => 'Refund Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'null',
                        'partial',
                        'full',
                    ],
                ],
                'save' => [
                    Fetch::LABEL  => 'Save',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'status' => Fetch::FIELD_PAYMENT_STATUS,
                'subscription_id' => Fetch::FIELD_SUBSCRIPTION_ID,
                'terminal_id' => [
                    Fetch::LABEL  => 'Terminal ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'token_id' => [
                    Fetch::LABEL  => 'Token Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'transfer_id' => [
                    Fetch::LABEL  => 'Transfer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'verified' => [
                    Fetch::LABEL  => 'Verified',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'null',
                        '0',
                        '1',
                        '2',
                    ],
                ],
                'wallet' => Fetch::FIELD_WALLET,
            ],

            Entity::PAYOUT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'destination' => [
                    Fetch::LABEL  => 'Bank Account Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'method' => [
                    Fetch::LABEL  => 'Method',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'fund_transfer',
                    ],
                ],
            ],

            Entity::PAYTM => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::PLAN => [
                'interval' => [
                    Fetch::LABEL  => 'Interval',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'item_id' => [
                    Fetch::LABEL  => 'Item Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'period' => [
                    Fetch::LABEL  => 'Period',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::PRICING => [
                'plan_id' => [
                    Fetch::LABEL  => 'Plan Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::REFUND => [
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'batch_id' => [
                    Fetch::LABEL  => 'Batch Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway' => Fetch::FIELD_GATEWAY,
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'method' => Fetch::FIELD_METHOD,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'processed',
                    ],
                ],
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'notes' => Fetch::FIELD_NOTES,
            ],

            Entity::REPORT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'merchant',
                        'order',
                        'payment',
                        'refund',
                        'reversal',
                        'settlement',
                        'transaction'
                    ],
                ],
            ],

            Entity::REVERSAL => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'transfer_id' => [
                    Fetch::LABEL  => 'Transfer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::RISK => [
                'fraud_type' => [
                    Fetch::LABEL  => 'Fraud Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'suspected',
                        'confirmed',
                    ],
                ],
                'source' => [
                    Fetch::LABEL  => 'Source',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'bank',
                        'gateway',
                        'maxmind',
                        'manual',
                        'internal',
                    ],
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::SETTLEMENT => [
                'batch_fund_transfer_id' => [
                    Fetch::LABEL  => 'Batch Fund Transfer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'processed',
                    ],
                ],
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::SETTLEMENT_DETAILS => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'settlement_id' => [
                    Fetch::LABEL  => 'Settlement Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::SUBSCRIPTION => [
                'auth_attempts' => [
                    Fetch::LABEL  => 'Auth Attempts',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_email' => [
                    Fetch::LABEL  => 'Customer Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'error_status' => [
                    Fetch::LABEL  => 'Error Status',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'notes' => Fetch::FIELD_NOTES,
                'plan_id' => [
                    Fetch::LABEL  => 'Plan Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'schedule_id' => [
                    Fetch::LABEL  => 'Schedule Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'authenticated',
                        'active',
                        'pending',
                        'halted',
                        'cancelled',
                        'completed',
                        'expired'
                    ]
                ],
                'token_id' => [
                    Fetch::LABEL  => 'Token Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::TERMINAL => [
                'enabled' => [
                    Fetch::LABEL  => 'Enabled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'gateway' => Fetch::FIELD_GATEWAY,
                'category' => [
                    Fetch::LABEL  => 'Category',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'shared' => [
                    Fetch::LABEL  => 'Shared',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'gateway_merchant_id' => [
                    Fetch::LABEL  => 'Gateway Merchant Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_terminal_id' => [
                    Fetch::LABEL  => 'Gateway Terminal Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'network_category' => [
                    Fetch::LABEL  => 'Network Category',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_acquirer' => [
                    Fetch::LABEL  => 'Gateway Acquirer',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'axis',
                        'hdfc',
                        'icic',
                    ],
                ],
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::TRANSACTION => [
                'entity_id' => [
                    Fetch::LABEL  => 'Payment/Refund/Settlement Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'reconciled' => [
                    Fetch::LABEL  => 'Reconciled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'settled' => [
                    Fetch::LABEL  => 'Settled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'on_hold' => [
                    Fetch::LABEL  => 'On Hold',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'settlement_id' => [
                    Fetch::LABEL  => 'Settlement Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
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
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'recipient' => [
                    Fetch::LABEL  => 'Recipient Merchant/Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::TOKEN => [
                'bank' => [
                    Fetch::LABEL  => 'Bank Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'card_id' => [
                    Fetch::LABEL  => 'Card Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id'   => Fetch::FIELD_MERCHANT_ID,
                'method'        => Fetch::FIELD_METHOD,
                'terminal_id' => [
                    Fetch::LABEL  => 'Terminal Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'token' => [
                    Fetch::LABEL  => 'Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'wallet' => Fetch::FIELD_WALLET
            ],

            Entity::UPI => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'bank' => Fetch::FIELD_UPI,
                'gateway_payment_id' => [
                    Fetch::LABEL  => 'Gateway Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'npci_reference_id' => [
                    Fetch::LABEL  => 'NPCI Reference Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'refund_id' => Fetch::FIELD_REFUND_ID,
            ],

            Entity::USER => [
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::VIRTUAL_ACCOUNT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'active',
                        'closed',
                        'paid',
                    ],
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::WALLET => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'gateway_payment_id' => [
                    Fetch::LABEL  => 'Gateway Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'wallet' => Fetch::FIELD_WALLET,
            ],

            Entity::WEBHOOK => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::SCHEDULE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],
        ];
    }

}
