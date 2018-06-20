<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testRblWrongFormatReconciliation' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'File contents are empty.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ReconciliationException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_RECONCILIATION,
        ],
    ],

    'testObcReconBatch' => [
        'sub_type'      => "payment",
        'gateway'       => "NetbankingObc",
        'status'        => "processed",
        'total_count'   => 1,
        'success_count' => 1,
        'failure_count' => 0,
        'attempts'      => 1,
        'entity'        => 'batch',
    ],

    'testObcReconBatchPartiallyProcessed' => [
        'sub_type'      => 'payment',
        'gateway'       => 'NetbankingObc',
        'status'        => 'partially_processed',
        'total_count'   => 1,
        'success_count' => 0,
        'failure_count' => 1,
        'attempts'      => 1,
        'entity'        => 'batch',
    ],
];
