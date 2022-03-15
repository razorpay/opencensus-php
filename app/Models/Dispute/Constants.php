<?php


namespace RZP\Models\Dispute;


use RZP\Models\Terminal\Category;

class Constants
{
    const CUSTOMER_DISPUTE_GATEWAY_DISPUTE_ID_PREFIX = 'DISPUTE';

    const FD_IND_INSTANCE_ROLLOUT_TS = 1626028200; // 12th July, 2021

    const DEFAULT_INTERNAL_RESPOND_BY_IN_SECONDS     = (24 * 60 * 60) * 10;

    const GATEWAY_DISPUTE_SOURCE_CUSTOMER = 'customer';

    const GATEWAY_DISPUTE_SOURCE_NETWORK = 'network';

    const DEFAULT_DEDUCTION_REVERSAL_AT_IN_SECONDS = (24 * 60 * 60) * 45;

    const CHARGEBACK_SMS_TEMPLATE_NAME      = 'sms.risk.chargeback_notification_mobile_signup';
    const CHARGEBACK_WHATSAPP_TEMPLATE_NAME = 'whatsapp_risk_chargeback_notification_mobile_signup';
    const CHARGEBACK_WHATSAPP_TEMPLATE      = 'Hi {merchantName}, we have received chargeback against payment(s) processed on your Razorpay Account. We request you to kindly respond with proof of service within the specified deadline to contest the chargeback with the bank. Please check link {supportTicketLink} for more details';

    const MCC_TO_EXCLUDE_FROM_DEDUCT_AT_ONSET = [
        '6211',
    ];

    const CATEGORY2_TO_EXCLUDE_FROM_DEDUCT_AT_ONSET = [
        Category::GOVERNMENT,
        Category::GOVT_EDUCATION
    ];
}
