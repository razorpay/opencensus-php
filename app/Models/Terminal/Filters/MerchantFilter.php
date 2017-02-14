<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Models\Terminal\Category;
use RZP\Models\Merchant;
use RZP\Models\Card\Network;

class MerchantFilter extends Terminal\Filter
{
    protected $properties = [
        'incompatible',
        'category',
        'pharma',
        'gateway',
        'wallet',
    ];


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

    /**
     * For merchants with a category2 that is incompatible,
     * the null and the default match terminals will be filtered out
     **/
    public function incompatibleFilter($terminal, $input)
    {
        $merchantTerminalCategory = $input['merchant']->getCategory2();

        if ((isset($merchantTerminalCategory) === true) and
            (Terminal\Category::isMerchantCategoryIncompatible($merchantTerminalCategory) === true))
        {
            $category = $terminal->getNetworkCategory();

            // If the terminal's category is null, don't allow.
            if (empty($category) === true)
            {
                return false;
            }

            $method = $input['payment']->getMethod();

            $network = $input['payment']->isMethodCardOrEmi() ? $input['payment']->card->getNetworkCode() : null;

            $defaultCategory = Terminal\Category::getDefaultForMethodAndNetwork($method, $network);

            // If category is a defaultCategory don't allow,
            if ($category === $defaultCategory)
            {
                return false;
            }
        }

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

        $category2 = $input['merchant']->getCategory2();

        // Use Merchant specific category for method, network or maybe overridden for gateway
        $merchantTerminalCategory = Terminal\Category::getCategoryForMethodAndNetwork(
                                                                        $method,
                                                                        $network,
                                                                        $category2);

        return ($category === $merchantTerminalCategory);
    }

    public function pharmaFilter($terminal, $input)
    {
        $category2 = $input['merchant']->getCategory2();

        $acquirer = $terminal->getGatewayAcquirer();

        if (($category2 === Category::PHARMA) and
            ($acquirer === Gateway::ACQUIRER_HDFC))
        {
            return false;
        }

        return true;
    }

    public function gatewayFilter($terminal, $input)
    {
        $merchantId = $input['payment']->getMerchantId();

        $merchantList = Merchant\Preferences::MERCHANT_TERMINAL_EXCLUDE_LIST;

        if (isset($merchantList[$merchantId]) === true)
        {
            $gateway = $terminal->getGateway();

            $excludedGateways = $merchantList[$merchantId];

            if (in_array($gateway, $excludedGateways, true) === true)
            {
                $network = $input['payment']->card->getNetworkCode();

                if (($network === Network::VISA) or
                    ($network === Network::MC))
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function walletFilter($terminal, $input, $applicableTerminals)
    {
        //
        // For wallets, payments have to go through their assigned terminal
        // because gateway has requested it and gives cashbacks, settlements
        // nuances based on the terminal
        //
        $gateway = $terminal->getGateway();

        // Filter only applicable for wallets
        if ($input['payment']->getMethod() !== Method::WALLET)
        {
            return true;
        }

        // wallets for which only direct assigned terminal must be accessed.
        $wallets = [
            Gateway::WALLET_FREECHARGE,
            Gateway::WALLET_AIRTELMONEY,
        ];

        if (in_array($gateway, $wallets, true) === false)
        {
            return true;
        }

        // If the wallet terminal is not shared, return the terminal
        if ($terminal->isShared() === false)
        {
            return true;
        }

        foreach ($applicableTerminals as $currentTerminal)
        {
            if ($currentTerminal->isShared() === false)
            {
                // direct terminals exists, do not use shared terminals
                return false;
            }
        }

        return true;
    }
}
