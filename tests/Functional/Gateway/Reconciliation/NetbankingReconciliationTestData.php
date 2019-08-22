<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
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

    'testCanaraReconBatchPartiallyProcessed' => [
        'sub_type'      => 'payment',
        'gateway'       => 'NetbankingCanara',
        'status'        => 'partially_processed',
        'total_count'   => 2,
        'success_count' => 1,
        'failure_count' => 1,
        'attempts'      => 1,
        'entity'        => 'batch',
    ]
];
