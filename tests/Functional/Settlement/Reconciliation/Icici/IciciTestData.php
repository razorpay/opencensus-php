<?php

use RZP\Models\FundTransfer\Icici\Reconciliation\Status;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;

return [

    'matchSummaryForReconFile' => [
        'channel'                       => Channel::ICICI,
        'total_count'                   => 1,
        'unprocessed_count'             => 0,
    ],

    'matchSettlementAttemptForReconSuccess' => [
        'channel'           => Channel::ICICI,
        'version'           => 'V3',
        'bank_status_code'  => Status::PAID,
        'status'            => AttemptStatus::INITIATED,
        'failure_reason'    => null,
    ],
];