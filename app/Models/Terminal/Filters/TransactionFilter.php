<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Models\Terminal\Shared;

class TransactionFilter extends Terminal\Filter
{
    protected $properties = [
        'method',
        'network',
        'international',
        'bank',
        'maestro',
        'recurring',
    ];

    public function methodFilter($terminal, $input)
    {
        $method = $input['payment']->getMethod();

        switch ($method)
        {
            case Method::CARD:
                return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false));

            case Method::NETBANKING:
                return $terminal->isNetbankingEnabled();

            case Method::EMI:
                return $this->isValidEmiTerminal($terminal, $input);

            // Pick the right terminal only
            case Method::WALLET:
                $wallet = $input['payment']->getWallet();

                $gateway = Gateway::getGatewayForWallet($wallet);

                return ($gateway === $terminal->getGateway());

            case Method::UPI:
                return $terminal->isUPITerminal();

            default:
                throw new Exception\LogicException('Unknown payment method passed.', null, ['method' => $method]);
        }
    }

    // Applicable only for card and emi
    public function networkFilter($terminal, $input)
    {
        if ($input['payment']->isMethodCardOrEmi())
        {
            $network = $input['payment']->card->getNetworkCode();

            return Gateway::isCardNetworkSupported($network, $terminal->getGateway());
        }

        return true;
    }

    public function internationalFilter($terminal, $input)
    {
        if ($input['payment']->isMethodCardOrEmi() === false)
        {
            return true;
        }

        $isPaymentInternational = $input['payment']->isInternational();

        if (($input['mode'] === Mode::TEST) and ($isPaymentInternational))
        {
            // Allow support for cards on atom for international test
            $testTerminals = array_merge(
                                [Gateway::ATOM, Gateway::AXIS_GENIUS, Gateway::PAYTM],
                                Gateway::$internationalCardGateways);

            return in_array($terminal->getGateway(), $testTerminals);
        }
        else if ($isPaymentInternational)
        {
            return in_array($terminal->getGateway(), Gateway::$internationalCardGateways);
        }
        else if ($input['mode'] === Mode::TEST)
        {
            $testTerminals = array_merge(
                                Gateway::$domesticCardGateways,
                                Gateway::$domesticCardGatewaysInTest);

            return in_array($terminal->getGateway(), $testTerminals);
        }
        else
        {
            return in_array($terminal->getGateway(), Gateway::$domesticCardGateways);
        }
    }

    public function bankFilter($terminal, $input)
    {
        if ($input['payment']->isNetbanking())
        {
            $bank = $input['payment']->getBank();

            $terminalGateway = $terminal->getGateway();

            $gateways = Gateway::getGatewaysForNetbankingBank($bank);

            return in_array($terminalGateway, $gateways);
        }

        return true;
    }

    public function maestroFilter($terminal, $input)
    {
        if ($input['payment']->isMethodCardOrEmi())
        {
            $network = $input['payment']->card->getNetworkCode();

            // Only shared terminals support Maestro on Live mode.
            if (($network === Network::MAES) and
                ($input['mode'] === Mode::LIVE))
            {
                return Shared::isSharedTerminal($terminal);
            }
        }

        return true;
    }

    public function recurringFilter($terminal, $input)
    {
        $payment = $input['payment'];

        $value = Terminal\Recurring::NON_RECURRING;

        // for cybersource, check get the terminal based on recurring type
        if ($payment->isRecurring() === true)
        {
            // for recurring payment, terminal must be cybersource
            if ($terminal->getGateway() !== Gateway::CYBERSOURCE)
            {
                return false;
            }

            $ba = app('basicauth');

            if (($payment->getTokenId() !== null) and
                ($payment->localToken->isRecurring() === true) and
                ($ba->isPrivateAuth() === true))
            {
                $value = Terminal\Recurring::RECURRING_N3DS;
            }
        }

        $isValidTerminal = ($terminal->getRecurring() === $value);

        return $isValidTerminal;
    }

    protected function isValidEmiTerminal($terminal, $input)
    {
        $bank = $input['payment']->getBank();

        // check if banks emi transactions can be processed from any card terminal
        if ((empty($bank) === false) and
            (in_array($bank, Gateway::$emiBanksUsingCardTerminals)))
        {
            return (($terminal->isCardEnabled()) and ($terminal->isEmiEnabled() === false));
        }

        // validate terminal using the gateway and emi duration
        $network = $input['payment']->card->getNetworkCode();

        if ($network === Network::AMEX)
        {
            $gateway = Gateway::AMEX;
        }
        else
        {
            $gateway = Gateway::$emiBankToGatewayMap[$bank];
        }

        $emiDuration = $input['payment']->emiPlan->getDuration();

        return $terminal->isValidEmiTerminal($gateway, $emiDuration);

    }
}
