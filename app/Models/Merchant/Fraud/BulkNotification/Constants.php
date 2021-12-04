<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

class Constants
{
    const FILE = 'file';

    const INPUT_KEY_REPORTED_TO_RAZORPAY_AT = 'reported_to_razorpay_at';
    const INPUT_KEY_PAYMENT_METHOD          = 'payment_method';
    const INPUT_KEY_REPORTED_BY             = 'reported_by';
    const INPUT_KEY_PAYMENT_ID              = 'payment_id';
    const INPUT_KEY_TYPE                    = 'type';
    const INPUT_KEY_ARN                     = 'arn';

    const CYBERCELL_SOURCES = ['CyberSafe', 'CyberCell'];
    const BANK_SOURCES      = ['Visa', 'MasterCard', 'Issuer', 'Network'];
    const SOURCE_BANK       = 'Bank';
    const SOURCE_CYBERCELL  = 'CyberCell';

    const OUTPUT_KEY_ARN          = 'arn';
    const OUTPUT_KEY_PAYMENT_ID   = 'payment_id';
    const OUTPUT_KEY_MERCHANT_ID  = 'merchant_id';
    const OUTPUT_KEY_FD_TICKET_ID = 'fd_ticket_id';
    const OUTPUT_KEY_ERROR        = 'error';

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

    const FRESHDESK_EMAIL_SUBJECT   = 'Razorpay | Unauthorized transaction Alert - %s [%s] | %s';
    const SMS_TEMPLATE              = 'sms.risk.fraud_notification_mobile_signup';
    const WHATSAPP_TEMPLATE_NAME    = 'whatsapp_risk_fraud_notification_mobile_signup';
    const WHATSAPP_TEMPLATE         = 'Hi {merchantName}, we have received an unauthorised transaction alert on the payments processed on your Razorpay Account. We request you to kindly stop the specified transactions and issue a refund for the same. Please check link {supportTicketLink} for more details';
}
