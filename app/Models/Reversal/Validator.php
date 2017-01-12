<?php

namespace RZP\Models\Reversal;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    protected static $reversalRules = [
        'amount'                => 'sometimes|integer|min:100'
    ];

    public function validateReversalAmount(array $input)
    {
        if (isset($input['amount']) === false)
        {
            return;
        }

        // validate amount limits etc
    }
}
