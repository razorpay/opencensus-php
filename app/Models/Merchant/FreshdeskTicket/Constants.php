<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

class Constants
{
    // Query params
    const PAGE     = 'page';
    const QUERY    = 'query';
    const STATUS   = 'status';
    const PER_PAGE = 'per_page';

    const CATEGORY                  = 'cf_requester_category';
    const SUB_CATEGORY              = 'cf_requestor_subcategory';
    const CUSTOM_FIELDS             = 'custom_fields';
    const TRANSACTION_ID            = 'cf_transaction_id';
    const PAYMENT_ID                = 'cf_razorpay_payment_id';
    const REFUND_ID                 = 'cf_refund_id';
    const ORDER_ID                  = 'cf_order_id';
    const MERCHANT_ID               = 'cf_merchant_id';
    const PAYMENT_CUSTOMER_EMAIL    = 'cf_payment_email';
    const PAYMENT_CUSTOMER_PHONE    = 'cf_payment_phone';

    //Flows
    const CUSTOMER = 'Customer';
    const PARTNER  = 'Partner';

    // ID Types
    const PAYMENT       = 'payment';
    const REFUND        = 'refund';
    const ORDER         = 'order';
    const TRANSACTION   = 'transaction';

    const OTP                         = 'otp';
    const OTP_SOURCE                  = 'source';
    const OTP_CONTEXT                 = 'context';
    const OTP_RECEIVER                = 'receiver';
    const OTP_CUSTOMER_SUPPORT_SOURCE = 'customer_support';

    // Results
    const TOTAL   = 'total';
    const RESULTS = 'results';

    const USER_ID    = 'user_id';
    const TICKET_ID  = 'ticket_id';
    const CREATED_AT = 'created_at';

    const FD_INSTANCE      = 'fd_instance';
    const FRESHDESK_CLIENT = 'freshdesk_client';

    // Custom field prefix
    const MERCHANT_DASHBOARD = 'merchant_dashboard';

    // Instances & urls
    const URL    = 'url';
    const RZP    = 'rzp';
    const URL2   = 'url2';
    const RZPSOL = 'rzpsol';

    // Active tickets and work in progress tickets
    const ACTIVE_STATUSES = [2, 3, 8, 9, 10, 11];

    // Awaiting merchant's response
    const MERCHANT_ACTION_STATUSES = [6];

    // Grievance related constants
    const GRIEVANCE_TAGS = ['new_grievance_raised'];

    //Freshdesk  Ticket Fields
    const TICKET_PRIORITY   = 'priority';
    const TICKET_STATUS     = 'status';
    const TICKET_TAGS       = 'tags';

    const ATTACHMENTS       = 'attachments';
    const BODY              = 'body';
    const DESCRIPTION       = 'description';

}
