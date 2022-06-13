<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Transaction\Type;

class Constants
{
    const FOUR_PM = 16;

    const DO_NOT_DISPATCH_FOR_SETTLEMENT = [
        Type::PAYMENT,
        Type::SETTLEMENT,
        Type::PAYOUT,
        Type::CREDIT_TRANSFER
    ];
}
