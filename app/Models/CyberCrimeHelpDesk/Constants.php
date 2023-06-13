<?php

namespace RZP\Models\CyberCrimeHelpDesk;

class Constants
{
    const CYBER_CRIME_HELPDESK_WORKFLOW_CONTROLLER = 'RZP\Http\Controllers\CyberCrimeHelpDeskController@postCyberCrimeWorkflowApproval';

    const LEA_ACKNOWLEDGEMENT_MAIL_SUBJECT = 'Razorpay Acknowledgement to LEA: %s';

    const LEA_ACKNOWLEDGEMENT_MAIL_TEMPLATE = 'emails.cyber_helpdesk.lea_acknowledgement';

    const NOTIFY_MERCHANT_ABOUT_FRAUD_MAIL_SUBJECT = 'Razorpay | Unauthorized transaction Alert - %s %s | %s';

    const NOTIFY_MERCHANT_ABOUT_FRAUD_MAIL_TEMPLATE = 'emails.cyber_helpdesk.notify_merchant';

    const SEND_DETAILS_TO_LEA_MAIL_SUBJECT = 'Razorpay Response to LEA: %s_%s';

    const SEND_DETAILS_WITH_LEA_MAIL_TEMPLATE = 'emails.cyber_helpdesk.send_details_with_lea';

    const FRESHDESK_EMAIL_CYBER_CELL_SUB_CATEGORY                       = 'Cybercell report';

    const TICKET_DATA                        = 'ticket_data';
    const TICKET                             = 'ticket';
    const DETAILS                            = 'details';
    const REQUEST_ID                         = 'request_id';
    const REQUEST                            = 'request';
    const HOLD_SETTLEMENT                    = 'hold_settlement';
    const SHARE_BENEFICIARY_ACCOUNT_DETAILS  = 'share_beneficiary_account_details';
    const FD_TICKET_ID                       = 'fd_ticket_id';
    const ID                                 = 'id';

    const ENABLE_SHARE_BENEFICIARY_DETAILS_CHECKBOX = 'enable_share_beneficiary_details_checkbox';

    const CURRENT_DATE_TIME = 'current_date_time';
    const IST_DIFF          = 'ist_diff';
    const DATA              = 'data';
    const RESPOND_BY        = 'respond_by';

    const MERCHANT_DETAILS = 'merchant_details';
    const MERCHANT_ID      = 'merchant_id';
    const MERCHANT_NAME    = 'merchant_name';
    const CONTACT_NAME     = 'contact_name';
    const CONTACT_EMAIL    = 'contact_email';
    const CONTACT_MOBILE   = 'contact_mobile';
    const BUSINESS_WEBSITE = 'business_website';

    const PAYMENT     = 'payment';
    const PAYMENT_ID  = 'payment_id';
    const TRANSACTION = 'transaction';
    const SETTLED     = 'settled';

    const PAYMENT_ANALYTICS = 'payment_analytics';
    const IP                = 'ip';


    const BANK_ACCOUNT     = 'bank_account';
    const BENEFICIARY_NAME = 'beneficiary_name';
    const ACCOUNT_NUMBER   = 'account_number';
    const IFSC_CODE        = 'ifsc_code';

    const UPI        = 'upi';
    const NETBANKING = 'netbanking';
    const CARD       = 'card';

    const SHARE_BENEFICARY_ACCOUNT_DETAILS = 'share_beneficary_account_details'; //typo at frontend needs to be fixed

    const PUT_SETTLEMENT_ON_HOLD = 'put_settlement_on_hold';

    const PAYMENT_REQUESTS = 'payment_requests';

    const PREFIX_CYBER_CRIME_PAYMENT_DETAILS_COMMENT = 'agent_approved_payment_details_';

    const MERCHANT_RESPOND_BY_IN_SECONDS = (24 * 60 * 60);

    const IST_DIFF_IN_SEC = (5 * 60 + 30) * 60;

    const VIRTUAL_ACCOUNT_PREFIXES = array("2223", "2224", "2226", "3434", "5656", "787878", "VAJSWCA");

    const REQUESTER_EMAIL = 'requester_mail';

    const RZP_EMAIL_DOMAIN = 'razorpay.com';

    const FRAUD_TYPE       = 'fraud_type';

    const  SEGMENT_EVENT_CYBER_CRIME_NON_FETCHED_PAYMENTS = 'cyber_crime_non_fetched_payments';

    const SEGMENT_EVENT_CYBER_CRIME_FETCHED_PAYMENTS = 'cyber_crime_fetched_payments';

    const SEGMENT_EVENT_CYBER_CRIME_FRAUD_PAYMENTS = 'cyber_crime_fraud_payments';
}
