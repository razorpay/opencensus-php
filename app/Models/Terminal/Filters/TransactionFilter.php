<?php

namespace RZP\Models\Terminal\Filters;

use App;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Type;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Shared;
use RZP\Models\Currency\Currency;
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
        'recurring',
        'gateway',
        'subscription',
        'tpv',
        'pharma',
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

        // for cybersource, check get the terminal based on recurring type
        if ($payment->isRecurring() === true)
        {
            $recurring = Gateway::isRecurringGateway($terminal->getGateway());

            if ($recurring === false)
            {
                return false;
            }

            if ($terminal->getGateway() === Gateway::CYBERSOURCE)
            {
                // for cybersource recurring payment, terminal must be hdfc acquired
                if ($terminal->getGatewayAcquirer() !== 'hdfc')
                {
                    return false;
                }
            }

            $ba = app('basicauth');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $access = (($ba->isPrivateAuth() === true) or ($ba->isPrivilegeAuth() === true));

            // Check if this is the second recurring payment
            if (($token !== null) and
                ($token->isRecurring() === true) and
                ($access === true))
            {
                $reference = $payment->getReferenceForGatewayToken();

                $gatewayTokens = $this->repo->gateway_token->findByTokenAndReference($token, $reference);

                $gatewayTokensCount = $gatewayTokens->count();

                if ($gatewayTokensCount === 1)
                {
                    //
                    // For second recurring payment, ensure that we select a terminal
                    // of the same gateway as for the first recurring payment and also
                    // of the same merchant (shared, direct)
                    //
                    $previousGateway = $gatewayTokens->first()->terminal->getGateway();
                    $previousMerchant = $gatewayTokens->first()->terminal->getMerchantId();

                    $currentGateway = $terminal->getGateway();
                    $currentMerchant = $terminal->getMerchantId();

                    return (($terminal->isNon3DSRecurring() === true) and
                            ($previousGateway === $currentGateway) and
                            ($previousMerchant === $currentMerchant));
                }
                //
                // If a token is present and is supposed to be subsequent charge,
                // the corresponding gateway_token must always be present.
                // If it's not present, there's something wrong somewhere!
                //
                else
                {
                    throw new Exception\LogicException(
                        'Should have gotten exactly 1 gateway token.',
                        ErrorCode::SERVER_ERROR_GATEWAY_TOKENS_INVALID_COUNT,
                        [
                            'gateway_tokens_count'  => $gatewayTokensCount,
                            'payment_id'            => $payment->getId(),
                            'token_id'              => $token->getId(),
                            'reference'             => $reference
                        ]);
                }
            }
            else
            {
                return ($terminal->is3DSRecurring() === true);
            }
        }

        return ($terminal->isNonRecurring() === true);
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
            return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false));
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
}
