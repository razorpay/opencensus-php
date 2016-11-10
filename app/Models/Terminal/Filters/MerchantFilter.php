<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;

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
        if ($input['payment']->isCard())
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
        $category = $terminal->getNetworkCategory();

        // If the terminal's category is null, pass though.
        // When all the terminals are without category, this
        // will pass them all through.
        if (empty($category) === true)
        {
            return true;
        }

        $method = $input['payment']->getMethod();

        $network = $input['payment']->isMethodCardOrEmi() ? $input['payment']->card->getNetworkCode() : null;

        $defaultCategory = Terminal\Category::getDefaultForMethodAndNetwork($method, $network);

        // If category is a defaultCategory allow,
        // no need to compute merchant category
        if ($category === $defaultCategory)
        {
            return true;
        }

        $merchantTerminalCategory = $input['merchant']->getCategory2();

        // Use Merchant specific category for method, network or maybe overridden for gateway
        $merchantTerminalCategory = Terminal\Category::getCategoryForMethodAndNetwork(
                                                                        $method,
                                                                        $network,
                                                                        $merchantTerminalCategory);

        return ($category === $merchantTerminalCategory);
    }

}
