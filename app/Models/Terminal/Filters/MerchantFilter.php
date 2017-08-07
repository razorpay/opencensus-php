<?php

namespace RZP\Models\Terminal\Filters;

use App;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Type;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\Category;
use RZP\Models\Payment\Processor\Netbanking;

class MerchantFilter extends Terminal\Filter
{
    // Banks that are to be removed for each of
    // the following caetgories are listed below
    const CATEGORY_DISALLOWED_IFSC = [
        Category::COMMODITIES => [
            IFSC::ICIC
        ],
        Category::SECURITIES => [
            IFSC::ICIC
        ],
        Category::CORPORATE => [
            IFSC::ICIC
        ],
        Category::INSURANCE =>
            self::DISALLOWED_COMMON_BANKS
        ,
        Category::MUTUAL_FUNDS =>
            self::DISALLOWED_COMMON_BANKS
        ,
        Category::HOUSING => [
            IFSC::ICIC,
            IFSC::UTIB,
        ],
    ];

    const DISALLOWED_COMMON_BANKS = [
        IFSC::SBBJ,
        IFSC::SBHY,
        IFSC::SBIN,
        IFSC::SBMY,
        IFSC::SBTR,
        IFSC::STBP,
        IFSC::STCB,
        IFSC::ICIC,
        IFSC::UTIB,
        IFSC::CNRB,
        Netbanking::PUNB_R,
        Netbanking::PUNB_C,
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
        'shared_terminal',
    ];

    /**
     * Performs category based filtering for billdesk terminals.
     * Rules are based on merchant category and the corresponding
     * banks not enabled on those categories.
     * */
    public function billdeskCategoryFilter(Terminal\Entity $terminal) : bool
    {
        $bank = $this->input['payment']->getBank();

        $gateway = $terminal->getGateway();

        $category2 = $this->input['merchant']->getCategory2();

        $networkCategory = $terminal->getNetworkCategory();

        $disAllowedBanks = self::CATEGORY_DISALLOWED_IFSC[$category2] ?? [];

        if (($this->input['payment']->isNetbanking() === true) and
            ($gateway === Gateway::BILLDESK) and
            ($this->isBankDisallowed($bank, $disAllowedBanks) === true))
        {
            // Two rules to be checked
            switch ($category2)
            {
                // If securities or commodities, no check required for other banks
                // the shared terminal should not be used for icici ,
                // i.e on the shared terminal return false.
                case Category::SECURITIES :
                case Category::COMMODITIES:
                    return ($terminal->isShared() === false);
                    break;

                // For corporate merchants,
                // In case of ICICI,
                // disallow - shared terminal with same category
                case Category::CORPORATE:
                    // on the shared terminal with a different
                    // network category is allowed
                     if (($terminal->isShared() === true) and
                         ($networkCategory === $category2))
                     {
                        return false;
                     }

                    break;

                // On the housing terminal, we do not pass the icici and
                // axis transactions, they are to be routed through our
                // shared directly integrated terminals
                case Category::HOUSING:
                    return false;
                    break;

                // For insurance and mutual funds merchants, the billdesk
                // shared terminals support only limited banks. They are
                // disallowed.
                case Category::INSURANCE:
                case Category::MUTUAL_FUNDS:
                    // The direct terminal allows other banks, however we
                    // wont send icici and axis terminal even in this case.

                    // Banks are disallowed only on the corresponding category terminal
                    // the shared ecommerce terminal is to be allowed
                    if ($networkCategory !== $category2)
                    {
                        $disAllowedBanks = [];
                    }

                    if ($terminal->isShared() === false)
                    {
                        $disAllowedBanks = [IFSC::ICIC, IFSC::UTIB];
                    }

                    if ($this->isBankDisallowed($bank, $disAllowedBanks) === true)
                    {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    protected function isBankDisallowed($bank, $disAllowedBanks)
    {
        return (in_array($bank, $disAllowedBanks, true) === true);
    }

    /**
     * Performs merchant based filtering for billdesk terminals.
     * Rules are based on merchant id and the corresponding
     * banks not enabled on those direct terminals.
     * */
    public function billdeskMerchantFilter(Terminal\Entity $terminal) : bool
    {
        $gateway = $terminal->getGateway();

        $bank = $this->input['payment']->getBank();

        $merchantId = $this->input['merchant']->getId();

        $merchantIdsToDisallowDirectTerminal = [
            '4sW8jQ22JR4Bfi',
        ];

        if (($bank === IFSC::ICIC) and
            ($gateway === Gateway::BILLDESK) and
            ($this->input['payment']->isNetbanking() === true))
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
    public function riskFilter(Terminal\Entity $terminal) : bool
    {
        // We allow EMI transactions a pass through for
        // the riskFilter. Because in EMI, we may have to
        // allow payment through a specific EMI terminal
        if ($this->input['payment']->isCard())
        {
            if ($this->input['merchant']->getRiskRating() >= 4)
            {
                $network = $this->input['payment']->card->getNetworkCode();

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
    public function incompatibleFilter(Terminal\Entity $terminal) : bool
    {
        $merchantTerminalCategory = $this->input['merchant']->getCategory2();

        if ((isset($merchantTerminalCategory) === true) and
            (Category::isMerchantCategoryIncompatible($merchantTerminalCategory) === true))
        {
            $category = $terminal->getNetworkCategory();

            // If the terminal's category is null, don't allow.
            if (empty($category) === true)
            {
                return false;
            }

            $method = $this->input['payment']->getMethod();

            $gateway = $terminal->getGateway();

            $defaultCategory = Category::getDefaultForMethodAndGateway($method, $gateway);

            // If category is a defaultCategory don't allow,
            if ($category === $defaultCategory)
            {
                return false;
            }
        }

        return true;
    }

    public function categoryFilter(Terminal\Entity $terminal) : bool
    {
        $category = $terminal->getNetworkCategory();

        // If the terminal's category is null, pass though.
        // When all the terminals are without category, this
        // will pass them all through.
        if (empty($category) === true)
        {
            return true;
        }

        $gateway = $terminal->getGateway();

        $method = $this->input['payment']->getMethod();

        $defaultCategory = Category::getDefaultForMethodAndGateway($method, $gateway);

        // If category is a defaultCategory allow,
        // no need to compute merchant category
        if ($category === $defaultCategory)
        {
            return true;
        }

        $category2 = $this->input['merchant']->getCategory2();

        // Use Merchant specific category for method, network or maybe overridden for gateway
        $merchantTerminalCategory = Category::getCategoryForMethodAndGateway(
                                                                        $method,
                                                                        $gateway,
                                                                        $category2);

        return ($category === $merchantTerminalCategory);
    }

    /**
     * Disallow pharma merchants from being sent on
     * terminals with acquirer as HDFC, if the card
     * network is Visa or Master
     *
     * @param Terminal\Entity $terminal
     *
     * @return bool Whether a terminal is to be chosen or not
     */
    public function pharmaFilter(Terminal\Entity $terminal) : bool
    {
        $category2 = $this->input['merchant']->getCategory2();

        $acquirer = $terminal->getGatewayAcquirer();

        if (($category2 === Category::PHARMA) and
            ($this->input['payment']->isMethodCardOrEmi()))
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
                 $network = $this->input['payment']->card->getNetworkCode();

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
     * @return bool Whether a terminal is to be chosen or not
     * */
    public function cryptocurrencyFilter(Terminal\Entity $terminal) : bool
    {
        $method = $this->input['payment']->getMethod();

        if ($this->input['merchant']->getCategory2() === Category::CRYPTOCURRENCY)
        {
            if ($this->input['payment']->isMethodCardOrEmi())
            {
                return false;
            }
            else if ($method === Method::Netbanking)
            {
                $bank = $this->input['payment']->getBank();

                return (in_array($bank,
                                Category::DISABLED[Method::Netbanking][Category::CRYPTOCURRENCY],
                                true) === false);
            }
        }

        return true;
    }

    public function gatewayFilter(Terminal\Entity $terminal) : bool
    {
        $method = $this->input['payment']->getMethod();

        if (in_array($method, [Method::CARD, Method::EMI], true) === false)
        {
            return true;
        }

        $merchantId = $this->input['payment']->getMerchantId();

        $gateway = $terminal->getGateway();

        $excludeList = Merchant\Preferences::MERCHANT_GATEWAY_BLACKLIST;

        if (isset($excludeList[$merchantId]) === true)
        {
            $excludedGateways = $excludeList[$merchantId];

            if (in_array($gateway, $excludedGateways, true) === true)
            {
                $network = $this->input['payment']->card->getNetworkCode();

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
                $network = $this->input['payment']->card->getNetworkCode();

                if (($network === Network::VISA) or
                    ($network === Network::MC))
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function walletFilter(Terminal\Entity $terminal, $applicableTerminals) : bool
    {
        //
        // For wallets, payments have to go through their assigned terminal
        // because gateway has requested it and gives cashbacks, settlements
        // nuances based on the terminal
        //
        $gateway = $terminal->getGateway();

        // Filter only applicable for wallets
        if ($this->input['payment']->getMethod() !== Method::WALLET)
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

    /**
     * For certaiin specified merchants, removes shared terminals from list of
     * terminals if the payment method is card.
     */
    public function sharedTerminalFilter(Terminal\Entity $terminal): bool
    {
        $merchantId = $this->input['payment']->getMerchantId();

        $blackListedMerchants = Merchant\Preferences::$merchantSharedTerminalsBlackList;

        if (($input['payment']->isCard() === true) and
            (in_array($merchantId,  $blackListedMerchants, true) === true))
        {
            // For card types listed below only allow shared terminal
            // - ICIC debit cards
            // - All CITI cards
            if ($this->isCardIssuerWhiteListed($input) === true)
            {
                return ($terminal->isShared() === true);
            }

            // For all other cards only allow direct terminals
            return ($terminal->isShared() === false);
        }

        return true;
    }

    public function isCardIssuerWhiteListed(array $input): bool
    {
        $issuer = $input['payment']->card->getIssuer();
        $type = $input['payment']->card->getType();

        return (((($issuer === Issuer::ICIC) and
                ($type !== Type::CREDIT))) or
                ($issuer === Issuer::CITI));
    }
}
