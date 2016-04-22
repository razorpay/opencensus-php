<?php

namespace Reconciliator;


final class Constants
{
    const VALIDATION_CONSTANTS = [
        /*
        * TODO: Add all email IDs which we are expecting the mails to come from for settlements.
        * This list should be the same as the one configured in MailGun.
        * It's being added here to ensure more robustness.
        */
        'fromEmailsFilter'        => ['prashanth.yv@razorpay.com'],
        'subjectFilter'           => [],

        // Can add more to this as and when we add converters to CSV from different file types.
        // Use https://github.com/jasonlewis/laravel.com/blob/master/application/config/mimes.php for mappings.
        'acceptedExtensionsMap' => [
            'csv'   => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values'],
            'txt'   => ['text/plain'],
            'text'  => ['text/plain'],
            'xlsx'  => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'xls'   => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'],
        ]
    ];
}
