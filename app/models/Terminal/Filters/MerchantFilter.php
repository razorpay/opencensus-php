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

    /**
     * Filter applies for securities merchants
     * Only for the netbanking method.
     * Allow Only Third Party Validation (TPV) terminals for
     * TPV required merchants.
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
            $terminalCategory = $terminal->getCategory();

            $tpvCategories = $input['merchant']->getTPVCategories();

            return in_array($terminalCategory, $tpvCategories);
        }

        return true;
    }

}
