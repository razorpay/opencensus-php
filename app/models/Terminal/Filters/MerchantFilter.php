<?php

namespace Models\Terminal\Filters;

use Constants\Mode;

use EE\Exception;
use EE\Error\ErrorCode;

use Models\Terminal;
use Models\Bank\IFSC;
use Models\Payment\Method;
use Models\Payment\Gateway;
use Models\Payment\Processor\Netbanking;

class MerchantFilter extends Terminal\Filter
{
    protected $properties = [
        'tpv',
    ];

    // Category related operations on terminals. 
    // In this case one of the params is the effect of 
    // TPV which is sensible only in case of 
    public function tpvFilter($terminal, $input)
    {
        if ($input['merchant']->isTPVRequired())
        {
            $terminalCategory = $terminal->getCategory();

            $tpvCategories = $input['merchant']->getTPVCategories();

            return in_array($terminalCategory, $tpvCategories);
        }

        return true;
    }

}
