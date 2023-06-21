<?php

namespace RZP\Models\FundTransfer\Attempt;

class Purpose
{
    const REFUND               = 'refund';
    const SETTLEMENT           = 'settlement';
    const PENNY_TESTING        = 'penny_testing';
    const INTER_ACCOUNT_PAYOUT = 'inter_account_payout';
    const RZP_FUND_MANAGEMENT  = 'rzp_fund_management';

    const TYPE_LIST = [
        self::REFUND,
        self::SETTLEMENT,
        self::PENNY_TESTING,
        self::INTER_ACCOUNT_PAYOUT,
        self::RZP_FUND_MANAGEMENT,
    ];

    public static function isValid(string $type)
    {
        return (in_array($type, self::TYPE_LIST, true) === true);
    }
}
