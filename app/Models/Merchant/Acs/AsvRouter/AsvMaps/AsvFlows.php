<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class AsvFlows
{

    public const ENABLED_QUERY = array(
        'payment_notify' => true,
        'payment_verify_new' => true,
        'internal_transactions' => true,
        'order_payments' => true,
        'payment_fetch_multiple' => true,
        'gateway_payment_callback_post' => true,
        'merchant_activation_details' => true,
        'payment_fetch_by_id' => true,
        'internal_merchant_fetch' => true,
        'payment_create_ajax' => true,
        'payment_create_checkout' => true,
        'scrooge_entities_fetch' => true,
        'payout_fetch_multiple' => true,
        'worker:p_g_ledger_acknowledgment_job' => true,
        'payment_create_upi' => true,
        'worker:pgos_cdc_events_job' => true,
        'worker:transfer_process_key_merchants' => true,
        'worker:banking_account_statement_processor' => true,
        'transfer_fetch' => true,
        'worker:async_balance_update_for_transfer' => true,
        'payment_timeout_new' => true,
        'payment_transfer' => true,
        'payment_callback_with_key_post' => true,
        'payout_create' => true,
        'worker:es_sync' => true,
        'internal_create_order_relations' => true,
        'payment_fetch_transfers' => true,
        'worker:merchant_based_balance_update_common5' => true,
        'merchant_entities_info' => true,
        'worker:merchant_based_balance_update_common2' => true,
        'worker:merchant_based_balance_update_common1' => true,
        'qr_code_create' => true,
        'payment_callback_with_key_get' => true,
        'update_fts_fund_transfer' => true,
        'payment_create_private_old' => true,
        'payment_capture' => true,
        'user_fetch' => true,
        'worker:queued_payouts_initiate' => true,
        'payment_create_private_json' => true,
        'payment_fetch_refunds' => true,
        'worker:transfer_process_slice' => true,
        'customer_fetch_internal_for_checkout' => true,
        'worker:transfer_process' => true,
        'payment_otp_submit' => true,
        'payment_redirect_to_authenticate_get' => true,
        'worker:merchant_based_balance_update_v1' => true,
        'gateway_payment_callback_recurring' => true,
        'worker:ledger_journal_live' => true,
        'merchant_edit_pre_signup_details' => true,
        'verify_user_otp_register' => true,
        'order_fetch_by_id' => true
    );

    public const MAP = array();

    public static function isExclusionFLow(string $flow): bool
    {
        if (array_key_exists($flow, self::MAP)) {
            return true;
        }

        return false;
    }

    public static function isEnabledQueryLogs(string $flow): bool
    {
        if (array_key_exists($flow, self::ENABLED_QUERY)) {
            return true;
        }

        return false;
    }

}
