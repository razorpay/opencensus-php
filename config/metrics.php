<?php

use RZP\Constants\Metric;
use RZP\Trace\Metrics\DimensionsProcessor;

return [

    'processors' => [
        DimensionsProcessor::class,
    ],

    /*
    | Label Values Whitelist: In case some label has values of high cardinality
    | we can white list here a finite set of values for which we want specific
    | monitoring. For values not in the corresponding whitelist 'other' value
    | would be used.
    */
    'whitelisted_label_values' => [
        Metric::LABEL_RZP_KEY_ID          => [
        ],

        Metric::LABEL_RZP_MERCHANT_ID     => [
        ],

        Metric::LABEL_RZP_OAUTH_CLIENT_ID => [
        ],
    ],

    'default_label_value' => Metric::LABEL_DEFAULT_VALUE,
];
