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

    'hdfcUnkownIssuerNetworkData' => [
        'gateway'     => 'hdfc',
        'reason_code' => 'ISSUER_DOWN',
        'source'      => 'STATUSCAKE',
    ],

    'allGatewayIssuerHdfcNetworkVisaData' => [
        'gateway'     => 'ALL',
        'reason_code' => 'ISSUER_DOWN',
        'network'     => 'VISA',
        'issuer'      => 'HDFC',
    ],

    'allGatewayAllIssuerNetworkHdfcData' => [
        'gateway'     => 'ALL',
        'reason_code' => 'ISSUER_DOWN',
        'network'     => 'ALL',
        'issuer'      => 'HDFC',
    ],

    'hdfcNetworkAllIssuerHdfc' => [
        'gateway'     => 'hdfc',
        'reason_code' => 'ISSUER_DOWN',
        'network'     => 'ALL',
        'issuer'      => 'HDFC',
    ],

    'cybersourceDowntimeData' => [
        'gateway'     => 'cybersource',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
    ],
];
