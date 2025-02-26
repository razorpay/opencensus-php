<?php

namespace RZP\Models\BankTransfer;

class Metric
{
    // Metric Constants
    const COLLECTX_BANK_CALLBACK_COUNT               = "collectx_bank_callback_count";
    const COLLECTX_BANK_CALLBACK_PROCESSED_COUNT     = "collectx_bank_callback_processed_count";
    const BANKTRANSFER_CALLBACK_COUNT                = "banktransfer_callback_count";
    const BANKTRANSFER_WEBHOOK_DELAY                 = "banktransfer_delay";
    const COLLECTX_WORKER_JOB_DISPATCH_FAILURE_COUNT = "collectx_worker_job_dispatch_failure_count";
    const COLLECTX_UNEXPECTED_PAYMENT_TRANSFER_COUNT = "collectx_unexpected_payment_transfer_count";
    const B2B_BANK_ACCOUNT_FETCH_ACCOUNT_BY_CURRENCY_FAILED = "b2b_bank_account_fetch_account_by_currency_failed";
    const INTERNATIONAL_B2B_CURRENCY_CLOUD_BANK_ACCOUNT_CREATION_FAILED = "international_b2b_currency_cloud_bank_account_creation_failed";
    const INTL_BANK_TRANSFER_SETTLEMENT_CONVERT_CURRENCY_FAILED = "intl_bank_transfer_settlement_convert_currency_failed";
    const INTL_BANK_TRANSFER_EMPTY_ACCOUNT_RETURNED_FROM_CURRENCY_CLOUD = "intl_bank_transfer_empty_account_returned_from_currency_cloud";
}
