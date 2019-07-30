<?php

return [
    'axisMigsAllNetworkDowntimeData' => [
        'gateway'     => 'axis_migs',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'source'      => 'other',
    ],

    'migsAllIssuerDowntimeData' => [
        'gateway'     => 'axis_migs',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
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

    'cybersourceDowntimeData' => [
        'gateway'     => 'cybersource',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
    ],
];
