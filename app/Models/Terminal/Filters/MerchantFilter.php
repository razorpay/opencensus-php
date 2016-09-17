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
        // 'risk',
        'category',
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
        if ($input['payment']->isNetbanking())
        {
            if ($input['merchant']->isTPVRequired())
            {
                return ($terminal->isTPVTerminal() === true);
            }

            return ($terminal->isTPVTerminal() === false);
        }

        return true;
    }


    /**
     * For merchants with a risk rating above 4 and card use only axis_migs
     * terminals if the card used is supported
     */
    public function riskFilter($terminal, $input)
    {
        // We allow EMI transactions a pass through for
        // the riskFilter. Because in EMI, we may have to
        // allow payment through a specific EMI terminal
        if ($input['payment']->isMethod(Method::CARD))
        {
            if ($input['merchant']->getRiskRating() >= 4)
            {
                $network = $input['payment']->card->getNetworkCode();

                if (Gateway::isCardNetworkSupported($network, Gateway::AXIS_MIGS))
                {
                    return ($terminal->getGateway() === Gateway::AXIS_MIGS);
                }
            }
        }

        // Else allow - By default allow all transactions
        return true;
    }

    public function categoryFilter($terminal, $input)
    {
        $category = $terminal->getTerminalCategory();

        if (empty($category) === true)
        {
            return true;
        }

        $method = $input['payment']->getMethod();

        $network = $input['payment']->isMethodCardOrEmi() ? $input['payment']->card->getNetworkCode() : null;

        $defaultCategory = Terminal\Category::getDefaultForMethodAndNetwork($method, $network);

        // If category is a defaultCategory allow, no need to compute merchant category
        if ($category === $defaultCategory)
        {
            return true;
        }

        $merchantTerminalCategory = $input['merchant']->getTerminalCategory();

        // Use Merchant specific for method or maybe overridden for gateway;
        $merchantTerminalCategory = Terminal\Category::getCategoryForMethodAndNetwork(
                                                                        $method,
                                                                        $network,
                                                                        $merchantTerminalCategory);

        // If the category matches merchantTerminalCategory pass, else fail
        return ($category === $merchantTerminalCategory);
    }

}
