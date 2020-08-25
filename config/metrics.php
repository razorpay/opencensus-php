<?php

use App\Metrics\DimensionProcessor;
use App\Metrics\Constants;

return [
    'processors'    => [
        DimensionProcessor::class,
    ],

    'default_label_value'   => Constants::LABEL_DEFAULT_VALUE,

    'namespace'     => 'dashboard',
];
