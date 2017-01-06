<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Currency\Currency;
use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\Terminal\Shared;
use RZP\Models\Payment\Processor\Netbanking;

class TransactionFilter extends Terminal\Filter
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
        'method',
        'network',
        'currency',
        'international',
        'bank',
        'maestro',
        'netbanking_billdesk',
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

    public function currencyFilter($terminal, $input)
    {
        $payment = $input['payment'];

        $paymentCurrency = $payment->getCurrency();

        if ($payment->getConvertCurrency() === true)
        {
            $paymentCurrency = Currency::INR;
        }

        $terminalCurrency = $terminal->getCurrency();

        return ($paymentCurrency === $terminalCurrency);
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

            $isTPV = $input['merchant']->isTPVRequired();

            $gateways = Gateway::getGatewaysForNetbankingBank($bank, $isTPV);

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

    public function netbankingBilldeskFilter($terminal, $input)
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
                case 'securities' :
                case 'commodities' :
                    return ($terminal->isShared() === false);
                    break;

                // If corporate or mutual_funds then the corresponding
                // terminal should not be used, as ICIC is not being allowed
                // on that terminal
                case 'corporate':
                    if (in_array($bank, self::CORPORATE_IFSC, true) === false)
                    {
                        return true;
                    }

                    return ($networkCategory !== $category2);
                    break;

                case 'mutual_funds':
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

    public function recurringFilter($terminal, $input)
    {
        $payment = $input['payment'];

        $value = Terminal\Recurring::NON_RECURRING;

        // for cybersource, check get the terminal based on recurring type
        if ($payment->isRecurring() === true)
        {
            // for recurring payment, terminal must be cybersource
            if (($terminal->getGateway() !== Gateway::CYBERSOURCE) or
                ($terminal->getGatewayAcquirer() !== 'hdfc'))
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
