<?php

namespace RZP\Models\Payment\Processor;

class Constants
{
    const REQUEST_TYPE                  = 'request_type';

    const REQUEST_TYPE_OTP              = 'request_type_otp';

    const REQUEST_TYPE_REDIRECT         = 'request_type_redirect';

    const ACTION                        = 'action';

    const ACTION_OTP_RESEND             = 'otp_resend';

    const UPI                           = 'upi';

    const CARD                          = 'card';

    const CREATED                       = 'created';

    const SUBSCRIPTION                  = 'subscription';

    const ADMIN_EMAIL                   = 'admin_email';
    const USER_EMAIL                    = 'user_email';
    const META_DATA                     = 'meta_data';
    const INITIATOR_EMAIL_ID            = 'initiator_email_id';
    const IS_CRON                       = 'is_cron';
    const IS_DASHBOARD_APP              = 'is_dashboard_app';
    const ROUTE_NAME                    = 'route_name';
    const IS_BATCH                      = 'is_batch';
    const CREATOR_ID                    = 'creator_id';
    const CREATOR_TYPE                  = 'creator_type';
    const IS_PAYMENT_CAPTURED           = 'is_payment_captured';
    const IS_ADMIN_AUTH                 = 'is_admin_auth';
    const IS_PAYMENT_AMOUNT_MISMATCH    = 'is_payment_amount_mismatch';
}
