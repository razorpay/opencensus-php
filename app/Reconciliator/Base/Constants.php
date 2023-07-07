<?php

namespace RZP\Reconciliator\Base;

class Constants
{
    const RECON_PUBLIC_DESCRIPTIONS = [

        InfoCode::RECONCILED                    => 'Reconciled Successfully',

        InfoCode::ALREADY_RECONCILED            => 'Already Reconciled',

        InfoCode::RECON_FAILED                  => 'Recon failed',

        InfoCode::RECON_UNPROCESSED_SUCCESS     => 'Row has not been considered for reconciliation',

        InfoCode::PAYMENT_ID_NOT_FOUND          => 'Payment ID could not be found for the MIS row',

        InfoCode::REFUND_ID_NOT_FOUND           => 'Refund ID could not be found for the MIS row',

        InfoCode::REFUND_ID_NOT_AS_EXPECTED     => 'Refund ID being sent in the file is not as expected',

        InfoCode::PAYMENT_ID_NOT_AS_EXPECTED    => 'Payment ID being sent in the file is not as expected',

        InfoCode::MIS_FILE_PAYMENT_FAILED       => 'Payment status in the mis row is failed',

        InfoCode::MIS_FILE_REFUND_FAILED        => 'Refund status in the mis row is failed',

        InfoCode::REFUND_PAYMENT_FAILED         => 'Corresponding payment for the refund is in failed state in the system',

        InfoCode::AMOUNT_MISMATCH               => 'Amount in the recon file does not match with the one stored in API.',

        InfoCode::CURRENCY_MISMATCH             => 'Currency in the recon file does not match with the one stored in API.',

        InfoCode::PAYMENT_ABSENT                => 'Payment not found in DB',

        InfoCode::REFUND_ABSENT                 => 'Refund not found in DB',

        InfoCode::REFUND_TRANSACTION_ABSENT                     => 'Refund transaction is missing, failed to create one',

        InfoCode::REFUND_PAYMENT_ABSENT                         => 'Corresponding payment for the refund not found in DB',

        InfoCode::RECON_AUTHORIZE_FAILED_PAYMENT_UNSUCCESSFUL   => 'Failed to authorize the payment. Payment is still in failed state.',

        InfoCode::RECON_GATEWAY_FEE_OR_TAX_IS_EMPTY             => 'Either gateway fee or service tax is not given in the row',

        InfoCode::RECON_RECORD_GATEWAY_FEE_TRANSACTION_ABSENT   => 'Unable to record gateway fee because transaction is absent and could not be created',

        InfoCode::RECON_RECORD_GATEWAY_FEE_FAILED               => 'Failed to record gateway fee and/or service tax in the transaction',

        InfoCode::GATEWAY_FEE_MISMATCH                          => 'Gateway fee in the recon file does not match with the one stored in API.',

        InfoCode::GATEWAY_SERVICE_TAX_MISMATCH                  => 'Gateway service tax in the recon file does not match with the one stored in API.',

        InfoCode::RECON_UNABLE_TO_IDENTIFY_RECON_TYPE           => 'Could not identify the row as payment or refund.',

        InfoCode::RECON_INSUFFICIENT_DATA_FOR_MANUAL_RECON      => 'Insufficient data given for manual reconciliation of txn.',
    ];

    // Fields being used to fetch CPS authorization data
    const RRN                    = 'rrn';
    const STATUS                 = 'status';
    const AUTH_CODE              = 'auth_code';
    const GATEWAY_TRANSACTION_ID = 'gateway_transaction_id';
    const NETWORK_TRANSACTION_ID = 'network_transaction_id';
    const GATEWAY_REFERENCE_ID2  = 'gateway_reference_id2';
    const GATEWAY_REFERENCE_ID1  = 'gateway_reference_id1';

    const CPS_PARAMS = [
        Constants::RRN,
        Constants::AUTH_CODE,
        Constants::GATEWAY_TRANSACTION_ID,
        Constants::GATEWAY_REFERENCE_ID1,
        Constants::GATEWAY_REFERENCE_ID2
    ];

    // Fields being used in batch recon request flow
    const BATCH_ID               = 'batch_id';
    const SOURCE                 = 'source';
    const GATEWAY                = 'gateway';
    const SUB_TYPE               = 'sub_type';
    const SHEET_NAME             = 'sheet_name';
    const ENTITY_TYPE            = 'entity_type';

    // Batch services uses this column name for Amount
    const COLUMN_BATCH_AMOUNT    = 'Amount (In Paise)';
    const COLUMN_API_AMOUNT      = 'Amount';

    // Used in batch response
    const HTTP_STATUS_CODE       = 'http_status_code';
    const IDEMPOTENT_ID          = 'idempotent_id';

    // Batch service feature name
    const BATCH_SERVICE_RECONCILIATION_MIGRATION = 'batch_service_reconciliation_migration';
}
