<?php

namespace RZP\Models\PayoutsStatusDetails;

use RZP\Models\Payout\Status as PayoutStatus;


class StatusReasonMap

{
    const BENEFICIARY_BANK_CONFIRMATION_PENDING = 'beneficiary_bank_confirmation_pending';

    const BANK_WINDOW_CLOSED                    = 'bank_window_closed';

    const PAYOUT_BANK_PROCESSING                = 'payout_bank_processing';

    const PARTNER_BANK_PENDING                  = 'partner_bank_pending';

    const PAYOUT_PROCESSING                     = 'payout_processing';

    const PAYOUT_PROCESSED                      = 'payout_processed';

    const LOW_BALANCE                           = 'low_balance';

    const NPCI_SYSTEM_DOWN                      = 'NPCI_system_down';

    const BENEFICIARY_BANK_DOWN                 = 'beneficiary_bank_down';

    const GATEWAY_DEGRADED                      = 'gateway_degraded';

    const PENDING_APPROVAl                      = 'pending_approval';

    const GATEWAY_TECHNICAL_ERROR               = 'gateway_technical_error';

    const BENEFICIARY_BANK_OFFLINE              = 'beneficiary_bank_offline';

    const BENEFICIARY_PSP_OFFLINE               = 'beneficiary_psp_offline';

    const BANK_ACCOUNT_INVALID                  = 'bank_account_invalid';

    const BANK_ACCOUNT_CLOSED                   = 'bank_account_closed';

    const TRANSACTION_LIMIT_EXCEEDED            = 'transaction_limit_exceeded';

    const BENEFICIARY_BANK_FAILURE              = 'beneficiary_bank_failure';

    const BENEFICIARY_BANK_REJECTED             = 'beneficiary_bank_rejected';

    const BANK_ACCOUNT_FROZEN                   = 'bank_account_frozen';

    const GATEWAY_TIMEOUT                       = 'gateway_timeout';

    const IMPS_NOT_ALLOWED                      = 'imps_not_allowed';

    const INVALID_IFSC_CODE                     = 'invalid_ifsc_code';

    const BENEFICIARY_VPA_INVALID               = 'beneficiary_vpa_invalid';

    const NRE_BANK_ACCOUNT                      = 'nre_bank_account';

    const SERVER_ERROR                          = 'server_error';

    const BANK_ACCOUNT_DORMANT                  = 'bank_account_dormant';

    const AMOUNT_LIMIT_EXHAUSTED                = 'amount_limit_exhausted';

    const GATEWAY_DOWN                          = 'gateway_down';

    public static $payoutStatusToReasonMap = [
        PayoutStatus::PROCESSING => [
            self::BENEFICIARY_BANK_CONFIRMATION_PENDING,
            self::BANK_WINDOW_CLOSED,
            self::PAYOUT_BANK_PROCESSING,
            self::AMOUNT_LIMIT_EXHAUSTED,
            self::PARTNER_BANK_PENDING,
        ],

        PayoutStatus::PROCESSED => [
            self::PAYOUT_PROCESSED,
        ],

        PayoutStatus::QUEUED => [
            self::LOW_BALANCE,
            self::NPCI_SYSTEM_DOWN,
            self::BENEFICIARY_BANK_DOWN,
            self::GATEWAY_DEGRADED,
        ],

        PayoutStatus::PENDING => [
            self::PENDING_APPROVAl,
        ],
 // to do - to add these reasons when filter for reversed and failed status would be supported
/*        PayoutStatus::REVERSED => [
            self::GATEWAY_TECHNICAL_ERROR,
            self::BENEFICIARY_BANK_OFFLINE,
            self::BENEFICIARY_PSP_OFFLINE,
            self::BANK_ACCOUNT_INVALID,
            self::BANK_ACCOUNT_CLOSED,
            self::TRANSACTION_LIMIT_EXCEEDED,
            self::BENEFICIARY_BANK_FAILURE,
            self::BENEFICIARY_BANK_REJECTED,
            self::BANK_ACCOUNT_FROZEN,
            self::GATEWAY_TIMEOUT,
            self::IMPS_NOT_ALLOWED,
            self::BANK_ACCOUNT_INVALID,
            self::INVALID_IFSC_CODE,
            self::BENEFICIARY_VPA_INVALID,
            self::NRE_BANK_ACCOUNT,
            self::SERVER_ERROR,
            self::BANK_ACCOUNT_DORMANT,
            self::AMOUNT_LIMIT_EXHAUSTED,
            self::GATEWAY_DOWN,
        ],

        PayoutStatus::FAILED => [
            self::GATEWAY_TECHNICAL_ERROR,
            self::BENEFICIARY_BANK_OFFLINE,
            self::BENEFICIARY_PSP_OFFLINE,
            self::BANK_ACCOUNT_INVALID,
            self::BANK_ACCOUNT_CLOSED,
            self::TRANSACTION_LIMIT_EXCEEDED,
            self::BENEFICIARY_BANK_FAILURE,
            self::BENEFICIARY_BANK_REJECTED,
            self::BANK_ACCOUNT_FROZEN,
            self::GATEWAY_TIMEOUT,
            self::IMPS_NOT_ALLOWED,
            self::BANK_ACCOUNT_INVALID,
            self::INVALID_IFSC_CODE,
            self::BENEFICIARY_VPA_INVALID,
            self::NRE_BANK_ACCOUNT,
            self::SERVER_ERROR,
            self::BANK_ACCOUNT_DORMANT,
            self::AMOUNT_LIMIT_EXHAUSTED,
            self::GATEWAY_DOWN,
        ],
*/
    ];
}


