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

    'hdfcAllNetworkAllIssuerAllAcquirerDowntimeData' => [
        'gateway'     => 'hdfc',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
        'acquirer'    => 'ALL',
    ],

    'hdfcAllNetworkAllIssuerHdfcAcquirerDowntimeData' => [
        'gateway'     => 'hdfc',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
        'acquirer'    => 'hdfc',
    ],

    'allGatewayAllIssuerAllNetworkHdfcAcquirerData' => [
        'gateway'     => 'ALL',
        'reason_code' => 'LOW_SUCCESS_RATE',
        'network'     => 'ALL',
        'issuer'      => 'ALL',
        'acquirer'    => 'hdfc',
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

    'testDowntimeSortingForVajraUpiWebHook' => [
        'request' => [
            'content' => [
                'evalMatches' => [],
                'message'  => '',
                'ruleId'   => 242,
                'ruleName' => 'Total Success  Rate is less then 80',
                'ruleUrl'  => 'https://vajra.razorpay.com/d/XmyC-WYmz/prod-payments-success-rate?fullscreen=true&edit=true&tab=alert&panelId=2&orgId=1',
                'state'    => 'alerting',
                'title'    => '[Alerting] Total Success  Rate is less then 80',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/vajra'
        ],
        'response' => [
            'content' => [
                [
                    'scheduled'   => false,
                    'partial'     => false,
                    'source'      => 'VAJRA',
                    'reason_code' => 'LOW_SUCCESS_RATE',
                    'method'      => 'upi',
                    'gateway'     => 'upi_icici',
                ]
            ]
        ],
    ],
];
