<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Terminal\Category;
use RZP\Models\Merchant;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Processor\Netbanking;

class MerchantFilter extends Terminal\Filter
{
    const CORPORATE_IFSC = [
        IFSC::ICIC
    ];

    const MUTUAL_FUNDS_IFSC = [
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::SBTR,
        IFSC::STBP,
        IFSC::STCB,
        Netbanking::PUNB_C,
        Netbanking::PUNB_R,
        IFSC::CNRB,
    ];

    protected $properties = [
        'billdesk_category',
        'billdesk_merchant',
        'incompatible',
        'category',
        'pharma',
        'cryptocurrency',
        'gateway',
        'wallet',
    ];

    /**
     * Performs category based filtering for billdesk terminals.
     * Rules are based on merchant category and the corresponding
     * banks not enabled on those categories.
     * */
    public function billdeskCategoryFilter(Terminal\Entity $terminal, array $input) : bool
    {
        $bankIfsc = array_merge(self::CORPORATE_IFSC, self::MUTUAL_FUNDS_IFSC);

        $bank = $input['payment']->getBank();

        $gateway = $terminal->getGateway();

        $category2 = $input['merchant']->getCategory2();

        $networkCategory = $terminal->getNetworkCategory();

        if (($input['payment']->isNetbanking()) and
            (in_array($bank, $bankIfsc, true) === true) and
            ($gateway === Gateway::BILLDESK))
        {
            // Two rules to be checked
            switch ($category2)
            {
                // If securities or commodities then the shared terminal
                // should not be used, i.e on the shared terminal return
                // false.
                case Category::SECURITIES :
                case Category::COMMODITIES:
                    return ($terminal->isShared() === false);
                    break;

                // If corporate or mutual_funds then the corresponding
                // terminal should not be used, as ICIC is not being allowed
                // on that terminal
                case Category::CORPORATE:
                    if (in_array($bank, self::CORPORATE_IFSC, true) === false)
                    {
                        return true;
                    }

                    return ($networkCategory !== $category2);
                    break;

                case Category::MUTUAL_FUNDS:
                    if (in_array($bank, self::MUTUAL_FUNDS_IFSC, true) === false)
                    {
                        return true;
                    }

                    return ($networkCategory !== $category2);
                    break;
            }
        }

        return true;
    }

    /**
     * Performs merchant based filtering for billdesk terminals.
     * Rules are based on merchant id and the corresponding
     * banks not enabled on those direct terminals.
     * */
    public function billdeskMerchantFilter(Terminal\Entity $terminal, array $input) : bool
    {
        $gateway = $terminal->getGateway();

        $bank = $input['payment']->getBank();

        $merchantId = $input['merchant']->getId();

        $merchantIdsToDisallowDirectTerminal = [
            '4sW8jQ22JR4Bfi',
        ];

        if (($bank === IFSC::ICIC) and
            ($gateway === Gateway::BILLDESK) and
            ($input['payment']->isNetbanking() === true))
        {
            if (in_array($merchantId, $merchantIdsToDisallowDirectTerminal, true) === true)
            {
                // Disable direct terminal i.e
                // Allow only shared terminal
                return ($terminal->isShared() === true);
            }
        }

        return true;
    }

    /**
     * For merchants with a risk rating above 4 and card use only axis_migs
     * terminals if the card used is supported
     */
    public function riskFilter(Terminal\Entity $terminal, array $input) : bool
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
    public function incompatibleFilter(Terminal\Entity $terminal, array $input) : bool
    {
        $merchantTerminalCategory = $input['merchant']->getCategory2();

        if ((isset($merchantTerminalCategory) === true) and
            (Category::isMerchantCategoryIncompatible($merchantTerminalCategory) === true))
        {
            $category = $terminal->getNetworkCategory();

            // If the terminal's category is null, don't allow.
            if (empty($category) === true)
            {
                return false;
            }

            $method = $input['payment']->getMethod();

            $network = $input['payment']->isMethodCardOrEmi() ? $input['payment']->card->getNetworkCode() : null;

            $defaultCategory = Category::getDefaultForMethodAndNetwork($method, $network);

            // If category is a defaultCategory don't allow,
            if ($category === $defaultCategory)
            {
                return false;
            }
        }

        return true;
    }

    public function categoryFilter(Terminal\Entity $terminal, array $input) : bool
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

        $defaultCategory = Category::getDefaultForMethodAndNetwork($method, $network);

        // If category is a defaultCategory allow,
        // no need to compute merchant category
        if ($category === $defaultCategory)
        {
            return true;
        }

        $category2 = $input['merchant']->getCategory2();

        // Use Merchant specific category for method, network or maybe overridden for gateway
        $merchantTerminalCategory = Category::getCategoryForMethodAndNetwork(
                                                                        $method,
                                                                        $network,
                                                                        $category2);

        return ($category === $merchantTerminalCategory);
    }

    /**
     * Disallow pharma merchants from being sent on
     * terminals with acquirer as HDFC, if the card
     * network is Visa or Master
     *
     * @param Terminal\Entity $terminal
     * @param array           $input Combined input
     *
     * @return bool Whether a terminal is to be chosen or not
     */
    public function pharmaFilter(Terminal\Entity $terminal, array $input) : bool
    {
        $category2 = $input['merchant']->getCategory2();

        $acquirer = $terminal->getGatewayAcquirer();

        if (($category2 === Category::PHARMA) and
            ($input['payment']->isMethodCardOrEmi()))
        {
            if (($terminal->isShared() === true) and
                ($acquirer === Gateway::ACQUIRER_HDFC))
            {
                // This check is for all the card networks which are
                // supported by gateways from other acquirers that also
                // have a shared terminal.
                // Currently, we don't have a shared terminal RuPay and
                // Maestro. We are doing a workaround using the
                // merchant descriptor feature of FirstData
                return false;
            }
            // Terminal ID for Aala first data terminal is 76lEBqibDvhOzY
            else if ($terminal->getId() === '76lEBqibDvhOzY')
            {
                 $network = $input['payment']->card->getNetworkCode();

                 if (in_array($network, [Network::RUPAY, Network::MAES], true) === false)
                 {
                    return false;
                 }
            }
        }

        return true;
    }

    /**
     * Disallow crytocurrency merchants from being sent on
     * netbanking terminals for HDFC or ICIC
     *
     * @param Terminal\Entity $terminal
     * @param Array $input Combined input
     * @return bool Whether a terminal is to be chosen or not
     * */
    public function cryptocurrencyFilter(Terminal\Entity $terminal, array $input) : bool
    {
        $method = $input['payment']->getMethod();

        if ($input['merchant']->getCategory2() === Category::CRYPTOCURRENCY)
        {
            if ($input['payment']->isMethodCardOrEmi())
            {
                return false;
            }
            else if ($method === Method::Netbanking)
            {
                $bank = $input['payment']->getBank();

                return (in_array($bank, Category::DISABLED[Method::Netbanking][Category::CRYPTOCURRENCY], true) === false);
            }
        }

        return true;
    }

    public function gatewayFilter(Terminal\Entity $terminal, array $input) : bool
    {
        $method = $input['payment']->getMethod();

        if (in_array($method, [Method::CARD, Method::EMI], true) === false)
        {
            return true;
        }

        $merchantId = $input['payment']->getMerchantId();

        $gateway = $terminal->getGateway();

        $excludeList = Merchant\Preferences::MERCHANT_GATEWAY_BLACKLIST;

        if (isset($excludeList[$merchantId]) === true)
        {
            $excludedGateways = $excludeList[$merchantId];

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

        $includeList = Merchant\Preferences::MERCHANT_GATEWAY_WHITELIST;

        if (isset($includeList[$merchantId]) === true)
        {
            $includedGateways = $includeList[$merchantId];

            if (in_array($gateway, $includedGateways, true) === true)
            {
                return true;
            }
            else
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

    public function walletFilter(Terminal\Entity $terminal, array $input, $applicableTerminals) : bool
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
