<?php

namespace RZP\Models\Terminal\Filters;

use App;

use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Terminal\Category;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Customer\GatewayToken;
use RZP\Models\Payment\Processor\Netbanking;

class TransactionFilter extends Terminal\Filter
{
    const PREPAID_IIN = '457392';

    protected $properties = [
        'method',
        'network',
        'bank',
        'recurring',
        'gateway',
        'subscription',
        'tpv',
        'upi',
        'pharma',
        'corporate',
        'mcc',
    ];

    public function methodFilter($terminal)
    {
        $method = $this->input['payment']->getMethod();

        switch ($method)
        {
            case Method::CARD:
                return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false));

            case Method::NETBANKING:
                return $terminal->isNetbankingEnabled();

            case Method::EMI:
                return $this->isValidEmiTerminal($terminal);

            // Pick the right terminal only
            case Method::WALLET:
                $wallet = $this->input['payment']->getWallet();

                $gateway = Gateway::getGatewayForWallet($wallet);

                return ($gateway === $terminal->getGateway());

            case Method::UPI:
                return $terminal->isUpiEnabled();

            case Method::AEPS:
                return $terminal->isAepsEnabled();

            default:
                throw new Exception\LogicException(
                    'Unknown payment method passed.',
                    null,
                    [
                        'terminal_id'   => $terminal->getId(),
                        'method'        => $method
                    ]);
        }
    }

    // Applicable only for card and emi
    public function networkFilter($terminal)
    {
        if ($this->input['payment']->isMethodCardOrEmi())
        {
            $network = $this->input['payment']->card->getNetworkCode();

            return Gateway::isCardNetworkSupported($network, $terminal->getGateway());
        }

        return true;
    }

    public function bankFilter($terminal)
    {
        if ($this->input['payment']->isNetbanking())
        {
            $bank = $this->input['payment']->getBank();

            $terminalGateway = $terminal->getGateway();

            $isTPV = $this->input['merchant']->isTPVRequired();

            $gateways = Gateway::getGatewaysForNetbankingBank($bank, $isTPV);

            return in_array($terminalGateway, $gateways);
        }

        return true;
    }

    /**
     * Filter to remove cybersource shared terminals for non recurring payments
     *
     * @param  Terminal\Entity $terminal
     * @return bool
     */
    public function gatewayFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        $merchant = $this->input['merchant'];

        // This filter should run only in production environment, else tests for
        // cybersource would fail.
        if (($this->isLiveMode() === true) and ($payment->isMethodCardOrEmi() === true))
        {
            if ($terminal->getGateway() === Gateway::CYBERSOURCE)
            {
                //
                // For some merchants, due to business reasons we want payments
                // to go through cybersource terminal
                //
                $merchantWhitelisted = (in_array($merchant->getId(),
                                            Preferences::CYBERSOURCE_MERCHANT_WHITELIST,
                                            true) === true);

                $iin = $payment->card->getIin();

                if (($merchantWhitelisted === false) and
                    ($payment->isRecurring() === false) and
                    ($payment->isInternational() === false) and
                    ($iin !== self::PREPAID_IIN) and
                    ($terminal->isDirectForMerchant($merchant) === false))
                {
                    return false;
                }
            }
            else if (($terminal->getGateway() === Gateway::AXIS_MIGS) and
                     ($merchant->getId() === Preferences::MID_ZOMATO))
            {
                $iin = $payment->card->getIin();

                if ($iin !== self::PREPAID_IIN)
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function recurringFilter($terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isRecurring() === false)
        {
            return ($terminal->isNonRecurring() === true);
        }

        $recurringGateway = Gateway::isRecurringGateway($terminal->getGateway());

        if ($recurringGateway === false)
        {
            return false;
        }

        // for cybersource recurring payment, terminal must be hdfc acquired
        if (($terminal->getGateway() === Gateway::CYBERSOURCE) and
            ($terminal->getGatewayAcquirer() !== 'hdfc'))
        {
            return false;
        }

        $basicAuth = $this->app['basicauth'];

        $token = $payment->getGlobalOrLocalTokenEntity();

        $access = (($basicAuth->isPrivateAuth() === true) or
                   ($basicAuth->isPrivilegeAuth() === true));

        //
        // All first recurring payments or payments made via public
        // auth need to go via 3DS Recurring terminals only.
        //
        if (($token === null) or
            ($token->isRecurring() === false) or
            ($access === false))
        {
            return ($terminal->is3DSRecurring() === true);
        }

        //
        // From here onwards, the terminal selection
        // logic is for second recurring.
        //
        if ($terminal->isNon3DSRecurring() === false)
        {
            return false;
        }

        $reference = $payment->getReferenceForGatewayToken();

        $gatewayTokens = $this->repo->gateway_token->findByTokenAndReference($token, $reference);

        $gatewayTokensCount = $gatewayTokens->count();

        if ($gatewayTokensCount > 0)
        {
            //
            // For second recurring payment, ensure that we select a terminal
            // of the same gateway as for the first recurring payment and also
            // of the same merchant (shared, direct)
            //
            $validGatewayTokens = $gatewayTokens->filter(
                                    function($gatewayToken) use ($terminal)
                                    {
                                        return (($gatewayToken->getGateway() === $terminal->getGateway()) and
                                            ($gatewayToken->terminal->getMerchantId() === $terminal->getMerchantId()));
                                    });

            //
            // We check if we have one valid gateway_token for the
            // terminal being selected. If yes, we return back true.
            // If we don't have even one valid gateway_token for the
            // terminal being selected, we return back false.
            //
            // The check is again 1 exactly because for a given gateway,
            // there should not be more than one terminal. We don't support
            // more than 1 set of terminals for a merchant (direct/shared).
            // If it's greater than 1, there's something wrong and should fail.
            //
            return ($validGatewayTokens->count() === 1);
        }
        //
        // If a token is present and is supposed to be subsequent charge,
        // the corresponding gateway_token must always be present.
        // If it's not present, there's something wrong somewhere!
        //
        else
        {
            throw new Exception\LogicException(
                'Should have gotten at least 1 gateway token.',
                ErrorCode::SERVER_ERROR_GATEWAY_TOKENS_INVALID_COUNT,
                [
                    'gateway_tokens_count'  => $gatewayTokensCount,
                    'payment_id'            => $payment->getId(),
                    'token_id'              => $token->getId(),
                    'reference'             => $reference
                ]);
        }
    }

    protected function upiFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isUpi() === true)
        {
            $flow = $payment->getMetadata('flow', 'collect');

            if ($flow === 'intent')
            {
                $gateway = $terminal->getGateway();

                return Gateway::isUpiIntentFlowSupported($gateway);
            }
        }

        return true;
    }

    protected function corporateFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isNetbanking() === true)
        {
            $bank = $payment->getBank();

            // If a bank does not require a corporate terminal
            // a corporate terminal should not allow the payment.
            return (Netbanking::isCorporateTerminalRequired($bank) === $terminal->isCorporate());
        }

        return true;
    }

    protected function subscriptionFilter(Terminal\Entity $terminal)
    {
        //
        // For now, not filtering based on gateway.
        // Assuming that all gateways work without
        // one year limitation. /cc @shk
        //
        return true;

        $payment = $this->input['payment'];

        //
        // If it's NOT a subscription payment,
        // don't do any filtering.
        //
        if ($payment->hasSubscription() === false)
        {
            return true;
        }

        $subscription = $payment->subscription;

        if ($subscription->isMoreThanOneYear() === false)
        {
            //
            // If subscription is not for more than a year,
            // there's no filtering required.
            //
            return true;
        }

        $currentGateway = $terminal->getGateway();
        $allowedGateways = Gateway::$subscriptionOverOneYearGateways;

        return (in_array($currentGateway, $allowedGateways, true) === true);
    }

    protected function isValidEmiTerminal($terminal)
    {
        $payment = $this->input['payment'];

        $bank = $payment->getBank();

        // check if banks emi transactions can be processed from any card terminal
        if ((empty($bank) === false) and
            (in_array($bank, Gateway::$emiBanksUsingCardTerminals)))
        {
            return (($terminal->isCardEnabled()) and
                    ($terminal->isEmiEnabled() === false) and
                    ($terminal->isCurrencyInr() === true));
        }

        // validate terminal using the gateway and emi duration
        $network = $this->input['payment']->card->getNetworkCode();

        if ($network === Network::AMEX)
        {
            $gateway = Gateway::AMEX;
        }
        else
        {
            $gateway = Gateway::$emiBankToGatewayMap[$bank];
        }

        $emiDuration = $this->input['payment']->emiPlan->getDuration();

        $subvention = $this->input['payment']->emiPlan->getSubvention();

        return $terminal->isValidEmiTerminal($gateway, $emiDuration, $subvention);
    }

    public function pharmaFilter(Terminal\Entity $terminal)
    {
        $category2 = $this->input['merchant']->getCategory2();

        $acquirer = $terminal->getGatewayAcquirer();

        if (($category2 === Category::PHARMA) and
            ($this->input['payment']->isMethodCardOrEmi() === true))
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
     * For netbanking payments, if a merchant has tpv feature enabled, checks
     * if the terminal supports tpv or not
     *
     * @param  Terminal\Entity      $terminal
     *
     * @return bool
     */
    public function tpvFilter($terminal)
    {
        if ($this->input['payment']->isNetbanking() === true)
        {
            if ($this->input['merchant']->isFeatureEnabled(Feature\Constants::TPV))
            {
                return ($terminal->isTpvAllowed() === true);
            }

            return ($terminal->isNonTpvAllowed() === true);
        }

        return true;
    }

    /**
     * For card / emi payments, selects terminals with null mcc or with mcc
     * matching that of the merchant
     *
     * @param  Terminal\Entity $terminal
     *
     * @return bool
     */
    public function mccFilter(Terminal\Entity $terminal, array $applicableTerminals)
    {
        $merchant = $this->input['merchant'];
        $merchantMcc = $merchant->getCategory();

        if (($this->input['payment']->isMethodCardOrEmi() === true) and
            (in_array($terminal->getGateway(), Gateway::MCC_FILTER_GATEWAYS, true) === true))
        {
            //
            // If terminal is direct for the merchant, we always select it.
            //
            if ($terminal->isDirectForMerchant($merchant) === true)
            {
                return true;
            }

            if ($terminal->getCategory() !== null)
            {
                //
                // If terminal category is not null, then we reject the terminal
                // if it's category is not the same as merchant mcc.
                //
                return ($terminal->getCategory() === $merchantMcc);

            }
            else
            {
                //
                // If the terminal is a shared terminal with category null, then
                // we select it, if there are no terminals with the merchant mcc
                // present in the set of all terminals.
                //
                return ($this->isTerminalWithMerchantMccAbsent(
                            $applicableTerminals,
                            $merchantMcc) === true);
            }
        }

        return true;
    }

    protected function isTerminalWithMerchantMccAbsent(
        array $applicableTerminals,
        int $merchantMcc = null): bool
    {
        foreach ($applicableTerminals as $terminal)
        {
            //
            // Currently this checks only for HDFC and hitachi gateway terminals
            //
            if ((in_array($terminal->getGateway(), Gateway::MCC_FILTER_GATEWAYS, true) === true) and
                ($terminal->getCategory() === $merchantMcc))
            {
                return false;
            }
        }

        return true;
    }
}
