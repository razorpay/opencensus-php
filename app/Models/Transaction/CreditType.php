<?php

namespace RZP\Models\Transaction;

use RZP\Exception;

class CreditType
{
    const DEFAULT           = 'default';
    const FEE               = 'fee';
    const AMOUNT            = 'amount';
    const REFUND            = 'refund';
    const REWARD_FEE        = 'reward_fee';
}
