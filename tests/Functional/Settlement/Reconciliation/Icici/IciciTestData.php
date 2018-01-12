<?php

use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\Status as SettlementStatus;
use RZP\Models\FundTransfer\Icici\Reconciliation\Status;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;

return [

    'matchSummaryForReconFile' => [
        'channel'               => Channel::ICICI,
        'total_count'           => 1,
        'unprocessed_count'     => 0,
    ],

    'matchSettlementAttemptForReconSuccess' => [
        'channel'           => Channel::ICICI,
        'version'           => 'V3',
        'bank_status_code'  => Status::PAID,
        'status'            => AttemptStatus::INITIATED,
    ],

    'fetchAndMatchSettlementsForReconSuccess' => [
        'channel'           => Channel::ICICI,
        'merchant_id'       => '10000000000000',
        'amount'            => 1752800,
        'fees'              => 47200,
        'tax'               => 7200,
        'failure_reason'    => null,
        'attempts'          => 1,
        'status'            => SettlementStatus::PROCESSED,
    ],

    'matchSettlementAttemptForReconFileFailure' => [
        'channel'           => Channel::ICICI,
        'version'           => 'V3',
        'bank_status_code'  => Status::CANCELLED,
        'status'            => AttemptStatus::INITIATED,
    ],

    'matchSummaryForReconFailure' => [
        'total_count'                   => 1,
        'failures_count'                => 1,
        'settlement_failure_amount'     => 1752800,
        'settlement_failure_count'      => 1,
        'settlement_failure_remarks'    => 'All settlements failed.',
    ],

    'fetchAndMatchSettlementsForReconFailure' => [
        'channel'           => Channel::ICICI,
        'merchant_id'       => '10000000000000',
        'amount'            => 1752800,
        'fees'              => 47200,
        'tax'               => 7200,
        'failure_reason'    => 'Reconciliation',
        'status'            => SettlementStatus::FAILED,
        'attempts'          => 1,
    ],

    'matchSettlementAttemptForReconEntityFailure' => [
        'channel'          => Channel::ICICI,
        'version'          => 'V3',
        'bank_status_code' => Status::CANCELLED,
        'status'           => AttemptStatus::FAILED,
        'failure_reason'   => 'Reconciliation',
    ],

    'fetchAndMatchBatchDataSettlement' => [
        'channel'           => Channel::ICICI,
        'amount'            => 1752800,
        'fees'              => 47200,
        'tax'               => 7200,
        'api_fee'           => 0,
        'gateway_fee'       => 0,
        'total_count'       => 1,
        'transaction_count' => 4,
    ],
];