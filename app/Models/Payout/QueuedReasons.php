<?php

namespace RZP\Models\Payout;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\AccountType;

class QueuedReasons
{
    const BENE_BANK_DOWN       = 'beneficiary_bank_down';
    const LOW_BALANCE          = 'low_balance';
    const GATEWAY_DEGRADED     = 'gateway_degraded';
    //Commenting the remaining constants since those are not used and in summary api we have to loop on the queued reasons
    //which can increase the latency further. We can uncomment it once we start using these queued reasons
   // const NPCI_DOWN            = 'npci_system_down';
   // const NEFT_LIMIT_EXHAUSTED = 'neft_limit_exhausted';
   // const NEFT_WINDOW_CLOSED   = 'neft_window_closed';

    protected static $queuedReasons = [
        self::BENE_BANK_DOWN,
        self::LOW_BALANCE,
        self::GATEWAY_DEGRADED,
       // self::NPCI_DOWN,
       // self::NEFT_LIMIT_EXHAUSTED,
       // self::NEFT_WINDOW_CLOSED,
    ];

    const QUEUED_REASONS_WITH_DESCRIPTION = [
        self::BENE_BANK_DOWN       => 'Beneficiary bank\'s systems are not working. Payout will be processed after the system starts working else it will be failed after the pre-defined time limit.',
        self::LOW_BALANCE          => 'Payout is queued as there is insufficient balance in your account to process the payout.',
        self::GATEWAY_DEGRADED    => 'Payout is queued as Partner bank systems are down'
      //  self::NPCI_DOWN             => 'Payout is queued as NPCI system is down',
      //  self::NEFT_LIMIT_EXHAUSTED  => 'NEFT limit exhausted for the day',
      //  self::NEFT_WINDOW_CLOSED    => 'NEFT window is closed',
    ];

    public static function validateReason(string $mode)
    {
        if (self::isValid($mode) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_QUEUED_REASON,
                null,
                [
                    'mode' => $mode,
                ]);
        }
    }

    protected static function isValid(string $mode): bool
    {
        return (in_array($mode, self::$queuedReasons) === true);
    }
}
