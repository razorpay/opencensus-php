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
    // TODO: Why not add all properties in the same filter class. These functions are pretty modular and independent
    // by themselves. I don't think a separation of classes is needed here.
    // We could just define all the properties in the base filter class and define the filter functions there itself.
    
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
     * @param Terminal\Entity $terminal
     * @param array $input
     * @return bool
     */
    public function tpvFilter(Terminal\Entity $terminal, array $input)
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
