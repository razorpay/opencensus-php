<?php

namespace RZP\Models\Payout;

class StatusDetails
{

    const BENEFICIARY_BANK_CONFIRMATION_PENDING            = 'beneficiary_bank_confirmation_pending';

    const BANK_WINDOW_CLOSED                               = 'bank_window_closed';

    const PAYOUT_PROCESSING                                = 'payout_processing';

    protected static $statusReasons = [
        self::BENEFICIARY_BANK_CONFIRMATION_PENDING,
        self::BANK_WINDOW_CLOSED,
        self::PAYOUT_PROCESSING,
    ];

    const STATUS_REASONS_WITH_DESCRIPTION = [

        self::BENEFICIARY_BANK_CONFIRMATION_PENDING  => "Confirmation of credit to the beneficiary is pending from ".
                                                        "beneficiary bank. Please check the status after time stamp",
        self::BANK_WINDOW_CLOSED                     => "The mode window for the day is closed. Payout will be "
                                                         ."processed by our partner bank at time stamp",
        self::PAYOUT_PROCESSING                      => "Payout is being processed by our partner bank. Please check "
                                                         ."the final status after some time",
    ];
}

