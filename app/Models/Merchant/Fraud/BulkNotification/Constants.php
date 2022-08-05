<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

class Constants
{
    const FILE              = 'file';

    const INPUT_KEY_REPORTED_TO_RAZORPAY_AT = 'reported_to_razorpay_at';
    const INPUT_KEY_PAYMENT_METHOD          = 'payment_method';
    const INPUT_KEY_REPORTED_BY             = 'reported_by';
    const INPUT_KEY_PAYMENT_ID              = 'payment_id';
    const INPUT_KEY_TYPE                    = 'type';
    const INPUT_KEY_ARN                     = 'arn';

    const CYBERCELL_SOURCES     = ['CyberSafe', 'CyberCell'];
    const BANK_SOURCES          = ['Visa', 'MasterCard', 'Issuer', 'Network'];
    const CARD_NETWORK_SOURCES  = ['Visa', 'MasterCard'];
    const SOURCE_BANK           = 'Bank';
    const SOURCE_CYBERCELL      = 'CyberCell';

    const FILE_SOURCE_VISA       = 'visa';
    const FILE_SOURCE_MASTERCARD = 'mastercard';
    const FILE_SOURCE_BUYER_RISK = 'buyer_risk';

    // Fraud Entity to Report Mapping: https://docs.google.com/spreadsheets/d/1s3CwzgXZ0X0QUNuP6AO1fOFB3uYgKlAJuWt3Bn-hccA/edit#gid=1849698905
    // input keys for mastercard fraud report
    const MASTERCARD_KEY_ARN            = 'Acquirer Reference Number';
    const MASTERCARD_KEY_FRAUD_TYPE     = 'Fraud Type';
    const MASTERCARD_KEY_FRAUD_SUB_TYPE = 'Fraud Subtype';
    const MASTERCARD_KEY_AMOUNT         = 'US Bill Amount';
    const MASTERCARD_POSTED_DATE        = 'Date (Fraud Report Date)';
    const MASTERCARD_CHARGEBACK_CODE    = 'Chargeback Code';

    // input keys for visa fraud report
    const VISA_KEY_ARN                  = 'Acquirer Reference Number';
    const VISA_KEY_FRAUD_TYPE           = 'Fraud Type';
    const VISA_KEY_AMOUNT               = 'Fraud Amount';

    const BATCH_TYPE_CREATE_PAYMENT_FRAUD       = 'create_payment_fraud';

    const OUTPUT_KEY_ARN          = 'arn';
    const OUTPUT_KEY_PAYMENT_ID   = 'payment_id';
    const OUTPUT_KEY_MERCHANT_ID  = 'merchant_id';
    const OUTPUT_KEY_FD_TICKET_ID = 'fd_ticket_id';
    const OUTPUT_KEY_ERROR        = 'error';

    const BATCH_KEY_ARN                     = 'arn';
    const BATCH_KEY_RRN                     = 'rrn';
    const BATCH_KEY_TYPE                    = 'type';
    const BATCH_KEY_SUB_TYPE                = 'sub_type';
    const BATCH_KEY_AMOUNT                  = 'amount_in_cents';
    const BATCH_KEY_CURRENCY                = 'currency';
    const BATCH_KEY_BASE_AMOUNT             = 'base_amount';
    const BATCH_KEY_REPORTED_TO_ISSUER_AT   = 'reported_to_issuer_at';
    const BATCH_KEY_CHARGEBACK_CODE         = 'chargeback_code';
    const BATCH_KEY_REPORTED_BY             = 'reported_by';
    const BATCH_KEY_ERROR_REASON            = 'error_reason';
    const BATCH_KEY_SEND_MAIL               = 'send_mail';
    const BATCH_KEY_REPORTED_TO_RAZORPAY_AT = 'reported_to_razorpay_at';

    const REPORTED_BY_VISA          = 'Visa';
    const REPORTED_BY_MASTERCARD    = 'MasterCard';

    const BATCH_STATUS_CREATED  = 'Created';
    const BATCH_STATUS_UPDATED  = 'Updated';
    const BATCH_STATUS_FAILED   = 'Failed';

    const BATCH_URL_TPL = 'https://admin-dashboard.razorpay.com/admin/entity/batch.service/live/%s';

    const BULK_FRAUD_NOTIFICATION_DISABLE_MID_SET = 'bulk_fraud_notification_disable_mid_set_%s';

    const VISA_MAP = [
        self::BATCH_KEY_ARN                     => self::VISA_KEY_ARN,
        self::BATCH_KEY_TYPE                    => self::VISA_KEY_FRAUD_TYPE,
        self::BATCH_KEY_AMOUNT                  => self::VISA_KEY_AMOUNT,
        self::BATCH_KEY_SEND_MAIL               => self::BATCH_KEY_SEND_MAIL,
    ];

    const MASTERCARD_MAP = [
        self::BATCH_KEY_ARN                     =>  self::MASTERCARD_KEY_ARN,
        self::BATCH_KEY_TYPE                    =>  self::MASTERCARD_KEY_FRAUD_TYPE,
        self::BATCH_KEY_SUB_TYPE                =>  self::MASTERCARD_KEY_FRAUD_SUB_TYPE,
        self::BATCH_KEY_AMOUNT                  =>  self::MASTERCARD_KEY_AMOUNT,
        self::BATCH_KEY_CHARGEBACK_CODE         =>  self::MASTERCARD_CHARGEBACK_CODE,
        self::BATCH_KEY_SEND_MAIL               =>  self::BATCH_KEY_SEND_MAIL,
    ];

    // 24 hours = 24 * 60 * 60 = 86400
    const REDIS_KEY_TTL = 86400;
    // redis key format: risk:fraud_notification_fd_<date>_<mid>
    const REDIS_KEY_FMT = 'risk:fraud_notification_fd_%s_%s';

    const MAX_NOTIFY_COUNT_PER_DAY_PER_MERCHANT = 8;

    const MERCHANT_DATA_KEY_NOTES                  = 'notes';
    const MERCHANT_DATA_KEY_AMOUNT                 = 'amount';
    const MERCHANT_DATA_KEY_RESPOND_BY             = 'respond_by';
    const MERCHANT_DATA_KEY_PAYMENT_ID             = 'payment_id';
    const MERCHANT_DATA_KEY_ORDER_RECEIPT          = 'order_receipt';
    const MERCHANT_DATA_KEY_CUSTOMER_CONTACT       = 'customer_contact';
    const MERCHANT_DATA_KEY_TRANSACTION_DATE       = 'transaction_date';
    const MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION = 'source_of_notification';
    const MERCHANT_DATA_KEY_CURRENCY = 'currency';

    const VISA_FRAUD_FILE_SOURCE        = 'Hitachi_VISA_TC_40_Report';
    const MASTERCARD_FRAUD_FILE_SOURCE  = 'Hitachi_SAFE_Report';

    const FRESHDESK_EMAIL_SUBJECT_DEFAULT = 'Razorpay | Unauthorized transaction Alert - %s [%s] | %s';
    const FRESHDESK_EMAIL_SUBJECT_CARD_NETWORK = 'Razorpay: Cross-Border Fraud transactions alert from the Card Schemes/Networks | %s | %s';
    const SMS_TEMPLATE              = 'sms.risk.fraud_notification_mobile_signup';
    const WHATSAPP_TEMPLATE_NAME    = 'whatsapp_risk_fraud_notification_mobile_signup';
    const WHATSAPP_TEMPLATE         = 'Hi {merchantName}, we have received an unauthorised transaction alert on the payments processed on your Razorpay Account. We request you to kindly stop the specified transactions and issue a refund for the same. Please check link {supportTicketLink} for more details';

    const FRAUD_ERROR_REASON_ARN_TO_PAYMENT_ID = 'Failed to fetch payment_id from ARN or RRN';
    const FRAUD_ERROR_REASON_ARN_NOT_FOUND     = 'ARN not found for the following row';
    const FRAUD_ERROR_REASON_ARN_TO_RRN        = 'Failed to map RRN from ARN';

    const IDEMPOTENCY_KEY   = 'idempotency_key';
    const SUCCESS           = 'success';

    const JAN_1_1970_TIMESTAMP = 25569;

    const DAYS_TO_SECONDS_MULTIPLIER = 86400;
}
