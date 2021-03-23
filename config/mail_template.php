<?php

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
        'emails.mjml.merchant.user.contact_mobile_updated',
    ],
];
