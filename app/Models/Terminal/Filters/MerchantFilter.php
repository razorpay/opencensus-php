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
        'category',
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

    public function categoryFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        if ($method !== Method::NETBANKING)
        {
            return true;
        }

        // Add restriction for 6211 only as of now.
        // Will be covered as part of true category selection.
        $terminalCategory = $terminal->getCategory();

        $restrictedTerminalCategory = 6211;

        if ($terminalCategory === $restrictedTerminalCategory)
        {
            // Check For TPV only
            return $input['merchant']->isTPVRequired();
        }
        else
        {
            return true;
        }
    }

}
