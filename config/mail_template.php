<?php

return [

    /**
     * ses_whitelist contains the list of templates which have been
     * explicitly whitelisted to be sent via SES. For all others
     * the default email driver will be used.
     */
    'ses_whitelist' => [
        'emails.payment.customer',
        'emails.payment.merchant_failure',
        'emails.payment.merchant',
        'emails.webhook.deactivate',
    ],

];
