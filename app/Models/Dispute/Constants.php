<?php


namespace RZP\Models\Dispute;


use RZP\Models\Terminal\Category;

class Constants
{
    const CUSTOMER_DISPUTE_GATEWAY_DISPUTE_ID_PREFIX = 'DISPUTE';

    const FD_IND_INSTANCE_ROLLOUT_TS = 1626028200; // 12th July, 2021

    const DEFAULT_INTERNAL_RESPOND_BY_IN_SECONDS     = (24 * 60 * 60) * 10;

    const REVERSE_SHADOW_RESPONSE   = 'reverse_shadow_response';
    const CREATE_REQUEST            = 'create_request';

    const GATEWAY_DISPUTE_SOURCE_CUSTOMER = 'customer';

    const GATEWAY_DISPUTE_SOURCE_NETWORK = 'network';

    const GATEWAY_DISPUTE_CODE = 'gateway_code';

    const UPDATE = "update";
    const CREATE = "create";
    const EVIDENCE_DOCUMENTS = "evidence_documents";
    const PURGE_DISPUTE_DOCUMENT = "purge_dispute_document";

    const DEFAULT_DEDUCTION_REVERSAL_AT_IN_SECONDS = (24 * 60 * 60) * 45;

    const CHARGEBACK_SMS_TEMPLATE_NAME      = 'sms.risk.chargeback_notification_mobile_signup';
    const CHARGEBACK_WHATSAPP_TEMPLATE_NAME = 'whatsapp_risk_chargeback_notification_mobile_signup';
    const CHARGEBACK_WHATSAPP_TEMPLATE      = 'Hi {merchantName}, we have received chargeback against payment(s) processed on your Razorpay Account. We request you to kindly respond with proof of service within the specified deadline to contest the chargeback with the bank. Please check link {supportTicketLink} for more details';

    const RISK_CHARGEBACK_INTIMATION_WITH_ATTACHMENT_TEMPLATE_NAME = 'risk_chargeback_with_attachment';
    const RISK_CHARGEBACK_INTIMATION_WITH_ATTACHMENT_TEMPLATE = '*CHARGEBACK NOTIFICATION*

Hello {merchantName}

Greetings from Razorpay!

This is to notify you on the chargeback(s) received on the following payments accepted by your business. Please download the attached file for the detailed list

1. You are requested to immediately stop the delivery of the services for the given transactions (and let us know once you do so).
2. In case the services are already rendered, kindly upload the below documents on your Razorpay dashboard :
A) Valid invoices
B) Proofs of delivery
C) Any other relevant docs pertaining to each chargeback.

Please login to your Razorpay dashboard (click on the link at the bottom) to respond to all the pending chargebacks in a timely manner to avoid business losses.

Regards,
Razorpay';

    const MCC_TO_EXCLUDE_FROM_DEDUCT_AT_ONSET = [
        '6211',
    ];

    const CATEGORY2_TO_EXCLUDE_FROM_DEDUCT_AT_ONSET = [
        Category::GOVERNMENT,
        Category::GOVT_EDUCATION
    ];

    const FRAUD_CHARGEBACK_MAPPING = [
        '10.1'  =>  'Visa',
        '10.2'  =>  'Visa',
        '10.3'  =>  'Visa',
        '10.4'  =>  'Visa',
        '4837'  =>  'Mastercard',
        '4840'  =>  'Mastercard',
        '4849'  =>  'Mastercard',
        '4863'  =>  'Mastercard',
    ];

    const FRAUD_CHARGEBACK = 'fraud_chargeback';

    const NETWORK_RUPAY = 'RuPay';
    const GATEWAY_CODE_1065 = '1065';
    const DISPUTE_REASON_CODE_ACCOUNT_DEBITED_NO_TRANSACTION_CONFIRMATION = 'account_debited_but_transaction_confirmation_not_received_at_merchant_location';

    const BULK_DISPUTE_ATTACHMENT_FILE_NAME = 'bulk_disputes_list';

    const MERCHANT                      = 'merchant';
    const GROUP_IDS                     = 'group_ids';
    const CHARGEBACKS                   = 'chargebacks';
    const EMAIL_CONFIG_IDS              = 'email_config_ids';
    const DISPUTESDATATABLE             = 'disputesDataTable';

    const DISPUTE_MERCHANT_EMAILS_INITIATE_ID_KEY = 'app.dispute_merchant_emails_initiate_experiment_id';

    const VARIANT_ENABLE = 'enable';

    const TYPE_STRING    = 'string';

    const DISPUTE_CONTEST_BY_MUTEX_PREFIX              = 'dispute_contest_by_';

    const DISPUTE_CONTEST_BY_MUTEX_TIMEOUT             = 60; // seconds

    const DISPUTE_CONTEST_BY_MUTEX_ACQUIRE_RETRY_LIMIT = 5;

    const ARB_PRE_ARB_FEE_ADJUSTMENT_EXPERIMENT = 'app.arb_pre_arb_fee_adjustment_experiment_id';

    const  AUTO_CONTEST_DISPUTE_GOVT_MX_EXPERIMENT_ID ='app.auto_contest_dispute_govt_mx_experiment_id';

    // Network
    public const MASTERCARD = 'MasterCard';
    public const VISA = 'Visa';
    public const RUPAY = 'RuPay';
    public const NPCI = 'NPCI';

    // Attribute keys
    public const PHASE = 'phase';
    public const NETWORK = 'network';
    public const FEE_AMOUNT = 'fee_amount';
    public const CURRENCY = 'currency';

    public const FEE_NEGATIVE_ADJUSTMENT_ID = 'fee_negative_adjustment_id';
    public const FEE_POSITIVE_ADJUSTMENT_ID = 'fee_positive_adjustment_id';

    // Currency
    public const CURRENCY_USD = 'USD';
    public const CURRENCY_INR = 'INR';

    public const DISPUTE_FEES = [
        [
            self::PHASE => phase::PRE_ARBITRATION,
            self::NETWORK => self::MASTERCARD,
            self::FEE_AMOUNT => 1500,
            self::CURRENCY => self::CURRENCY_USD,
        ],
        [
            self::PHASE => phase::PRE_ARBITRATION,
            self::NETWORK => self::VISA,
            self::FEE_AMOUNT => 75,
            self::CURRENCY => self::CURRENCY_USD,
        ],
        [
            self::PHASE => phase::ARBITRATION,
            self::NETWORK => self::MASTERCARD,
            self::FEE_AMOUNT => 67500,
            self::CURRENCY => self::CURRENCY_USD,
        ],
        [
            self::PHASE => phase::ARBITRATION,
            self::NETWORK => self::VISA,
            self::FEE_AMOUNT => 60000,
            self::CURRENCY => self::CURRENCY_USD,
        ],
        [
            self::PHASE => phase::ARBITRATION,
            self::NETWORK => self::RUPAY,
            self::FEE_AMOUNT => 354000,
            self::CURRENCY => self::CURRENCY_INR,
        ],
        [
            self::PHASE => phase::ARBITRATION,
            self::NETWORK => self::NPCI,
            self::FEE_AMOUNT => 50000,
            self::CURRENCY => self::CURRENCY_INR,
        ],
    ];
}
