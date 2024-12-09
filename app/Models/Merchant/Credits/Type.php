<?php

namespace RZP\Models\Merchant\Credits;

class Type
{
    const AMOUNT             = 'amount';
    const FEE                = 'fee';
    const REFUND             = 'refund';
    const REWARD_FEE         = 'reward_fee';
    const FEE_CREDIT         = 'fee_credit';
    const FEE_WITHDRAW      = 'fee_withdraw';
    const REFUND_WITHDRAW   = 'refund_withdraw';

    const WITHDRAWAL_TYPE_MAPPING = [
        self::FEE         => self::FEE_WITHDRAW,
        self::REFUND       => self::REFUND_WITHDRAW,
    ];
}
