<?php

namespace RZP\Models\Terminal\Filters;

use App;

use RZP\Exception;
use RZP\Models\Feature;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Card\Network;
use RZP\Models\Card\IIN\Flow;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Category;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Payment\Processor\Netbanking;

class TransactionFilter extends Terminal\Filter
{
    const PREPAID_IIN = '457392';

    protected $properties = [
        'method',
        'network',
        'bank',
        'emandate',
        'recurring',
        'gateway',
        'subscription',
        'tpv',
        'upi',
        'pharma',
        'corporate',
        'mcc',
        'auth_type',
        'bharat_qr',
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

            case Method::EMANDATE:
                return $terminal->isEmandateEnabled();

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
        $payment = $this->input['payment'];

        if ($payment->isMethodCardOrEmi() === true)
        {
            $network = $payment->card->getNetworkCode();

            return Gateway::isCardNetworkSupported($network, $terminal->getGateway(), $payment->isRecurring());
        }

        return true;
    }

    public function bankFilter($terminal)
    {
        if ($this->input['payment']->isNetbanking() === true)
        {
            $bank = $this->input['payment']->getBank();

            $terminalGateway = $terminal->getGateway();

            $isTPV = $this->input['merchant']->isTPVRequired();

            $gateways = Gateway::getGatewaysForNetbankingBank($bank, $isTPV);

            return in_array($terminalGateway, $gateways);
        }

        return true;
    }

    public function emandateFilter($terminal)
    {
        if ($this->input['payment']->isEmandate() === false)
        {
            return true;
        }

        $gateways = [];

        $paymentBank = $this->input['payment']->getBank();

        $authType = $this->input['payment']->getAuthType();

        $terminalGateway = $terminal->getGateway();

        $authTypeGateways = ($authType !== null) ? Gateway::getEmandateGatewaysForAuthType($authType) : [];

        // @todo: Can be more cleaner
        foreach (Gateway::$gatewaysEmandateBanksMap as $gateway => $gatewaySupportedBanks)
        {
            if (in_array($paymentBank, $gatewaySupportedBanks, true) === true)
            {
                if (($authType !== null) and
                    (in_array($gateway, $authTypeGateways, true) === false))
                {
                    continue;
                }

                $gateways[] = $gateway;
            }
        }

        return in_array($terminalGateway, $gateways);
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

        $payment = $this->input['payment'];

        $gatewayTokens = $this->input['gateway_tokens'];

        //
        // All first recurring payments or payments made via public
        // auth need to go via 3DS Recurring terminals only.
        //
        if ($payment->isSecondRecurring(true, $gatewayTokens) === false)
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

        $applicableTypes = [
            Terminal\Type::RECURRING_3DS,
            Terminal\Type::RECURRING_NON_3DS,
        ];

        //
        // If the terminal supports both [recurring 3ds and recurring non-3ds] or [no-2fa],
        // we don't care about gateway tokens. We care about gateway tokens
        // only because of 2fa. But if the terminal supports both [3ds and
        // non-3ds] or [no-2fa], it means that the terminal does not care about 2fa and
        // hence, we don't need to too. We can just use this terminal without
        // worrying about whether we have a gateway token for this or not.
        //
        // Also, we would be doing this only for direct terminals and for card
        // payments. Though, it would be applicable for shared terminals also,
        // we don't want to fallback on that just yet.
        //
        if ((empty(array_diff($applicableTypes, $terminal->getType())) === true) or
            ($terminal->isNo2Fa() === true))
        {

            if (($terminal->isFallbackApplicable($this->input['merchant']) === true) and
                ($payment->isCard() === true))
            {
                return true;
            }
        }

        return (new Terminal\Core)->hasApplicableGatewayTokens($terminal, $payment, $gatewayTokens);
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

                if ((Gateway::isUpiIntentFlowSupported($gateway) === true) and
                    ($terminal->isPay() === true))
                {
                    return true;
                }

                return false;
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

        return $terminal->isValidEmiTerminal($gateway, $emiDuration);
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
        if ($this->input['payment']->isTpvMethod() === true)
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
     * @param array            $applicableTerminals
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

    public function authTypeFilter(Terminal\Entity $terminal)
    {
        $payment = $this->input['payment'];

        if ($payment->isMethodCardOrEmi() === false)
        {
            return true;
        }

        //
        // We use preferred_auth only if it's available else to fallback to
        // $authType attribute
        //
        $authType = (array) $payment->getAuthType();

        $authTypes = $payment->getMetadata(Payment\Entity::PREFERRED_AUTH, $authType);

        //
        // We fallback to the default flow if the preferred authentication or authType
        // is empty. Normal flow chooses all the 3ds terminals.
        //
        if (empty($authTypes) === false)
        {
            foreach ($authTypes as $authType)
            {
                switch ($authType)
                {
                    case Payment\AuthType::PIN:
                        $gateway = $terminal->getGateway();
                        $acquirer = $terminal->getGatewayAcquirer();

                        $issuer = $payment->card->getIssuer();

                        //
                        // Pin auth terminal is only selected when the terminal issuer supports pin auth
                        // and card iin also supports the flow
                        //
                        if (($terminal->isPin() === true) and
                            (Gateway::isIssuerSupportedForPinAuthType($issuer, $gateway, $acquirer) === true))
                        {
                            if (($payment->card->iinRelation !== null) and
                                ($payment->card->iinRelation->supports(Flow::PIN) === true))
                            {
                                return true;
                            }
                        }

                        break;

                    case Payment\AuthType::_3DS:
                        if ($this->is3DSTerminal($terminal) === true)
                        {
                            return true;
                        }

                        break;
                }
            }

            //
            // If the terminal doesn't match the given condition then
            // we filter that terminal.
            //
            return false;
        }

        // Default terminals should always be the one which supports 3DS
        // Any other auth type terminals should be filtered out if `auth_type`
        // is empty or null.
        // In case, we have plan to add new auth in the filter, we will have to
        // add a condition here to remove terminals of that auth type while
        // ensuring that all other gateways are selected.
        return ($this->is3DSTerminal($terminal) === true);
    }

    protected function is3DSTerminal($terminal)
    {
        return ($terminal->isPin() === false);
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

    public function bharatQrFilter($terminal)
    {
        if ($this->input['payment']->isBharatQr() === true)
        {
            return $terminal->isBharatQr();
        }
        else
        {
            return ($terminal->isBharatQr() === false);
        }
    }
}
