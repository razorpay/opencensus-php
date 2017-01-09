<?php

namespace RZP\Models\ReverseTransfer;

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

    public function validateReverseTransfers()
    {

    }
}
