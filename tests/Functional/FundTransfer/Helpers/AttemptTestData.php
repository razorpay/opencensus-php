<?php

use RZP\Models\Settlement\Channel;
use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Models\Settlement\Status as SettlementStatus;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status as RblStatus;
use RZP\Models\FundTransfer\Icici\Reconciliation\Status as IciciStatus;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Status as KotakStatus;
use RZP\Models\FundTransfer\Hdfc\Reconciliation\Status as HdfcStatus;
use RZP\Models\FundTransfer\Axis\Reconciliation\Status as AxisStatus;

return [
    'testFileCreationSettlement' => [
        'amount'            => 1952600,
        'fees'              => 47200,
        'tax'               => 7200,
        'processed_amount'  => 0,
        'processed_count'   => 0,
        'total_count'       => 1,
        'transaction_count' => 4,
        'type'              => 'settlement',
    ],

    'testFileCreationPayout' => [
        'amount'            => 1000,
        'fees'              => 602,
        'tax'               => 92,
        'processed_amount'  => 0,
        'processed_count'   => 0,
        'total_count'       => 1,
        'transaction_count' => 1,
        'type'              => 'payout',
    ],

    'matchAttemptForReconSuccessKotak' => [
        'version'           => 'V3',
        'bank_status_code'  => 'P',
        'status'            => AttemptStatus::INITIATED,
        'remarks'           => '',
        'failure_reason'    => null,
    ],

    'matchAttemptForReconSuccessIcici' => [
        'version'           => 'V3',
        'bank_status_code'  => 'Paid',
        'status'            => AttemptStatus::INITIATED,
        'failure_reason'    => null,
    ],

    'matchAttemptForReconSuccessHdfc' => [
        'version'           => 'V3',
        'bank_status_code'  => 'E',
        'status'            => AttemptStatus::INITIATED,
        'failure_reason'    => null,
    ],

    'matchAttemptForReconSuccessAxis' => [
        'version'           => 'V3',
        'bank_status_code'  => 'Settled',
        'status'            => AttemptStatus::INITIATED,
        'failure_reason'    => null,
    ],

    'matchAttemptForReconSuccessRbl' => [
        'version'           => 'V3',
        'bank_status_code'  => 'SUCCESS',
        'status'            => AttemptStatus::INITIATED,
        'failure_reason'    => null,
    ],

    'matchAttemptForReconFailureRbl' => [
        'version'           => 'V3',
        'bank_status_code'  => 'Failure',
        'status'            => AttemptStatus::INITIATED,
        'failure_reason'    => null,
    ],

    'matchSummaryForReconFile' => [
        'total_count'           => 1,
        'unprocessed_count'     => 0,
    ],

    'matchAttemptForReconFailureKotak' => [
        'channel'           => Channel::KOTAK,
        'version'           => 'V3',
        'bank_status_code'  => KotakStatus::PROCESSED,
        'status'            => AttemptStatus::INITIATED,
        'remarks'           => 'Some failure.',
    ],

    'matchAttemptForReconFailureIcici' => [
        'channel'           => Channel::ICICI,
        'version'           => 'V3',
        'bank_status_code'  => IciciStatus::CANCELLED,
        'status'            => AttemptStatus::INITIATED,
    ],

    'matchAttemptForReconFailureHdfc' => [
        'channel'           => Channel::HDFC,
        'version'           => 'V3',
        'bank_status_code'  => HdfcStatus::CANCELLED,
        'status'            => AttemptStatus::INITIATED,
    ],

    'matchAttemptForReconFailureAxis' => [
        'channel'           => Channel::AXIS,
        'version'           => 'V3',
        'bank_status_code'  => AxisStatus::REJECTED,
        'status'            => AttemptStatus::INITIATED,
    ],

    'fetchAndMatchReconSuccessForPayout' => [
        'amount'            => 1000,
        'fees'              => 602,
        'tax'               => 92,
        'failure_reason'    => null,
        'status'            => PayoutStatus::PROCESSED,
    ],

    'fetchAndMatchReconSuccessForSettlement' => [
        'amount'            => 1952600,
        'fees'              => 47200,
        'tax'               => 7200,
        'failure_reason'    => null,
        'attempts'          => 1,
        'status'            => SettlementStatus::PROCESSED,
    ],

    'matchSummaryForReconFailure' => [
        'total_count'                   => 1,
        'failures_count'                => 1,
        'settlement_failure_amount'     => 1952600,
        'settlement_failure_count'      => 1,
        'settlement_failure_remarks'    => 'All settlements failed.',
    ],

    'fetchAndMatchSettlementsForReconFailure' => [
        'amount'            => 1952600,
        'fees'              => 47200,
        'tax'               => 7200,
        'failure_reason'    => 'Reconciliation',
        'status'            => SettlementStatus::FAILED,
        'attempts'          => 1,
    ],

    'matchSettlementAttemptForReconFailureKotak' => [
        'channel'          => Channel::KOTAK,
        'version'          => 'V3',
        'bank_status_code' => KotakStatus::PROCESSED,
        'status'           => AttemptStatus::FAILED,
        'remarks'          => 'Some failure.',
        'failure_reason'   => 'Reconciliation',
    ],

    'matchSettlementAttemptForReconFailureIcici' => [
        'channel'          => Channel::ICICI,
        'version'          => 'V3',
        'bank_status_code' => IciciStatus::CANCELLED,
        'status'           => AttemptStatus::FAILED,
        'failure_reason'   => 'Reconciliation',
    ],

    'matchSettlementAttemptForReconFailureHdfc' => [
        'channel'          => Channel::HDFC,
        'version'          => 'V3',
        'bank_status_code' => HdfcStatus::CANCELLED,
        'status'           => AttemptStatus::FAILED,
        'failure_reason'   => 'Reconciliation',
    ],

    'matchSettlementAttemptForReconFailureAxis' => [
        'channel'          => Channel::AXIS,
        'version'          => 'V3',
        'bank_status_code' => AxisStatus::REJECTED,
        'status'           => AttemptStatus::FAILED,
        'failure_reason'   => 'Reconciliation',
    ],

    'matchSettlementAttemptForReconFailureRbl' => [
        'channel'          => Channel::RBL,
        'version'          => 'V3',
        'bank_status_code' => RblStatus::FAILURE,
        'status'           => AttemptStatus::FAILED,
        'failure_reason'   => 'Reconciliation',
    ],

    'testRetrySettlement' => [
        'attempts'                  => 2,
        'batch_fund_transfer_id'    => null,
        'remarks'                   => '',
        'status'                    => SettlementStatus::CREATED,
        'failure_reason'            => null,
        'utr'                       => null,
    ],

    'matchBatchReconcileDataForSettlement' => [
        'processed_amount'  => 1952600,
        'processed_count'   => 1,
        'total_count'       => 1,
    ],

    'matchBatchReconcileDataForPayout' => [
        'processed_amount'  => 1000,
        'processed_count'   => 1,
        'total_count'       => 1,
    ],
];

