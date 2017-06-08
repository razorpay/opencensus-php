<?php

return [
    'axisMigsAllNetworkDowntimeData' => [
        'gateway'     => 'axis_migs',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'source'      => 'other',
    ],

    'hdfcVisaDowntimeData' => [
        'gateway'     => 'hdfc',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'VISA',
        'issuer'      => 'HDFC',
    ],

    'migsAllIssuerDowntimeData' => [
        'gateway'     => 'axis_migs',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
    ],

    'cybersourceDowntimeData' => [
        'gateway'     => 'cybersource',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
    ],

    'hdfcAllNetworkAllIssuerDowntimeData' => [
        'gateway'     => 'hdfc',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
    ],

    'hdfcMastercardNetworkData' => [
        'gateway'     => 'hdfc',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'MC',
        'issuer'      => 'ALL',
    ],
];
