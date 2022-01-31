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
        'emails.payment.merchant'                          => RazorxTreatment::API_STORK_MAIL_PAYMENT_CAPTURE,
        'emails.mjml.merchant.user.contact_mobile_updated' => RazorxTreatment::API_STORK_MAIL_CONTACT_MOBILE_UPDATED,
        'emails.mjml.customer.payment'                     => RazorxTreatment::API_STORK_MAIL_CUSTOMER_PAYMENT,
        'emails.invoice.customer.notification'             => RazorxTreatment::API_STORK_MAIL_CUSTOMER_INVOICE,
        'emails.mjml.customer.failure'                     => RazorxTreatment::API_STORK_MAIL_PAYMENT_FAILURE,
        'emails.payment.merchant_failure'                  => RazorxTreatment::API_STORK_MAIL_PAYMENT_FAILURE,
        'emails.payment.failed_to_authorized'              => RazorxTreatment::API_STORK_MAIL_PAYMENT_FAILURE,
    ],
];
