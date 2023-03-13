<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Settlement\Channel;
use RZP\Models\Transaction\Type;

class Constants
{
    const FOUR_PM = 16;

    const COUNTRY_CODE_TO_TRANSACTION_CHANNEL_MAP = [
        'MY' => Channel::RHB,
        'IN' => Channel::YESBANK
    ];

    const DO_NOT_DISPATCH_FOR_SETTLEMENT = [
        Type::PAYMENT,
        Type::SETTLEMENT,
        Type::PAYOUT,
        Type::CREDIT_TRANSFER
    ];
}
