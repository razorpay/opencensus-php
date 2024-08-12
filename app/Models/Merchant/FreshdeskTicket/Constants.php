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
    const CONTACT = 'contact';
    const DUE_BY = 'due_by';

    const DEFAULT_ACTIVATION_STATUS = 'undefined';
    const DEFAULT_POS_ACTIVATION_STATUS = 'under_review';

    const CC_EMAILS    = 'cc_emails';
    const REQUESTER_ID = 'requester_id';

    const CUSTOM_FIELDS = 'custom_fields';
    // custom fields
    const CF_REQUESTOR_CATEGORY                = 'cf_requester_category';
    const CF_REQUESTOR_SUBCATEGORY             = 'cf_requestor_subcategory';
    const CF_SUBCATEGORY                       = 'cf_subcategory';
    const CF_REQUESTOR_ITEM                    = 'cf_requester_item';
    const CF_CATEGORY                          = 'cf_category';
    const CF_NEW_CATEGORY                      = 'cf_new_category';
    const CF_NEW_SUBCATEGORY                   = 'cf_new_sub_category';
    const CF_NEW_REQUESTOR_CATEGORY            = 'cf_new_requester_category';
    const CF_NEW_REQUESTOR_SUBCATEGORY         = 'cf_new_requester_sub_category';
    const CF_NEW_REQUESTOR_ITEM                = 'cf_new_requester_item';
    const TRANSACTION_ID                       = 'cf_transaction_id';
    const PAYMENT_ID                           = 'cf_razorpay_payment_id';
    const REFUND_ID                            = 'cf_refund_id';
    const ORDER_ID                             = 'cf_order_id';
    const CF_MERCHANT_ID                       = 'cf_merchant_id';
    const PAYMENT_CUSTOMER_EMAIL               = 'cf_payment_email';
    const PAYMENT_CUSTOMER_PHONE               = 'cf_payment_phone';
    const CF_MERCHANT_ID_DASHBOARD             = 'cf_merchant_id_dashboard';
    const CF_TICKET_QUEUE                      = 'cf_ticket_queue';
    const CF_REQUESTER_CONTACT_RAZORPAY_REASON = 'cf_requester_contact_razorpay_reason';
    const CF_PRODUCT                           = 'cf_product';
    const CF_QUERY                             = 'cf_query';
    const CF_CREATED_BY                        = 'cf_created_by';
    const CF_CREATION_SOURCE                   = 'cf_creation_source';
    const CF_MERCHANT_ACTIVATION_STATUS        = 'cf_merchant_activation_status';
    const CF_WORKFLOW_ID                       = 'cf_workflow_id';
    const CF_MULTIPRODUCT_USER_P0              = 'cf_multiproduct_user_p0';

    const MULTI_PRODUCT_USER                   = 'MP User P0';
    const POS_ONBOARDING_TYPE                  = 'POS';

    const ID                                   = 'id';
    const CF_WEBSITE_URL                       = 'cf_website_url';

    const EMAIL_SOURCE_DISPUTES_TAG            = 'email_source_Disputes';

    const EMAIL_CONFIG_ID    = 'email_config_id';

    const AGENT              = 'agent';
    const AGENT_ID           = 'agent_id';
    const FRESHDESK_AGENT_ID = 'freshdesk_agent_id';
    const AGENT_NAME         = 'agent_name';


    //Requestor category
    const RAZORPAY           = 'Razorpay';

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
    const SMS_OTP_ASSISTANT_NODAL_GRIEVANCE     = 'sms.grievance_flow';
    const G_RECAPTCHA_RESPONSE                  = 'g_recaptcha_response';

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

    // Instances
    const RZP       = 'rzp';
    const RZPIND    = 'rzpind';
    const RZPCAP    = 'rzpcap';
    const RZPX      = 'rzpx';
    const RZPSOL    = 'rzpsol';
    const EZETAPIND = 'Ezetap';

    // URLs
    const URLIND        = 'urlind';

    const URLMY         = 'urlmy';
    const RZPMY         = 'rzpmy';
    const URLCAP        = 'urlcap';
    const URLX          = 'urlx';
    const URL2          = 'url2';
    const URL           = 'url';
    const URL_EZETAP    = 'url_ezetap';

    const ONBOARDING_TYPE = "onboarding_type";

    const FRESHDESK_INSTANCES = [
        Type::SUPPORT_DASHBOARD_X => [self::RZPX => self::URLX,
            self::RZPCAP => self::URLCAP],
        Type::SUPPORT_DASHBOARD => [self::RZPIND => self::URLIND,
            self::RZPCAP => self::URLCAP,
            self::RZPMY => self::URLMY,
            self::EZETAPIND => self::URL_EZETAP]
    ];

    const URL_VS_INSTANCES = [
        self::URLX       => self::RZPX,
        self::URLCAP     => self::RZPCAP,
        self::URLIND     => self::RZPIND,
        self::URLMY      => self::RZPMY,
        self::URL        => self::RZP,
        self::URL_EZETAP => self::EZETAPIND
    ];

    const FRESHDESK_URL_LIST = [self::URL, self::URLIND, self::URLMY, self::URLCAP, self::URLX, self::URL_EZETAP];

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
    const CUSTOM_FIELDS_LIST_FOR_QUERY = [self::CF_REQUESTOR_CATEGORY, self::CF_REQUESTOR_SUBCATEGORY , self::CF_REQUESTOR_ITEM, self::CF_CREATED_BY, self::CF_WORKFLOW_ID,
                                          self::CF_NEW_REQUESTOR_CATEGORY, self::CF_NEW_REQUESTOR_SUBCATEGORY, self::CF_NEW_REQUESTOR_ITEM];

    // Requester items allowed for whatsapp notification
    const TICKET_NEW_REQUESTER_ITEMS_FOR_WA_NOTIFICATION = ['Reports', 'Credits Enquiry', 'Pricing Enquiry', 'Email Address Update', 'Update GST', 'FIRC Request', 'Add additional website', 'Add new website', 'Product/Feature assistance', 'Bank account change', 'Website replacement', 'Refund credits', 'Transaction Related Issues', 'Settlement related issue', 'Cards', 'Netbanking', 'Wallet', 'EMI', 'UPI'];

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

    const MAX_OPEN_TICKETS_FOR_MERCHANT = 10;

    // Webhook response
    const SUCCESS = 'success';

    // razorx flags
    const RAZORX_FLAG_SHOULD_MIGRATE_FRESHDESK_IND_MERCHANT           = 'should_migrate_freshdesk_ind_merchant';
    const RAZORX_FLAG_FRESHDESK_CUSTOMER_TICKET_CREATION_SERVER_PICK  = 'Freshdesk_Customer_Ticket_Creation_Server_Pick';
    const RAZORX_FLAG_TO_ADD_PLUGIN_MERCHANT_TAG                      = 'freshdesk_add_plugin_merchant_tag';
    const RAZORX_PA_PG_NODAL_STRUCTURE                                = 'pa_pg_nodal_structure';
    const RAZORX_FLAG_TO_LIMIT_NO_OF_OPEN_FRESHDESK_TICKETS           = 'limit_no_of_open_freshdesk_tickets';

    const CAPITAL_MIGRATION_EXPERIMENT_ID = 'app.capital_migration_experiment_id';
    const ENABLE                          = 'enable';

    // values for account recovery flow
    const MERCHANT       = 'Merchant';
    const ACCOUNT_LOCKED = 'Account Locked';

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
    const CHARGEBACKS_SUBCATEGORY                   = 'Chargebacks';


    // Categories
    const RISK_REPORT_CATEGORY = 'Risk Report_Merchant';
    const CHARGEBACKS_CATEGORY = 'Chargebacks';

    const FRESHDESK_DEFAULT_PAGE_SIZE = 30;

    const MERCHANT_PLUGIN_TAG = 'plugin_merchant';

    /*Session Variables*/
    const EMAIL_VERIFIED        = 'email_verified';
    const SESSION_EXPIRY_IN_SEC = 60 * 20;

    const ALLOWED_CUSTOMER_KEYS = ['body', 'body_text', 'created_at', 'updated_at', 'attachments','user_id'];
    const ACTION                = 'action';
    const ASSISTANT_NODAL       = 'assistant_nodal';
    const NODAL                 = 'nodal';
    const ACCOUNT_RECOVERY      = 'account_recovery';
    const TICKET_ID_ARRAY       = 'ticket_id_array';

    const MINIMUM_DAYS_FOR_NODAL_ASSISTANT_GRIEVANCE = 10;
    const MINIMUM_DAYS_FOR_NODAL_GRIEVANCE           = 20;

    const ACTION_VS_TEMPLATE_FOR_OTP = [
        self::ASSISTANT_NODAL  => Constants::SMS_OTP_TEMPLATE_FOR_ACCOUNT_RECOVERY,
        self::ACCOUNT_RECOVERY => Constants::SMS_OTP_TEMPLATE_FOR_ACCOUNT_RECOVERY,
    ];
    const DEFAULT_CF_CATEGORY = 'New Ticket';

    //DASHBOARD URLS
    const SUPPORT_TICKET_DASHBOARD_URL = 'app/ticket-support/%s/%s/agent/conversation';
    const SUPPORT_TICKET_DASHBOARD_BUTTON_URL = '%s/%s/agent/conversation';

    const TICKET_QUEUE_INTERNAL     = 'Internal';


    const IS_PA_PG_ENABLED     = 'isPaPgEnable';

    // Ticket Creation Sources
    const FD_TICKET_CREATION_SOURCE_DASHBOARD = 'Dashboard';
    const FD_TICKET_CREATION_SOURCE_MOBILE_DASHBOARD = 'Mobile Dashboard';
    const FD_TICKET_CREATION_SOURCE_WEBVIEW_IOS = 'Webview - iOS';
    const FD_TICKET_CREATION_SOURCE_WEBVIEW_ANDROID = 'Webview - Android';
    const FD_TICKET_CREATION_SOURCE_DASHBOARD_X = 'Dashboard X';

    const VALID_FD_TICKET_CREATION_SOURCE_LIST = [
        self::FD_TICKET_CREATION_SOURCE_DASHBOARD,
        self::FD_TICKET_CREATION_SOURCE_MOBILE_DASHBOARD,
        self::FD_TICKET_CREATION_SOURCE_WEBVIEW_ANDROID,
        self::FD_TICKET_CREATION_SOURCE_WEBVIEW_IOS,
        self::FD_TICKET_CREATION_SOURCE_DASHBOARD_X,
    ];

    const ACTIVATIONS_DOCUMENT_REVIEW = "Activations: Document Review";
    const LEAD_SCORE                  = "cf_lead_score";
    const CF_CASE_TRIGGER             = "cf_case_trigger";

    const CUSTOM_FIELDS_LIST_FOR_FETCH_TICKETS = [
        self::CF_MERCHANT_ID,
        self::CF_WEBSITE_URL,
        self::CF_SUBCATEGORY,
    ];

    const RAZORPAY_ONE_MERCHANTS = array(
        "GmFC3z7gCW6UAz",
        "EpMDtCCiMh4jsM",
        "I611zkDR28Qaeg",
        "7ak7IFOqUWAujL",
        "DqSdLYSNtE6vZE",
        "DqrptaXpss2NAZ",
        "I9GAlhzT2fISMP",
        "JMmJnEicg5wGbr",
        "CoLzhRuZplgPTB",
        "4oTXxbDyGlEywv",
        "Dpa6oNgPiKorsO",
        "ErQK2AE7sl7fCn",
        "EcNlblEc34xLu6",
        "AxAj2TIwTgJU9b",
        "GP1Syatl1WJVlP",
        "H2NL7YMYlq8ORK",
        "APA3shidYaWONr",
        "A6CclRUMy6wv1i",
        "GIqvB0vRZ9DBmw",
        "JNrymGlbgxRl04",
        "JGLOinsCjcioeb",
        "CViun6ucy11rp3",
        "FESrjk0EtuPZuQ",
        "CFpcj9vG3Q0TD3",
        "ILAshNwbHR6K3n",
        "Fpuxp7iVxeTB9M",
        "D4zeggM4gLEJsU",
        "FjUd8sbEBJh4T4",
        "DuNdS5C8RhPFIj",
        "9T3NDBebARDvU0",
        "FnyMHFP7JmtFPw",
        "D29xJTKD3Lbfwv",
        "7MLLxmeOZI3ubg",
        "Aem79epGanknHP",
        "6OcIHrABZYCcsj",
        "HK7q2lLv4gpuza",
        "HbxHqUVrK9jFGZ",
        "FelOpAgIJojcBM",
        "KCyCxTBBDQsbTr",
        "JT2nvbng4pMRTo",
        "BbcGgATQ7NP3E8",
        "EtJKU5G9neOCj9",
        "6sN3L8PNVgAHTT",
        "HZdXm3WtN31PtL",
        "ImQNvnxlA1wYhI",
        "G4W67THqDvRitm",
        "KSVXCO6Gd1Yrcl",
        "IJosFOZfgVbMAM",
        "FH845aPiexcUyL",
        "B8xrolmOP8wA4x",
        "6kZQBf6TLoHVoq",
        "CNviDUGHLH5D9H",
        "BV9oWfitMdIMyd",
        "8AFDFtjNyDJK6N",
        "7f1XYocSsD8Xrm",
        "FIsflzBBaAX9Tj",
        "ASMbUeTQAPQ90V",
        "GFchetiok67cXA",
        "B564Gd8DrCUrFn",
        "HFq6RshByK3nX0",
        "7rJT0As47hC1LG",
        "EzUGtY7pnU12Vt",
        "D4UickFXo2vz5h",
        "BSuMK9jcQb7Mvo",
        "HGeqocvEnE6Ovt",
        "GLYSM4cnE9I6xc",
        "FbJkwpi0EhlUNe",
        "GcdtV7Jcc6QsvL",
        "IjFMTfDhAWvHrQ",
        "HqVjVuDqQxYp83",
        "7oyIgMBnJOG8xg",
        "F8W0x3zFB3BXov",
        "B95QcZQnhWsjUd",
        "Hn7xGdwnhv9Cid",
        "D6xgGesPTY73Uh",
        "GwmW823WwiviZl",
        "DBprEIKiwAIQ2c",
        "GIPCXBGBM15TRr",
        "JZjmIuL9N355Qa",
        "ETC24h2TtNve2T",
        "Dk8zkzKP5Q8gf4",
        "H96l8gKtew2azY",
        "AMPtNdWfqpWFE2",
        "B2JVFC0INoR47s",
        "JwPSY5BUCgo5Hp",
        "GMRWnEQFf2sS7Y",
        "DJCvjkU6QbElFe",
        "J1rTWZUWmgNzSW",
        "DSeoPhPQAE1D6b",
        "D6tRpoOi2vvOF1",
        "96T6bizAZodWZ5",
        "GA3rTCbJqkgXn0",
        "ELJ1ZBZQTX4ll0",
        "J6aAgN0jF72fKb",
        "HFnjXNI7JSf294",
        "EkeEOEXUMKjDvg",
        "Inxrw86YTEwhre",
        "8n369BAMSz2GMm",
        "Bnp6P4LBqU4TG3",
        "GL9gUzJo2oaaZJ",
        "JlKRuTlbmFhNvo",
        "FopgLHiMahqW6K",
        "JCYcqp4AUOO62P",
        "I2wbNFh1Ob7QYN",
        "E19KbJNRciCgaP",
        "9si35a54kIJ6WV",
        "FeZAO9EQhSMj7Y",
        "ERhfGDGrfM5eRN",
        "EF2ArnDqJofDgg",
        "GMfJNUyD4y3lwa",
        "HWhlxXtZannjXO",
        "DLziPzWtzPLN7e",
        "GRxJNJp1B1DcDJ",
        "CHWP5EOW49Y33v",
        "JvyMtn1BxLZXUy",
        "K1v9OvHU2doIhO",
        "DzbppS5QhagL6o",
        "DJNlPP9GlerbMQ",
        "DCSXv9xjjpqkcw",
        "CskxvYfEl1QsIb",
        "Juq2kJYkMyQkOJ",
        "El0E7gaivmEqe1",
        "JUGgDpl81uP79d",
        "FKy5dLolXQNAn8",
        "J5i4wFF86Uo1up",
        "IGiElCJDwTXNys",
        "Jh1TX5tdY4gT1l",
        "F1eaKAQidy9d75",
        "Jk47rcHG3F5s2R",
        "AynC5XjU5KypOy",
        "DpL2BYmBd424Ou",
        "GcFRZydZMgqpoN",
        "JV0Z4UsP1VnmVU",
        "I9vOXEBx9bS4DJ",
        "4dU6VbPoN6JLIY",
        "K30v4KOWjfBiJl",
        "Du3ZH7QT3t8Td7",
        "FcnfLWe4aCoWQX",
        "AdO4ewODkYxzVm",
        "E7cgv00EpzNEPF",
        "Ebb0RruRAAxC6U",
        "HkBMSyQUhvsSBN",
        "IWwkvnW7tqRyQU",
        "FtSg1MR15KciyQ",
        "FboArCRgy1Eh2i",
        "DdHIMffZ5IATIf",
        "FmkpcOejINrfwO",
        "GFKnaTZBJmBzYx",
        "J8pedLsO7mLrEY",
        "FGN9xlSvOeQVFM",
        "64Vl4ByKPl2v4H",
        "FgnJ9iZkkKiC5N",
        "F5bkbkm5e1x5ZF",
        "CzSXv4EOwnwowy",
        "CnCQMeEuVQ04R3",
        "6tRJ7zRKzY3hU9",
        "EuZtsUvZ8Rng7a",
        "JA7VbI6zvvHGUS",
        "CudDBMEfSU8hub",
        "5jyiD5pXst3kJk",
        "EJqvtpSxyEZNmQ",
        "CdhxBPmsSDzFw6",
        "5vnxmkWcTa3DnB",
        "DnesXMkcYqtvLV",
        "KHrQyBc77Jh0T5",
        "DA7nB6FeHgLWvH",
        "H4Dtw21WE5hXJ8",
        "BxkxX9Ul3P7EIb",
        "FlEd8rGkw2As4F",
        "GZ0y9QNgstrY8z",
        "E8TgPRuMMdaH44",
        "F7Y2DPZSkUBJ3v",
        "HZxddRNg91ugdZ",
        "CbinFVMY7TkZzX",
        "FtqcCOm7gZqhI7",
        "JSDXTPvW9mKzQC",
        "Cz1XP87eMntmSn",
        "8nWEfMZRjJIS0c",
        "9Y6zcG0hGVR3z4",
        "FPifAWXKZWG7kS",
        "87VPFqFJtcLfbl",
        "GGXB4gGhzIotyC",
        "B90upqXO22ONnF",
        "KE9pLn9twQRihX",
        "BW4G6MXUgoHJyl",
        "9h2ZbtzwkkIY2K",
        "G5IsMT3tTbVKfH",
        "9gr9TU8nqKdFXO",
        "I5ybZFQJmZXr2o",
        "GfINzOQMDqkOtF",
        "AwM9sQSFrl4U0i",
        "C7JqFVmvCwrKsj",
        "HC7MLfoN2xemhX",
        "Hy67zbJeoyWHgk",
        "HYc3ZXLGYecjFk",
        "JVpPu9Nx6iafaU",
        "7lQwEaKicH8I23",
        "Edt8yrM1vycpzU",
        "Ck01U8U62yCKNA",
        "E7qxiBIp2b0ACL",
        "HHMMAzXv5TpHT8",
        "HYOIZvTnWzz4j2",
        "HHK9YwRpsURyNS",
        "IUqW2QwpP9YxBc",
        "Bx11xlymxrmaca",
        "JX9dqp9259w3JI",
        "E7pggX3hCEWCRP",
        "IFST3ATu55HnxT",
        "Aad1fKuogIacPm",
        "Eyd6nHUZb6AE4U",
        "BxjNZq2rRNSWCm",
        "Kiu03VGpg7xuVE",
        "H8CPnMlts0CjzQ",
        "HkkNakl1ucTjC0",
        "Di7L2paJwOwWC3",
        "EaLgXhKsT4cKFy",
        "9QBtuV80wO00C0",
        "5xJdXhL5yV4V9Z",
        "FHHBwAAVWbCkS4",
        "F9tqSUh4mzphzL",
        "CzspcxwYz6KfvI",
        "HB4WZAHHATObiG",
        "JnILX1rIQp3AqG",
        "BskP7oK6KP7qku",
        "BnPAHDs0IP4RSg",
        "GWFJme52bRLqnm",
        "HkhTnOmsUpuJud",
        "HBqNPkH5RQWlFJ",
        "DukS6v3Fr8qkXp",
        "HBoOcrWhoto9Uk",
        "K4dmh8k1ALh7c1",
        "GMhlNWoPOv2nSS",
        "EI5tWJbTRKCWNt",
        "GRaMxMJ1vslp2u",
        "FDEpetFDtIpOF6",
        "IRgV41ypvbhMtW",
        "HapnRvfRVGyA4i",
        "AoWemee0CfrFpR",
        "IkvJxvOqUDo0S3",
        "7dmT0rWP3v4xHd",
        "EbETRiH0rHruO5",
        "FHmlLDW1W6qWbT",
        "JUiBk3KuosqccM",
        "GoT27jPOWDBmK9",
        "Fpwh8Zl9lmT5v9",
        "BzFWaLK0Y6Z8tc",
        "ErSzaiL9431kLb",
        "DADw21Q2FIMMKy",
        "KihiOkBiYdZ9li",
        "6ddbRuUZoa6VyL",
        "IBebO3rcY3VmSa",
        "HyrSV7Bo3pSBh8",
        "DjIgHtMswncda8",
        "CIh0e1A7cA9CjF",
        "G0AXR3E8GmdldM",
        "CIWzhIJt6QWk9a",
        "AsSlzovv23CDfR",
        "FezuC8X0Cy8Nud",
        "K4i1ccODPOCW4a",
        "CNRlVNc5fXhCCn",
        "EyUdEerwBNJ3Lb",
        "E6L4JDtNZmPDCC",
        "Ew7tObrmxWSRrI",
        "FGNrvrBUx9kLIv",
        "CkrBSgo8BKUy1f",
        "Fr9VyhZBY01Rnq",
        "HhvJheVeSJJ2ys",
        "7GTEbjJ0Ui4wMN",
        "GkSHw8q1iN50Sa",
        "DSJbsZ9dJaYX0H",
        "46mJwf5IwYCHAa",
        "C0ZflwqEMZkef2",
        "3QfrnNBqzU4wHq",
        "I6ywP3PjrC72iv",
        "9ALAZjRt2QW4cg",
        "IKFqZy7zEshVoQ",
        "DOUTZG7TeFKQzK",
        "EAymJNmSTQ8olA",
        "CVnvCiaGuLvpqz",
        "JMO35iSTXn8uJO",
        "INmSeldc63QJ9j",
        "G4upKHX7JY9qzO",
        "ADek8PWgNOdyk0",
        "7I7gvF48HA6WYg",
        "HNhkBL6iWAYRuB",
        "Bn5KinhZ9zIfWt",
        "JndcnDLnC64PHT",
        "DeAwwllIrFJ2AG",
        "GDXzNgd1QTNOjk",
        "Eef0xGdabvo7hP",
        "GIYgSvhowrdQKx",
        "FfGnUP2bEZqRu1",
        "EVj2f27rzYl8m6",
        "G7IPbB4Xm3Ia1Y",
        "GiX3OookjYRLAl",
        "IUYuIJice1VHo0",
        "DcUzoU2T8BwxyH",
        "HGg6S2seJ6UGJS",
        "FcOVk10JYHw6yn",
        "GDplv8kimIIe37",
        "ARf0uwFmXkvKWF",
        "FQAVC0015NOb0k",
        "FMDfr15kqgJcgV",
        "Idw84ThED8z7dW",
        "7I2398ynPcF0eA",
        "HO5YVix0GduHmq",
        "B7VbUbAIJ1bsA4",
        "BaPtJ6LTzeE3nF",
        "HZ6nfnqqJwnhfl",
        "DmlNX1kn2PPPpx",
        "Jt9n9Q7szgoxBJ",
        "9YtL6ts23OBev2",
        "IffPnpMYnvM669",
        "FpWGYTBq9E1Xv5",
        "BUQdWbjkkk99oI",
        "JcjwXXQkoOQaoq",
        "JxEREVa8reQULX",
        "HJ3wVaswveLR3g",
        "GqB3udmglbdWT6",
        "HSmUYyuYaJ96EU",
        "Cnpc9RQBMJLYE7",
        "Cr9k2hfiAh3VaO",
        "GuWgc2GVioGTiw",
        "DIm9rLj2KQtJCG",
        "D1KKIgNSPv8Jr0",
        "DXrORroXdz1zju",
        "KVayTb5hIH0uGK",
        "IFSHZ9djTzk9MQ",
        "DkxQB8uqhVvfwT",
        "B8l5pJblPXXsC7",
        "DlQ1GCVbyBq2jj",
        "IxKq2AbiEf8OUi",
        "Dzno5qBQfIvLee",
        "F4S1iCnWBn8ytS",
        "Hgh2ZjyyRxCUni",
        "Fx4MzZok9hjtVg",
        "ExQpfM99LGzzdC",
        "9cQu6y1ObCDN3X",
        "Jf5nBCdpqpYj8J",
        "DC4X1C1KfTD2uB",
        "HSopcZMJnCl4vN",
        "JqlbtikrvrIxaJ",
        "IMEnh1ctpXTxuz",
        "6q0BL9DgjgHdIv",
        "JDA5RPbvIyrZ3V",
        "4Gt6lQMw9vGRi5",
        "F0XvPl8wdlF0NH",
        "IVEBuVc6AMW9PG",
        "ApMzu4AFLu6kNs",
        "HWOlw4ovAJE5rS",
        "JDEGVCIvCuD1lF",
        "9vrg8LseBnxczD",
        "CwdKpduwUaqrUr",
        "CruIAoVQu3VANE",
        "CrAQwJfzre0YK6",
        "H0pIRBMYtX6iBD",
        "FYi5hRGGVsAwKn",
        "Iam9i75thSP7A1",
        "EkMa3YQt70cOoQ",
        "Day0pm4UHBCo79",
        "FznktaciHBUKxi",
        "FMZBdoENAuj26J",
        "CYZGfs94fCLUo7",
        "EvkAuYHZbMUX7U",
        "EFjk7qyF1fNL1l",
        "7ecZmIubJ98NZA",
        "AiA1zGSKglYe6K",
        "GbpI8vx7kSEeE9",
        "JpYVQPn1imCvbN",
        "HehaoblB8Wu8el",
        "JncvbDBy4y2MUj",
        "DK82v62knjwsyA",
        "CYwg3qdWAzkcJg",
        "HxCvEsAJRyxA5D",
        "DQJLIFX9rkEzU2",
        "Fzmk2ZrSDAhWMl",
        "Hah6VKSnN6GhIk",
        "GIT1JXFh6Vx2qw",
        "6N5ssOOKSLBIES",
        "CMcppKxLQCjUZE",
        "G67chnDXWyrMpQ",
        "HY2Q1AjuskkYGk",
        "EQsQT5u04QdePE",
        "H3B7HGkCSBxplX",
        "I1EPtbJakAWdx9",
        "Aeu35hI92Gr9KO",
        "ExQRBpWrZlvbRw",
        "Dkespitl0uyz9E",
        "88jVsv1Xwk5VMu",
        "FpxOX6EjXWxXQT",
        "EoVjQll2qCuoK7",
        "E7uUJpL3meGkM7",
        "H0H8axGEtuCwoM",
        "E5Q7dUy9ecQXFF",
        "9VGjn7tC1PGdrl",
        "DPcdpoxklDjerb",
        "IxOqHFP0llDVpm",
        "FAoMUezKUjGtYw",
        "922PUUD6db7Rfg",
        "HG7T3oYHtKUMCg",
        "IJnc8spJ7IMT7U",
        "BtNctWRfn4hYh8",
        "ACqYC4WpzjUkh9",
        "IywJgjz196NPVt",
        "GnDlZNrxULaNqE",
        "CpC5QIQNCf4xKH",
        "DpSPD8fe67Frr1",
        "FVIFrfi2XuQGuk",
        "FctMZgj6DrGe9c",
        "G32gRRhQ6vA7oN",
        "Idc5VaTaNGaMVU",
        "DDRBxmim0kUIw9",
        "HGY4rlQN99T6Aa",
        "HqXr4hy4WgsW9S",
        "DNDtij8TxebPBi",
        "DWMPAs4UaFvmJh",
        "Akw9dUHYo128Xm",
        "6klNIVu7hLDPXB",
        "HtoHHKG1nrXNOl",
        "Ir3TKZupRxSGw8",
        "EZEWpmyXXiplP7",
        "Cqe5BS48iSvAiA",
        "HjRAorkq4aMPvi",
        "IDbaEk2nUpMzAe",
        "DaB9vQx3UYL2cr",
        "IZhWW5g5rry0Fh",
        "DqOnjRUyeRptVG",
        "DsZEGFlPh8iF7Z",
        "Hj4rGlPCEoC2Mv",
        "BvinxUBrCDyLuY",
        "C8kbnQRmeAT5Nu",
        "8pVEOPi29b2ivc",
        "EoFLuzL9uiP3G2",
        "GLXkNtYgqBXEwS",
        "J2ab5NBsdSTnqe",
        "DBPLLC6fGrWE3R",
        "BUOPLc8zVITWGR",
        "FwvOg6NoyH9xCL",
        "AjlZQIvnatSFi7",
        "929lLYKAI6JBp7",
        "8gML9kBUnGGJ9i",
        "IA0cPjHV8xH2J8",
        "KNcVeqhjNz8UV6",
        "JhfqAxXRsyAFeB",
        "FwyLMwwVHbvwUR",
        "Jv4T9wyVZzog4l",
        "FD3nlGLgiqRBhP",
        "FwIgEYx2xs7qWH",
        "9lhgriVbkRAsYI",
        "Bt058A4nnUmr9n",
        "9FYaA9xYhCjEk8",
        "FdePWxKtzAqxD8",
        "GPnV3AMw5oyCnG",
        "J7G8GPabpuT2Dg",
        "Gis4StUNu4Rc5C",
        "CVKwQc7fLKuLqT",
        "BCbKzrEt97WADa",
        "JGxcNGlqDPFdNK",
        "FX3VXKw1Z2EHbs",
        "HZ6r4wtTLeAX70",
        "F009U3GTEnWPKK",
        "I9sutTWByaIJKD",
        "FIH3EtzcTDPjmL",
        "ESnbmJgOzSym1I",
        "G2w0X5Fa7NgPDW",
        "GOpGEKmumrFbUC",
        "CjCoFDWSd9Ezx7",
        "8K4v0EqHDl342o",
        "BaOgS8Su5uG94z",
        "E2FiuaI7KYFfZG",
        "GXvdpZ4pVfm0tf",
        "GoSjL264tUBgzK",
        "CwMqVkQiPUF9gZ",
        "HpNfzqAGIiChpZ",
        "4xcFPHnOqL3URQ",
        "DZUtAIGfE3JJRx",
        "G4bJf1Gqg9gBIZ",
        "87vD0TvQkVH8ub",
        "Chi3IyGOuEoD1T",
        "FG27uZGWv8fUHp",
        "JqQb3PlUeOBnrE",
        "HjtKcrffVqJpWP",
        "Eckv4LUV1PQJky",
        "9N6rRhKjQDUMnn",
        "FBZftClq7omC5j",
        "CTi0PBK1qIIPTF",
        "CkpEkD0fNhqClX",
        "H2phb9kDHcLnY6",
        "9N2BIh7AF9Ztom",
        "EdA1ZMOGqD0C40",
        "DHdTIljuZHH43S",
        "CcWIXZGAI2sH7P",
        "FN0cLuXp0isIOJ",
        "GE5DSywAEvDeT1",
        "GrBaCcupzktJ0h",
        "EKrJUjM7ID3ndJ",
        "HmYCZksO6t92m8",
        "GI4t0jIVNystqK",
        "E1DXZHZnDegwAI",
        "FLQMdDJAc7VEQV",
        "IgnQ121FrGE7hj",
        "EZuoXrBs3Ax2J5",
        "CmnFH0j8505YgT",
        "Dq1Jah4WlPL0qX",
        "DRA8pvry1C4oTC",
        "EjVxgGR8XuJ2Hk",
        "EQ429y09rIM2Ws",
        "FGmEEx9rbF5e8F",
        "FJoYrMX7WNIC1X",
        "GJL3oOcphEZKCk",
        "GwKl7TCo0HdCZR",
        "I0n9EvljLBUWqN",
        "Ju2oLfc7mpoK0Y",
        "JWOhiDzI1AfUCS",
        "Kb86PrdlM26Wf5",
        "KjPAo41feOMl8g",
        "ItRg2N43wJ0cOL",
        "2qebZpMatnm5MD",
        "5IXXDp7kTi2BtJ",
        "5UVod1WR5sDUZo",
        "6X3Le0BVBq4n5E",
        "6i7TBRuDZH25cZ",
        "6lUN4gwUrIDfdk",
        "7ETTbsAdEeHyYE",
        "7Qk9dTHRjG6VyR",
        "7TI92Uvnvz70hy",
        "7WeNQq5WsOCu6W",
        "7z8W3XEYXZDeRr",
        "8Fq3COnP7m2PXP",
        "8oHi9q4ZxUGKUh",
        "8pjlhRnyEqFofy",
        "9DBUEMHlUL2sH3",
        "9IdbkKMeR5n7Iq",
        "9IkCRdN0D4dran",
        "9Mm2XPeOWI0uyu",
        "9bdOUz7dYgXstp",
        "9ffDZ4rzNqU5tx",
        "9hFkguatLAD5WJ",
        "9qoMFfIN5r4C51",
        "9s0qnbgEuMLIyI",
        "A2zNubjpKdSmdB",
        "ABhohukcrp6L5R",
        "ACGhFdYoaaTjMD",
        "ADh2kavsVhZq3Z",
        "APa2yzB8Yq1YIH",
        "AisofeG4BcQddZ",
        "Aks7MykSTAVlEL",
        "AlHMnwMa5KEeiQ",
        "B1ZsGi9Rc2Sjru",
        "B7zGcPP646pezN",
        "B81BnSV0XgUefV",
        "BADeZFDX9xx045",
        "BJ2LmtgRVZMd6F",
        "BTg430VswZfYxO",
        "BVIrcDSDcXzp9K",
        "BYjNJgttmfnADl",
        "Bn5zftbMXJ3qtM",
        "Bsb6pyMLCftbdZ",
        "C7GnThHUzoxttX",
        "C8lFxGCbaCa47L",
        "CL4jvA2gnhoXzM",
        "CLPuFc2czTdfuk",
        "CPnrGKls7IqPMg",
        "CPqn0ssnu6E4ib",
        "CSkHiRj083qhnP",
        "Cc5vqxG87HgHX0",
        "CeqBfwBG4Ji1DQ",
        "CkSAwgHoTe2ilR",
        "CsLLv4ff7NNboW",
        "CuJvjsjxProwUb",
        "CzrPEDCVxiL7tw",
        "D0NJGhDLCRuLd8",
        "D2zSNqP1fozL5a",
        "D5lrTvC2WOi7s5",
        "D6e219v1pFXbQm",
        "DcdBJzkc1G0S21",
        "DdRpwYKD5b887w",
        "DlHsnkZxCIjpO1",
        "DnIZJhITVvc0FA",
        "DspUiy70VGdhOl",
        "DuoHYqW9MQxJVh",
        "DxCeP0YUR5hWMM",
        "DymvwwiXmcgvTN",
        "DzaJtQ5yXsgVCp",
        "E9q1tladeki4Cd",
        "EDz3G8tI1woE1A",
        "EFIufM4QFpa6z0",
        "EHkLz0fDPHS2F8",
        "EJmvWbPodGJ16M",
        "EKUXHTjUGbxAnt",
        "ENOkHHf1DdOtkh",
        "EUwiofcKMM4xHK",
        "EVYOKTPGRXe4i2",
        "EVaXyKNik3EPcb",
        "EdswLWWoNgepzM",
        "EedIzQXMxgCVoj",
        "EhQmg6hAbhT7J2",
        "EiJRRF70HuGTLF",
        "EiQWCKHCviytXl",
        "EoJqvdRDYpe5tT",
        "EsB2pJHL07gUSf",
        "ExJaWqIUnZrjEc",
        "EyTxT9Xpb8hRde",
        "EyxVUNKnr4Slb1",
        "F0rWZJqwSeDifm",
        "F1lfM8x4lnTI6r",
        "F62roIYmkBhK1B",
        "F85qAuENURDczF",
        "FBW4FRip3RiwvF",
        "FBzfMO0qcXnhXV",
        "FDUx3hjp3AVHVJ",
        "FGpk2lOo2DUUND",
        "FHWTWtbGbpdOjF",
        "FHXWx0W4B9loA5",
        "FHYa10hck8WnMu",
        "FIeVmykTIt3HQx",
        "FNp4UVxcaxUzlY",
        "FOJNRyqbII4lIO",
        "FPgr53ZGa42jxR",
        "FPpIODtnoP18RZ",
        "FScSSaK6EqVBsd",
        "FUXCPSyWkge1YO",
        "FVK0Yh22RqQNCC",
        "FVn8Y50CklUHKt",
        "FWy6pLGUmm8G7K",
        "FXfrPJNvW3DVGC",
        "FYtI0HKkqrGWOu",
        "FdEb6kZ9GjGP6T",
        "FhF6mokprQQXDe",
        "FhHt1ApyMdIGvm",
        "FjtBB35i3PcUX0",
        "FlSL6i3E48kw6w",
        "FmWfxLT3dcRMNj",
        "FqGdXt9oPvkFau",
        "FtdB8WXPvj9oHv",
        "FvDfioENhbjHO6",
        "Fx2o9GkTWjSU7q",
        "FyFvZAykki6ESq",
        "G1nTLNq3Xv3fBp",
        "G2EV5MsTW1H0No",
        "G3g435I6xPyWrz",
        "G6SgibVtUIJtci",
        "G7LGy2wUrewGRE",
        "G86Am0Iu7t1mWy",
        "G8ToWoGE03xFvl",
        "GA3gSM7f50uI07",
        "GAtCifs4799hen",
        "GDNNCZd6gF3yIV",
        "GDa0dFfrUKlxO8",
        "GGaY9zwC8VNaVP",
        "GIYzufMWOM86WB",
        "GIopGWYVS6oSLs",
        "GJITRQwKBrbbdb",
        "GMmpnZmA5rcrCS",
        "GNXXas9YHmrpWj",
        "GRrjNa1V0luZwL",
        "GX2tHFrVvG1fZ5",
        "GX5yVvuIiOxAtT",
        "GfKyDOExvCStkA",
        "Gi4titz7xPtEGP",
        "GjrHTas4MP3s26",
        "GoULnA9JRZRusS",
        "GumvIu9IRg2k7q",
        "GvGcwTnVfIFXeh",
        "GvwoWnJkXUwkC5",
        "GwJYNdZSsCWomb",
        "GwikW4oPIEyX0S",
        "Gwujvv9aT36NKg",
        "H2Pg8XOKEHNjCg",
        "H2l1USQFVFWqDy",
        "H4JltS8GLwnusg",
        "H6EitBXZiGhfGV",
        "H6KMauDmzE41sA",
        "HAvaGdHAQInY8R",
        "HC5y3dATF0YwKj",
        "HNFFCCzdVyib4p",
        "HPZAgB5M5xRd8A",
        "HPfFYdoHg9XFF0",
        "HRfC8IIgYLZrJ1",
        "HU0xRlsXvIff0M",
        "HUVPzOyv9BaabN",
        "HY0KLxdnpEHDUk",
        "Hd5yT059GokQJk",
        "HdwUjMop7M6xpI",
        "HejdI7Asb3c1DH",
        "HfSBML5R6QM3Gs",
        "HghJDPe2FShSRJ",
        "HjSlFLZ6AF6ppB",
        "HkbIq9owtcmdaL",
        "Hlq916cFWYoLU6",
        "HmBtgaSYaKMyj8",
        "Hn0qD9gBxrdAWo",
        "HnuHnQOi17WQzH",
        "Hod4BwliaNS6bo",
        "Hs4j5VYz0Oo3dh",
        "Hub3d8JrT1Tw4o",
        "HwsKDQAkxAUA5q",
        "I3NcU7HleVqpVe",
        "ICe9Qs0TchFKxd",
        "IELAN4p8e8iv0T",
        "IEgAmAvzmt3qHy",
        "IQAVOcJMmWh9Kr",
        "IUsAEHRBssg8oN",
        "IVL6mvfRmjNUYX",
        "IWtacWxAPXvWlk",
        "IYeJTVALr1qmlW",
        "IZe8kUeRKrQux2",
        "IaQlVsL0zJGVYU",
        "IhvifbjaivPwN9",
        "Ii3sHTiJVZU9Uu",
        "Ik762hHZ9jlMhz",
        "IkLuiiPC0ADyIU",
        "ItU3a45mQQnhMz",
        "J1Ku0woNvdfBjG",
        "JAXhr7UVPdkruc",
        "JCVn6fK9TJplRS",
        "JFEPjq66EBR3xm",
        "JIjvS7MXZfufLE",
        "JJdbOt92G27dti",
        "JNsM750pzrXO2V",
        "JOh4BuSCHYJVxi",
        "JTsVc76QcpXXy6",
        "JX1PncMw0ZFldT",
        "JcFIMWXkYT6RRO",
        "Je8D6q9EEKPVGJ",
        "JhJyMIOBKiZT6m",
        "Ji3ZIlWgOdWfx1",
        "JkPvaiDRlifQTd",
        "JnK0cYfdVQnt1d",
        "JuKZ3qZeDKYdL2",
        "Jv7M9FpEJrFYzH",
        "JzUxMoaPud5LW6",
        "K2U6hCq77GZ3Dm",
        "K3EuwQgrJ56Fgr",
        "K9aNdeEAsAGGkC",
        "KAClcAdhrjGy7c",
        "KAucEsBqBVWQmq",
        "KFPvsuPSxIPkOr",
        "KKTXt0CVGJPVKI",
        "KMYGcIs1tgZa3B",
        "KQqtT96PFazNlo",
        "KTgwy1UBITLVRk",
        "KVxEHlMcwYIDk2",
        "KWMgR4RwgXle8r",
        "KmXsHMXUwd3u5p",
        "KqznG2DUHU92ii",
        "5W75urhT0BH1o8",
        "7UKdQS2KrwBXGc",
        "Bh1BPBybP4PBGR",
        "CV2oYhruQZbnDC",
        "DvahUDNTeDfWde",
        "FHUFyBPCS8UutF",
        "GDPCT6yn17xHcx",
        "HBrReg4I0Mgye4",
        "JToVBPziEr1Vsk",
        "KgFnCw8NCivHfC",
        "GnhHRSbEozz7At",
        "B5UXrRnUQvpaJR",
        "9H42ERznEMp12Z",
        "DGRrxLsnvfdEw3",
        "IDzbAJNyEZLx9O",
        "Gky9UkwOhX0dmx",
        "Iv165FoQS1QoaN",
        "FluOZb6en1ddUH",
        "G30QgZylsOA13j",
        "FdZSeIwnkYJ0Sn",
        "BvOv0BXNWgBG1A",
        "71jbOrkBVs3Qvg",
        "KK9mm59BqfZpFs",
        "HbRArnFzWcsg1r",
        "I2qjaSczuLkVF1",
        "CScJoQy9y4ceT5",
        "8v3K3sx25RGTy2",
        "DLbRVocXjBCThK",
        "7K9CjKAEhUCDY9",
        "FUrwM61OcRiRaq",
        "Ix84vi9qKQiTSy",
        "KD06WuJvRD5R6F",
        "JCoAmq2ctwYNKS",
        "FzUBehwu5gTdgv",
        "E9s8I6Tjn5KfEn",
        "DZoYBwMwfCAh02",
        "HjrwcKwVtxgM76",
        "EpLDldJvZtOBSn",
        "Ak7kOAejZNkEJf",
        "DJgYsinY6uIkGT",
        "GjI4HoYd8jHMhA",
        "AuMl3dNeTBhTI5",
        "EvGQs26aqq5k3p",
        "4kcrHcA7wl5sWp",
        "FYtKbixyG2nVRY",
        "FJSNOxuPw2uBXW",
        "JhjzPlfbTqEEQD",
        "Fm31dSDPKglKHp",
        "C64WuYOZJhMBEN",
        "DiUj33CAisTFRp",
        "IS5JGwW2Vm19Yx",
        "DbqafUR5KcTcvP",
        "FLysATDYHFkJ7a",
        "IUuWQDxwWVGaUp",
        "GT8WZRfdZYcfBW",
        "FPOXpbWiJ17u24",
        "FvocfWHpOUAlyV",
        "DaHRyf8zcq3N38",
        "JLAHNY0CzXZHWC",
        "EwDRBvLmGBAkoc",
        "Dymj5NnRVZ76cA",
        "H9pEF1aYZ7q4tx",
        "H50y2pm1nlAoQZ",
        "Ina4stlc1FW7ht",
        "HQzcs7tjSuPylc",
        "I5vh1A21kpVYJn",
        "HLI27vOU52P4aQ",
        "CxsQyfMOl10Auv",
        "Cuhm4dExFtCh7x",
        "E4K5KCOPH6s09A",
        "JNu5jtIYxftpFa",
        "CmQNLH2ANqSAIi",
        "BfTQ2cBQhnZRlE",
        "FFTbT1rDOic71O",
        "Jwh2DBxd69MnOC",
        "BNJn2ceIZbeETy",
        "GEOvuLXgL3gqUE",
        "DsNaF2T0MASRoU",
        "Cub8bEM4yXbFc6",
        "DPVCb7mCmQFET0",
        "9T8ZrXCfo68vlx",
        "Fc9sP33CZlf8dZ",
        "CxnZ2B6t9SIVQn",
        "7N5nzEwWFz2fda",
        "GgE0wUVvabtC7i",
        "IUBl5H0FXNvSBi",
        "GIWWUkv9rbyUb7",
        "B7ECoTdzgM9uw4",
        "Dsrqa7HT8748Nu",
        "FaW4LZL5gxHhf2",
        "CAL1odqKFbwCJw",
        "F0x4yl9MiyzkDD",
        "DCahLbIhhnzEPB",
        "DGqx8RsFbRZhqE",
        "KCwDS1KMN9Sn9d",
        "D8zmQaVlRolf19",
        "G8qeAyGfL1QhEW",
        "BfA2Sz3t6fDfnc",
        "DOR7RXeJM2y25v",
        "2qavBxg3Gssugs",
        "ITPEZDAM5kwoGd",
        "AC55WhcswnfErx",
        "GG1eb2WNOaQpTI",
        "Js53RjWlamT43H",
        "JNw5vZWhmj93T5",
        "GRTrwsckq8Cew6",
        "GJbQqSxjdOjY6g",
        "JNvGEFStKJYoJU",
        "FpzKhQii2rvSW7",
        "BPhrIcdG4Chqot",
        "7akTKTEH8aGUeu",
        "DxgMIdoodsjDEn",
        "JfVaC6qZvWmkgo",
        "Gxvb3dnBwinN8s",
        "FaaKe7PzlDT9pY",
        "HTUCWoR9csXEjr",
        "J9cIiJijcLW0Ri",
        "FVghD8D8nYNkKT",
        "GmuvSKSgmghD8S",
        "HzhPPm0gwD375O",
        "Gma4m9IPDvv3bO",
        "DSiP1l18H80xl7",
        "GZIxRYYfwnquG1",
        "CNQwSQdHVZJ0D1",
        "K6ohf7MyUJeWAC",
        "G8cj4yVadFRDGQ",
        "FAIBkiWkdpZazg",
        "7h03u7N7PYIkjD",
        "BCehJ3JL17e1ib",
        "JDD0XscRlJmD81",
        "FQy7ZLFo3jFYtb",
        "Ckj5eSG3mkbWpX",
        "6zOmn8PenxJB3v",
        "EAxM77V20IxaGS",
        "Gtz8sDQ50tU1Yu",
        "KFiU5FiIQv6sSS",
        "GhhOxYF7Lbs2TG",
        "Fv76pbN0jDDdtO",
        "IheM3BFiMO96p5",
        "JRYTPdg9oliUTE",
        "GmtVpqHKPJTMCF",
        "Etrlio6flbzRY2",
        "Exo9Q59jf18l5W",
        "IEF6OJZ8XpWxhW",
        "ElKk2sBD1gFHvw",
        "ImpaAsgr6dGsZ9",
        "GVQpJqORoKnSo1",
        "Fj9lw47ZefO8VH",
        "F1gY1mBDbyGOMo",
        "G8A6XRL6SIqCZA",
        "EwgRS9Y4kZPNl6",
        "JUDBNN59tYTGfp",
        "91ineiX9eB6ouf",
        "A0dYCNUdq0cl3E",
        "8QqZAoeWacByXo",
        "ELHvTRqIlbhNCz",
        "8YQQq3wNySAeWH",
        "Gz6iBkUxhGZHCq",
        "G5hGceyHBbmyIH",
        "6WM57ccccIDYgY",
        "8aUf1PX8KjAuxj",
        "8lQYMbbw799Dpy",
        "F0bPnSUf1opgeU",
        "J7Mx7EIwcLHRNt",
        "F0azIqi3cOb7SN",
        "FYrZjvTICQEnMg",
        "FBvsLr2rUHyMDQ",
        "JGnMHnb5XmcifR",
        "HalIo5h6rwNYrf",
        "I5FFK0ErTEhW7D",
        "JmV7b2F6u2WPWm",
        "FQdYMQTrq2ox89",
        "EjPWd5GSJr8wtW",
        "F6kId6czGYhDjH",
        "FjAYQ3QZNDnvCw",
        "JP3NysmZ8kLNPq",
        "EehLoM6uLEZo1S",
        "HbZAmOprzxUtTB",
        "FugudYddIAriIN",
        "KYoLByDKc0QyFg",
        "6hhaZuE600ACqP",
        "HinzNtH3VzmOEo",
        "JZoUR6L98B0asl",
        "JHFuqsdIPYe6xR",
        "Gf1A7ZNxXiIDcA",
        "JTpYgPKcxzULWP",
        "ILbeXEWH8taJyX",
        "B9RHTG5GkCi4Gg",
        "Jd5bzNwwK3EMS3",
        "Fpa4bQnywvGReB",
        "IQBQ9ExQcFTfln",
        "7skcMmfKUJ16Xe",
        "Jc8BdXAH3slmCm",
        "CQi3k8sATmJRZx",
        "9QKxJnjBaWdsdW",
        "J2z1zLkIZd0kNX",
        "K16NXeUcQwI8s8",
        "ISWJF76Nybzv6y",
        "HHmivVh5DZhohJ",
        "KCC2NR9n0sgPmB",
        "KkZ2RbNxXkzjUZ",
        "CKE5IMx1nZxMns",
        "HQtKlw8VxVoTI5",
        "FiMeFIdPmcUmPT",
        "I0yENGYcME0C9B",
        "IZemi6UVzNQaI5",
        "DEQkxnSRQ1t2ri",
        "JkYgXkOWXVEmTF",
        "JOfInBVTt5HhlK",
        "F5XXqCIGzQzLEF",
        "FJVTSUQGph1Uwi",
        "C8w9mRmtZonY2s",
        "A1MNf0sdqcn32L",
        "ArbvIc5ndUfup6",
        "5d70xqnEHc18RF",
        "JxTxafzFRNJ7gM",
        "JX34jEa3mq2Iul",
        "FXF0h2Odd2TwKT",
        "I63YVVTvb2c2Sc",
        "F4ulo9MYGYKzoZ",
        "IOnNT0zjXj91LG",
        "Gqu3m6dP5WtrDw",
        "It54U2URhuKqfn",
        "DAcv1n4SSmk6cF",
        "Cx3J7rmA3Ew616",
        "CmL91fl6m3vTFk",
        "K3nX1lvmca2tEH",
        "IT2rCsRts2G9Ir",
        "I6OSXhhyDszn7q",
        "FUGRpHOcl2EsDH",
        "IxLUaxs7GROR2S",
        "FpUTqbLdSgbQDc",
        "7wuNU5hKXR3Bfx",
        "6erRl3In8kARcr",
        "7PvyJxLP4qHnY5",
        "DYBkfqtmgW2xFp",
        "KcLWSyTylARiAT",
        "HfYKIuliFJfd6s",
        "Dps3EBkUouZGxU",
        "IAAr8kauDaHPek",
        "Fxzv1wSckB4inE",
        "9RPqzHSQgpOSfv",
        "H2TB39H2XkWoxv",
        "GwuqdorIxEhk2M",
        "E0LN8MugTAR1VG",
        "BjuIGIjqMmiXm9",
        "FKeb0Q5mUXPkkb",
        "7SNVeNu8XvDghM",
        "CTlevCXeeAWsty",
        "JFdCSveud39UHw",
        "JYfKnmlCFvhR7M",
        "GOipDrTUd4oSlt",
        "GzCAFZ6EBKOJbI",
        "G4U8LPdao7OJ2O",
        "AjQVey8cHXzXc8",
        "Gd23XfPuziofXE",
        "KfmvS3xoaUWOPK",
        "CDydIemZeW4TSR",
        "AQPsqfJPiBG0Pz",
        "J4g1qN7dKTiGfS",
        "AdmQfsNEhb88Da",
        "FErGFSmSphnV8k",
        "JTEbABuwx123ot",
        "FgvUHRd2ht87Ll",
        "JYzUrhSLu7Ex9u",
        "GWIBNYLCZei69X",
        "Eh7pw8zm3gTyl2",
        "CqMjnlGHzU5RU6",
        "HeHopV0cKWRNV2",
        "JKnkVb4ABN8dqJ",
        "CsPF5oYFo9WFrh",
        "G3RaPHFx3oPVl8",
        "IwftdcrikMH15H",
        "HthgckVdzUVXZK",
        "Ji5kVRTn0WGSsh",
        "Hri6sgV13LOD6W",
        "HEuidkzjzRbnGY",
        "Kb8f202avuxBzi",
        "H1vVcPQI9azRA2",
        "CVMMEeel5sSAKb",
        "H6HGfUi4otpJDS",
        "IwFWVkPTzlAmej",
        "7TZ1ybsonFFEmz",
        "CDRughEi8mnWBj",
        "FTs5cA7wATsWb2",
        "ErrY6YDuV5bG1N",
        "CSe3pegBk8pGz1",
        "EYn98mXEBjTb5i",
        "J2U2VDdKFHG3Pa",
        "EYPxEhGDjdDcP9",
        "GSiIWIHtLae11y",
        "EJMWJBGJGjJlnJ",
        "KWbFqA48fPeESJ",
        "F0Vu6qzjhmxGPZ",
        "GUiMcY1Bu9ZWwI",
        "Jbn786s2g6Bda2",
        "FGK4Z9HW1qNKvz",
        "G30CWylvSuiGf6",
        "EseDyCLIqJh8ax",
        "HxLmee6TqkB0xN",
        "ItSa24YExnWCFl",
        "EpYq7nPCIz0sz3",
        "D9ppLb7n5brj9C",
        "FMECOoJtxyuRWn",
        "JndqCOMtJ2Qbwp",
        "Kj25DFRHVKyD5x",
        "Jsljt3P4Zlmy1q",
        "HHjB47H2192ClW",
        "KmdtAl1iuke4C0",
        "FN8XM27FRw3Pab",
        "Ho6OONREzCJZHe",
        "KYgSRIxOeiQinc",
        "JE4czNnJFvEMik",
        "JQeW7G0X1m3YvY",
        "KHWEDcR5iiKN4Z",
        "IHnOHFM9mihpwK",
        "KTGbq5KYTJtclw",
        "IEgjzOKLdC2f7E",
        "IRjVTKjMoufI5S",
        "IBx8U7mfBiugrD",
        "IAk3sAViEQrZ2U",
        "LVEpUaHBSiFhR0",
        "IfCPfXeL7Nz0Wu",
        "JvjZkimavyICTw",
        "KYJkGgTsbhzapa",
        "FXpmr6nM8z27dC",
        "KQsxxETkNYXnZ9",
        "Fg54fLGdKPd7Iv",
        "DWE1NifAUMcGHH",
        "KHJvwUfdf7aBlS",
        "BeDmb5urqjgJY0",
        "BkaqONd6ZRtKCa",
        "3ln38YUUd4EmrA",
        "8IyubR9X946QnN",
        "9o20RDxgKiTI0f",
        "AULWBy0TCqjHs7",
        "CPlvUSfKZBnibA",
        "D8aEBZOIF6Die4",
        "DJzg46fHbMPJgl",
        "E8IYu5tXIZL1yN",
        "EyC05SOhjRHKSV",
        "FIGTQyIyW0LT6m",
        "Glia7TWPoGexqQ",
        "H8sVhqsKESeNUs",
        "HWknlIsui1F1sI",
        "IyaNv8xpcMR9kq",
        "JgUvgzztfX5slb",
        "JlHGdXnVUxMdx8",
        "KNMpqsvX9q1f8o",
        "KNWtDyAg5Yhpt0",
        "ENLetH4qNSLLAv",
        "AOnks3EgS0JXwq"
    );
}
