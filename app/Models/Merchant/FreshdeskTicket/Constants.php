<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

class Constants
{
    // Query params
    const PAGE      = 'page';
    const QUERY     = 'query';
    const STATUS    = 'status';
    const PER_PAGE  = 'per_page';
    const PAN       = 'pan';
    const EMAIL     = 'email';
    const OLD_EMAIL = 'old_email';
    const PHONE     = 'phone';
    const OLD_PHONE = 'old_phone';

    const CC_EMAILS                 = 'cc_emails';

    const CUSTOM_FIELDS             = 'custom_fields';
    // custom fields
    const CF_REQUESTOR_CATEGORY     = 'cf_requester_category';
    const CF_REQUESTOR_SUBCATEGORY  = 'cf_requestor_subcategory';
    const CF_SUBCATEGORY            = 'cf_subcategory';
    const CF_REQUESTOR_ITEM         = 'cf_requester_item';
    const CF_CATEGORY               = 'cf_category';
    const TRANSACTION_ID            = 'cf_transaction_id';
    const PAYMENT_ID                = 'cf_razorpay_payment_id';
    const REFUND_ID                 = 'cf_refund_id';
    const ORDER_ID                  = 'cf_order_id';
    const CF_MERCHANT_ID            = 'cf_merchant_id';
    const PAYMENT_CUSTOMER_EMAIL    = 'cf_payment_email';
    const PAYMENT_CUSTOMER_PHONE    = 'cf_payment_phone';
    const CF_MERCHANT_ID_DASHBOARD  = 'cf_merchant_id_dashboard';
    const CF_TICKET_QUEUE           = 'cf_ticket_queue';
    const CF_PRODUCT                = 'cf_product';
    const CF_QUERY                  = 'cf_query';
    const CF_CREATED_BY             = 'cf_created_by';

    const AGENT = 'agent';

    //Flows
    const CUSTOMER = 'Customer';
    const PARTNER  = 'Partner';

    // ID Types
    const PAYMENT       = 'payment';
    const REFUND        = 'refund';
    const ORDER         = 'order';
    const TRANSACTION   = 'transaction';
    const TYPE          = 'type';

    const OTP                                   = 'otp';
    const OTP_SOURCE                            = 'source';
    const OTP_CONTEXT                           = 'context';
    const OTP_RECEIVER                          = 'receiver';
    const OTP_CUSTOMER_SUPPORT_SOURCE           = 'customer_support';
    const SMS_OTP_TEMPLATE_FOR_ACCOUNT_RECOVERY = 'sms.support.account_recovery_otp';

    const GOOGLE_CAPTCHA_VERIFICATION_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    const CAPITAL_QUERY               = 'Corporate Credit Cards';
    // Results
    const TOTAL   = 'total';
    const RESULTS = 'results';

    const USER_ID       = 'user_id';
    const TICKET_ID     = 'ticket_id';
    const PRIORITY      = 'priority';
    const CREATED_AT    = 'created_at';
    const FROM_EMAIL    = 'from_email';
    const FR_DUE_BY     = 'fr_due_by';
    const GROUP_ID      = 'group_id';

    const FD_INSTANCE      = 'fd_instance';
    const FRESHDESK_CLIENT = 'freshdesk_client';

    // Custom field prefix
    const MERCHANT_DASHBOARD = 'merchant_dashboard';

    // Instances & urls
    const URL    = 'url';
    const RZP    = 'rzp';
    const URLIND = 'urlind';
    const RZPIND = 'rzpind';
    const URL2   = 'url2';
    const URLCAP = 'urlcap';
    const RZPSOL = 'rzpsol';
    const RZPCAP = 'rzpcap';
    const RZPX   = 'rzpx';
    const URLX   = 'urlx';

    const FRESHDESK_INSTANCES = [
        Type::SUPPORT_DASHBOARD_X => [self::RZPX   => self::URLX,
                                      self::RZPCAP => self::URLCAP],
        Type::SUPPORT_DASHBOARD   => [self::RZPIND => self::URLIND,
                                      self::RZPSOL => self::URL2,
                                      self::RZPCAP => self::URLCAP]
    ];

    // Active tickets and work in progress tickets
    const ACTIVE_STATUSES = [2, 3, 8, 9, 10, 11];

    // Awaiting merchant's response
    const MERCHANT_ACTION_STATUSES = [6];

    // Cache keys
    const CACHE_KEY_FIRST_RESPONSE_TIME_AVERAGE = 'support_dashboard_fr_time_average_cache_key_%s_%s';

    const FRESHDESK_TIME_FORMAT = '%Y-%m-%dT%H:%I:%SZ';

    // Grievance related constants
    const GRIEVANCE_TAGS                    = ['new_grievance_raised'];
    // workflow constant
    const AUTOMATED_WORKFLOW_RESOLVE_TAGS   = ['automated_workflow_response'];
    // All custom fields allowed to be queried
    const CUSTOM_FIELDS_LIST_FOR_QUERY = [self::CF_REQUESTOR_CATEGORY, self::CF_REQUESTOR_SUBCATEGORY , self::CF_REQUESTOR_ITEM, self::CF_CREATED_BY];
    // Fd instances to find ticket details
    const FD_INSTANCES_LIST_FOR_FETCHING_CUSTOMER_TICKETS = [self::RZPIND];

    //Freshdesk  Ticket Fields
    const TICKET_PRIORITY   = 'priority';
    const TICKET_STATUS     = 'status';
    const TICKET_TAGS       = 'tags';
    const RESPONDER_ID      = 'responder_id';

    const ATTACHMENTS       = 'attachments';
    const BODY              = 'body';
    const DESCRIPTION       = 'description';
    const SUBJECT           = 'subject';
    const NAME              = 'name';

    const NAME_NOT_PROVIDED = 'NAME NOT PROVIDED';
    //description constants
    const DESCRIPTION_CONTACT_DETAILS = 'New Contact Detail: ';
    CONST DESCRIPTION_ERROR_MESSAGE   = "<b style='color:red;'> Error Message: </b>";

    const ROUTE             = 'route';
    const RESPONSE_CODE     = 'response_code';

    // Webhook response
    const SUCCESS    = 'success';

    // razorx flags
    const RAZORX_FLAG_VALIDATE_FRESHDESK_ATTACHMENT_EXTENSION         = 'validate_freshdesk_attachment_extension';
    const RAZORX_FLAG_SHOULD_MIGRATE_FRESHDESK_IND_MERCHANT           = 'should_migrate_freshdesk_ind_merchant';
    const RAZORX_FLAG_FRESHDESK_CUSTOMER_TICKET_CREATION_SERVER_PICK  = 'Freshdesk_Customer_Ticket_Creation_Server_Pick';
    const RAZORX_FLAG_FRESHDESK_RZPSOL_RZP_MERGED                     = 'freshdesk_rzpsol_rzp_merged';
    const RAZORX_FLAG_TO_ADD_PLUGIN_MERCHANT_TAG                      = 'freshdesk_add_plugin_merchant_tag';

    // values for account recovery flow
    const MERCHANT          = 'Merchant';
    const ACCOUNT_LOCKED    = 'Account Locked';

    // Default values for Activation Workflow Ticket Creation
    const SERVICE_REQUEST_TICKET_TYPE   = 'Service request';
    const ACTIVATION_SUBJECT            = 'Ticket Created from Backend';
    const ACTIVATION_CF_CATEGORY        = 'Dashboard';
    const ACTIVATION_CF_SUBCATEGORY     = 'Account Activated and Account Rejected';
    const MERCHANT_TICKET_QUEUE         = 'Merchant';
    const PAYMENT_GATEWAY_CF_PRODUCT    = 'Payment Gateway';

    const NOTIFICATION_EVENT = 'event';

    const SUBCATEGORY_CAPITAL = 'Capital';
    const QUESTION_TICKET_TYPE  = 'Question';
    const INCIDENT_TICKET_TYPE  = 'Incident';

    // Sub Categories
    const FD_SUB_CATEGORY_FUNDS_ON_HOLD             = 'Funds on hold';
    const SERVICE_CHARGEBACK_SUBCATEGORY            = 'Service Chargeback';
    const FD_SUB_CATEGORY_NEED_CLARIFICATION        = 'Need Clarification';
    const FD_SUB_CATEGORY_FRAUD_ALERTS              = 'Fraud alerts';
    const FD_SUB_CATEGORY_WEBSITE_MISMATCH          = 'Website Mismatch';
    const FD_SUB_CATEGORY_INTERNATIONAL_ENABLEMENT  = 'International Enablement';

    // Categories
    const RISK_REPORT_CATEGORY               = 'Risk Report_Merchant';
    const CHARGEBACKS_CATEGORY               = 'Chargebacks';

    const FRESHDESK_DEFAULT_PAGE_SIZE        = 30;

    //DASHBOARD URLS
    const SUPPORT_TICKET_DASHBOARD_URL = 'app/ticket-support/%s/%s/agent/conversation';
    const SUPPORT_TICKET_DASHBOARD_BUTTON_URL = '%s/%s/agent/conversation';

    const MERCHANT_PLUGIN_TAG       = 'plugin_merchant';
}
