<?php

use \RZP\Models\Merchant\RazorxTreatment;

return [

    /**
     * mailgun_whitelist contains the list of templates which have been
     * explicitly whitelisted to be sent via Mailgun. For all others
     * the default email driver will be used.
     */
    'mailgun_whitelist' => [
        'emails.user.razorpayx.account_verification',
        'emails.user.account_verification',
        'emails.user.password_reset',
        'emails.user.password_change',
        'emails.user.otp_email_verify',
        'emails.downtime.update_downtime',
        'emails.downtime.create_downtime',
        'emails.downtime.resolve_downtime',
    ],

    'stork_whitelist' => [
        'emails.invoice.customer.notification'             => RazorxTreatment::API_STORK_MAIL_CUSTOMER_INVOICE,
    ],
];
