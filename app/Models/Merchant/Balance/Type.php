<?php

namespace RZP\Models\Merchant\Balance;

class Type
{
    /*
     * PG balance types.
     */
    const PRIMARY        = 'primary';
    const FEE_CREDITS    = 'fee_credits';
    const REFUND_CREDITS = 'refund_credits';
    const AMOUNT_CREDITS = 'amount_credits';

    /**
     * Banking balance.
     */
    const BANKING        = 'banking';

    public static $pgBalances = [
        self::PRIMARY,
        self::FEE_CREDITS,
        self::REFUND_CREDITS,
        self::AMOUNT_CREDITS,
    ];
}
