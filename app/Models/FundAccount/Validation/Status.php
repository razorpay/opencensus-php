<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Models\Transaction\Processor\Ledger;

class Status
{
    const CREATED   = 'created';
    const COMPLETED = 'completed';

    const FAILED    = 'failed';

    // This is here because FTA recon expects all source entities to have
    // the same status, and attempts to resolve the constant. TODO: Fix.
    const PROCESSED = 'processed';

    const REVERSED  = 'reversed';

    public static $favToLedgerStatusEventMap = [
        self::CREATED   => Ledger\FundAccountValidation::FAV_INITIATED,
        self::COMPLETED => Ledger\FundAccountValidation::FAV_PROCESSED,
        self::FAILED    => Ledger\FundAccountValidation::FAV_FAILED,
        self::REVERSED  => Ledger\FundAccountValidation::FAV_REVERSED,
    ];

    /**
     * @param string $favStatus
     * @return mixed|string
     * Return ledger event mapped to a fav status. If no such mapping is found, return
     * DEFAULT_EVENT. This is then handled in isDefaultEvent() in Transaction/Processor/Ledger
     */
    public static function getLedgerEventFromFavStatus(string $favStatus)
    {
        return self::$favToLedgerStatusEventMap[$favStatus] ?? Ledger\Base::DEFAULT_EVENT;
    }

    public static function hasFinalStatus(Entity $fav)
    {
        return in_array($fav->getStatus(), [self::FAILED, self::COMPLETED]) === true;
    }
}
