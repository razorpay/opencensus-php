<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Constants\Mode;

use RZP\Exception;
use RZP\Error\ErrorCode;

use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Netbanking;

class MerchantFilter extends Terminal\Filter
{
    protected $properties = [
        'tpv',
    ];

    /**
     * Filter applies for securities merchants
     * Only for the netbanking method.
     * Allow Only Third Party Validation (TPV) terminals for
     * TPV required merchants, and non TPV terminals for non
     * TPV merchants.
     *
     * @return bool
     */
    public function tpvFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        if ($method !== Method::NETBANKING)
        {
            return true;
        }

        if ($input['merchant']->isTPVRequired())
        {
            return ($terminal->isTPVTerminal() === true);
        }

        return ($terminal->isTPVTerminal() === false);
    }
}
